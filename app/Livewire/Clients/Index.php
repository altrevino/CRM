<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\RefreshesOnSave;
use App\Livewire\Concerns\WithTable;
use App\Models\Client;
use App\Services\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Clientes')]
class Index extends Component
{
    use RefreshesOnSave, WithTable;

    /** '' | con_saldo | con_activos | seguimiento_vencido | sin_seguimiento */
    #[Session]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Client::class);
    }

    protected function sortable(): array
    {
        return ['name', 'ranches_count', 'active_opportunities_count', 'total_sold', 'last_contact_at', 'next_follow_up_at', 'created_at'];
    }

    protected function filterProperties(): array
    {
        return ['status'];
    }

    protected function query(): Builder
    {
        $query = Client::query()->withSummary()->search($this->search);

        match ($this->status) {
            'con_saldo' => $query->whereRaw('(SELECT COALESCE(SUM(q.total),0) FROM quotes q JOIN opportunities o ON o.id = q.opportunity_id JOIN ranches r ON r.id = o.ranch_id WHERE r.client_id = clients.id AND q.status = ? AND q.deleted_at IS NULL AND o.deleted_at IS NULL AND o.stage IN (?, ?))
                > (SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN opportunities o ON o.id = p.opportunity_id JOIN ranches r ON r.id = o.ranch_id WHERE r.client_id = clients.id AND p.deleted_at IS NULL AND o.deleted_at IS NULL AND o.stage IN (?, ?))',
                ['aceptada', 'confirmado', 'censo_realizado', 'confirmado', 'censo_realizado']),
            'con_activos' => $query->whereHas('opportunities', fn ($q) => $q->whereIn('stage', ['prospecto', 'cotizacion_enviada', 'seguimiento', 'pendiente_anticipo', 'confirmado'])),
            'seguimiento_vencido' => $query->whereHas('tasks', fn ($q) => $q->overdue()),
            'sin_seguimiento' => $query->whereDoesntHave('tasks', fn ($q) => $q->pending()),
            default => null,
        };

        $field = in_array($this->sortField, $this->sortable(), true) ? $this->sortField : 'name';

        return $query->orderBy($field, $this->sortField ? $this->direction() : 'asc')->orderBy('clients.id');
    }

    public function delete(string $id): void
    {
        $client = Client::withCount('ranches')->findOrFail($id);
        $this->authorize('delete', $client);

        if ($client->ranches_count > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar: el cliente tiene ranchos registrados.', type: 'error');

            return;
        }

        $client->delete();
        $this->dispatch('notify', message: 'Cliente eliminado');
    }

    public function export()
    {
        $rows = $this->query()->lazy(200)->map(fn (Client $c) => [
            $c->name,
            $c->phone,
            $c->ranches_count,
            $c->active_opportunities_count,
            CsvExporter::number($c->total_sold),
            CsvExporter::number($c->balance),
            fecha($c->last_contact_at, ''),
            fecha($c->next_follow_up_at, ''),
            fecha($c->created_at, ''),
        ]);

        return CsvExporter::download('clientes', [
            'Nombre', 'Teléfono', 'Ranchos', 'Servicios activos', 'Total vendido', 'Saldo pendiente',
            'Último contacto', 'Próximo seguimiento', 'Fecha de alta',
        ], $rows);
    }

    public function render()
    {
        return view('livewire.clients.index', [
            'clients' => $this->query()->paginate(25),
            'statusOptions' => [
                'con_activos' => 'Con servicios activos',
                'con_saldo' => 'Con saldo pendiente',
                'seguimiento_vencido' => 'Con seguimiento vencido',
                'sin_seguimiento' => 'Sin seguimiento programado',
            ],
        ]);
    }
}
