<?php

namespace App\Livewire\Payments;

use App\Livewire\Concerns\RefreshesOnSave;
use App\Livewire\Concerns\WithTable;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pagos')]
class Index extends Component
{
    use RefreshesOnSave, WithTable;

    #[Session]
    public string $method = '';

    #[Session]
    public string $client = '';

    #[Session]
    public string $dateFrom = '';

    #[Session]
    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Payment::class);
    }

    protected function sortable(): array
    {
        return ['paid_at', 'amount', 'client_name', 'ranch_name'];
    }

    protected function filterProperties(): array
    {
        return ['method', 'client', 'dateFrom', 'dateTo'];
    }

    protected function query(): Builder
    {
        $field = in_array($this->sortField, $this->sortable(), true) ? $this->sortField : 'paid_at';
        $direction = $this->sortField ? $this->direction() : 'desc';

        return Payment::query()
            ->select('payments.*', 'clients.name as client_name', 'ranches.name as ranch_name')
            ->join('opportunities', 'opportunities.id', '=', 'payments.opportunity_id')
            ->join('ranches', 'ranches.id', '=', 'opportunities.ranch_id')
            ->join('clients', 'clients.id', '=', 'ranches.client_id')
            ->with(['method', 'quote', 'creator', 'opportunity.ranch.client'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('clients.name', 'like', "%{$this->search}%")
                ->orWhere('ranches.name', 'like', "%{$this->search}%")
                ->orWhere('payments.reference', 'like', "%{$this->search}%")))
            ->when($this->method, fn ($q) => $q->where('payments.payment_method_id', $this->method))
            ->when($this->client, fn ($q) => $q->where('ranches.client_id', $this->client))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('payments.paid_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('payments.paid_at', '<=', $this->dateTo))
            ->orderBy(in_array($field, ['client_name', 'ranch_name'], true) ? $field : 'payments.'.$field, $direction)
            ->orderByDesc('payments.created_at');
    }

    public function export()
    {
        $rows = $this->query()->lazy(200)->map(fn (Payment $p) => [
            fecha($p->paid_at, ''),
            $p->client_name,
            $p->ranch_name,
            $p->opportunity->title(),
            $p->quote?->number,
            CsvExporter::number($p->amount),
            $p->method->name,
            $p->reference,
            $p->notes,
            $p->creator->name,
        ]);

        return CsvExporter::download('pagos', [
            'Fecha', 'Cliente', 'Rancho', 'Servicio', 'Cotización', 'Monto', 'Forma de pago', 'Referencia', 'Notas', 'Registró',
        ], $rows);
    }

    public function render()
    {
        return view('livewire.payments.index', [
            'payments' => $this->query()->paginate(25),
            'total' => (float) (clone $this->query())->reorder()->sum('payments.amount'),
            'methods' => PaymentMethod::orderBy('sort_order')->pluck('name', 'id')->all(),
            'clients' => Client::orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }
}
