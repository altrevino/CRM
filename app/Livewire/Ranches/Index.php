<?php

namespace App\Livewire\Ranches;

use App\Enums\FenceType;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Livewire\Concerns\WithTable;
use App\Models\Client;
use App\Models\MexicanState;
use App\Models\Ranch;
use App\Services\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ranchos')]
class Index extends Component
{
    use RefreshesOnSave, WithTable;

    #[Session]
    public string $client = '';

    #[Session]
    public string $state = '';

    #[Session]
    public string $municipality = '';

    #[Session]
    public string $fence = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Ranch::class);

        // Enlaces desde el buscador global (?municipio=…) o fichas (?cliente=…).
        if (request()->filled('municipio')) {
            $this->clearFilters();
            $this->municipality = (string) request('municipio');
        }
        if (request()->filled('cliente')) {
            $this->clearFilters();
            $this->client = (string) request('cliente');
        }
    }

    protected function sortable(): array
    {
        return ['name', 'client_name', 'municipality', 'state_name', 'total_hectares', 'opportunities_count', 'last_census_date', 'next_census_date'];
    }

    protected function filterProperties(): array
    {
        return ['client', 'state', 'municipality', 'fence'];
    }

    protected function query(): Builder
    {
        $field = in_array($this->sortField, $this->sortable(), true) ? $this->sortField : 'name';

        return Ranch::query()
            ->select('ranches.*', 'clients.name as client_name', 'mexican_states.name as state_name')
            ->join('clients', 'clients.id', '=', 'ranches.client_id')
            ->leftJoin('mexican_states', 'mexican_states.id', '=', 'ranches.state_id')
            ->whereNull('clients.deleted_at')
            ->withSummary()
            ->with(['client', 'state'])
            ->search($this->search)
            ->when($this->client, fn ($q) => $q->where('ranches.client_id', $this->client))
            ->when($this->state, fn ($q) => $q->where('ranches.state_id', $this->state))
            ->when($this->municipality, fn ($q) => $q->where('ranches.municipality', $this->municipality))
            ->when($this->fence, fn ($q) => $q->where('ranches.fence_type', $this->fence))
            ->orderBy($field === 'name' ? 'ranches.name' : $field, $this->sortField ? $this->direction() : 'asc')
            ->orderBy('ranches.id');
    }

    public function delete(string $id): void
    {
        $ranch = Ranch::withCount('opportunities')->findOrFail($id);
        $this->authorize('delete', $ranch);

        if ($ranch->opportunities_count > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar: el rancho tiene servicios registrados.', type: 'error');

            return;
        }

        $ranch->delete();
        $this->dispatch('notify', message: 'Rancho eliminado');
    }

    public function export()
    {
        $rows = $this->query()->lazy(200)->map(fn (Ranch $r) => [
            $r->name,
            $r->client->name,
            $r->client->phone,
            $r->municipality,
            $r->state?->name,
            $r->fence_type?->label(),
            CsvExporter::number($r->total_hectares),
            $r->km_round_trip,
            $r->maps_url,
            $r->opportunities_count,
            fecha($r->last_census_date, ''),
            fecha($r->next_census_date, ''),
        ]);

        return CsvExporter::download('ranchos', [
            'Rancho', 'Cliente', 'Teléfono', 'Municipio', 'Estado', 'Cerca', 'Superficie (ha)', 'Km redondo',
            'Google Maps', 'Servicios', 'Último censo', 'Próximo censo',
        ], $rows);
    }

    public function render()
    {
        return view('livewire.ranches.index', [
            'ranches' => $this->query()->paginate(25),
            'clients' => Client::orderBy('name')->pluck('name', 'id')->all(),
            'states' => MexicanState::query()->whereIn('id', Ranch::query()->select('state_id'))->orderBy('name')->pluck('name', 'id')->all(),
            'municipalities' => Ranch::query()->whereNotNull('municipality')->distinct()->orderBy('municipality')->pluck('municipality', 'municipality')->all(),
            'fenceTypes' => FenceType::options(),
        ]);
    }
}
