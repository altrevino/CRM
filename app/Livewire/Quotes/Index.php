<?php

namespace App\Livewire\Quotes;

use App\Enums\QuoteStatus;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Livewire\Concerns\WithTable;
use App\Models\Client;
use App\Models\MexicanState;
use App\Models\Quote;
use App\Services\AttentionService;
use App\Services\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cotizaciones')]
class Index extends Component
{
    use RefreshesOnSave, WithTable;

    #[Session]
    public string $status = '';

    #[Session]
    public string $client = '';

    #[Session]
    public string $state = '';

    #[Session]
    public string $vat = '';

    #[Session]
    public string $dateFrom = '';

    #[Session]
    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Quote::class);
    }

    protected function sortable(): array
    {
        return ['number', 'issued_at', 'client_name', 'ranch_name', 'hectares', 'subtotal', 'vat_amount', 'total', 'status'];
    }

    protected function filterProperties(): array
    {
        return ['status', 'client', 'state', 'vat', 'dateFrom', 'dateTo'];
    }

    protected function query(): Builder
    {
        $field = in_array($this->sortField, $this->sortable(), true) ? $this->sortField : 'issued_at';
        $direction = $this->sortField ? $this->direction() : 'desc';
        $column = match ($field) {
            'number' => 'quotes.folio',
            'client_name', 'ranch_name' => $field,
            default => 'quotes.'.$field,
        };

        return Quote::query()
            ->select('quotes.*', 'clients.name as client_name', 'ranches.name as ranch_name')
            ->join('opportunities', 'opportunities.id', '=', 'quotes.opportunity_id')
            ->join('ranches', 'ranches.id', '=', 'opportunities.ranch_id')
            ->join('clients', 'clients.id', '=', 'ranches.client_id')
            ->whereNull('opportunities.deleted_at')
            ->with(['opportunity.ranch.client', 'opportunity.ranch.state'])
            ->search($this->search)
            ->when($this->status, fn ($q) => $q->where('quotes.status', $this->status))
            ->when($this->client, fn ($q) => $q->where('ranches.client_id', $this->client))
            ->when($this->state, fn ($q) => $q->where('ranches.state_id', $this->state))
            ->when($this->vat !== '', fn ($q) => $q->where('quotes.apply_vat', $this->vat === 'si'))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('quotes.issued_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('quotes.issued_at', '<=', $this->dateTo))
            ->orderBy($column, $direction)
            ->when($field === 'number', fn ($q) => $q->orderBy('quotes.version', $direction))
            ->orderByDesc('quotes.folio')->orderByDesc('quotes.version');
    }

    public function export()
    {
        $rows = $this->query()->lazy(200)->map(fn (Quote $q) => [
            $q->number,
            fecha($q->issued_at, ''),
            $q->client_name,
            $q->ranch_name,
            $q->opportunity->ranch->municipality,
            $q->opportunity->ranch->state?->name,
            $q->opportunity->service_type->label(),
            CsvExporter::number($q->hectares),
            CsvExporter::number($q->service_amount),
            CsvExporter::number($q->logistics_amount),
            CsvExporter::number($q->subtotal),
            $q->apply_vat ? 'Sí' : 'No',
            CsvExporter::number($q->vat_rate),
            CsvExporter::number($q->vat_amount),
            CsvExporter::number($q->total),
            $q->status->label(),
            $q->opportunity->stage->label(),
        ]);

        return CsvExporter::download('cotizaciones', [
            'Número', 'Fecha', 'Cliente', 'Rancho', 'Municipio', 'Estado', 'Servicio', 'Hectáreas', 'Importe servicio',
            'Logística', 'Subtotal', 'Aplica IVA', '% IVA', 'IVA', 'Total', 'Estado cotización', 'Etapa del servicio',
        ], $rows);
    }

    public function render()
    {
        $quotes = $this->query()->paginate(25);

        return view('livewire.quotes.index', [
            'quotes' => $quotes,
            'staleIds' => array_flip(app(AttentionService::class)->staleQuoteIds()),
            'statuses' => QuoteStatus::options(),
            'clients' => Client::orderBy('name')->pluck('name', 'id')->all(),
            'states' => MexicanState::orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }
}
