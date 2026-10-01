<?php

namespace App\Livewire\Census;

use App\Livewire\Concerns\RefreshesOnSave;
use App\Models\Opportunity;
use App\Models\Setting;
use App\Services\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Censos programados = oportunidades en etapa Confirmado con fecha de censo.
 * No existe una tabla propia: todo se deriva de la oportunidad.
 */
#[Title('Censos programados')]
class Index extends Component
{
    use RefreshesOnSave;

    #[Url(as: 'vista', except: 'calendario')]
    public string $mode = 'calendario';

    #[Url(as: 'mes')]
    public string $month = '';

    #[Session]
    public string $search = '';

    /** Incluye censos con fecha pasada que siguen en Confirmado. */
    #[Session]
    public bool $includePast = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Opportunity::class);
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = today()->format('Y-m');
        }
        if (! in_array($this->mode, ['calendario', 'lista'], true)) {
            $this->mode = 'calendario';
        }
    }

    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->month = today()->format('Y-m');
    }

    private function monthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay();
    }

    protected function baseQuery(): Builder
    {
        return Opportunity::scheduled()
            ->joinRanchAndClient()
            ->withFinancials()
            ->with(['ranch.client', 'ranch.state'])
            ->whereNull('ranches.deleted_at')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('clients.name', 'like', "%{$this->search}%")
                ->orWhere('ranches.name', 'like', "%{$this->search}%")
                ->orWhere('ranches.municipality', 'like', "%{$this->search}%")));
    }

    protected function listQuery(): Builder
    {
        return $this->baseQuery()
            ->when(! $this->includePast, fn ($q) => $q->whereDate('opportunities.census_date', '>=', today()))
            ->orderBy('opportunities.census_date')
            ->orderBy('ranches.name');
    }

    #[Computed]
    public function calendar(): array
    {
        $start = $this->monthStart();
        $gridStart = $start->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $events = $this->baseQuery()
            ->whereDate('opportunities.census_date', '>=', $gridStart->toDateString())
            ->whereDate('opportunities.census_date', '<=', $gridEnd->toDateString())
            ->orderBy('opportunities.census_date')
            ->get()
            ->groupBy(fn (Opportunity $o) => $o->census_date->toDateString());

        $weeks = [];
        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $weeks[intdiv($gridStart->diffInDays($day), 7)][] = [
                'date' => $day->copy(),
                'inMonth' => $day->month === $start->month,
                'isToday' => $day->isToday(),
                'events' => $events->get($day->toDateString(), collect()),
            ];
        }

        return [
            'label' => ucfirst($start->translatedFormat('F Y')),
            'weeks' => $weeks,
            'monthEvents' => $events->flatten(1)->filter(fn ($o) => $o->census_date->month === $start->month)->values(),
        ];
    }

    public function export()
    {
        $rows = $this->listQuery()->lazy(200)->map(fn (Opportunity $o) => [
            fecha($o->census_date, ''),
            $o->ranch->client->name,
            $o->ranch->client->phone,
            $o->ranch->name,
            $o->ranch->municipality,
            $o->ranch->state?->name,
            $o->ranch->fence_type?->label(),
            $o->service_type->label(),
            CsvExporter::number($o->quoted_hectares),
            CsvExporter::number($o->quotedTotal()),
            CsvExporter::number($o->paidTotal()),
            CsvExporter::number($o->balance()),
            implode(', ', $o->missingInfo()),
        ]);

        return CsvExporter::download('censos-programados', [
            'Fecha de censo', 'Cliente', 'Teléfono', 'Rancho', 'Municipio', 'Estado', 'Cerca', 'Servicio',
            'Hectáreas', 'Total cotizado', 'Pagado', 'Saldo', 'Información faltante',
        ], $rows);
    }

    public function render()
    {
        return view('livewire.census.index', [
            'items' => $this->mode === 'lista' ? $this->listQuery()->get() : collect(),
            'upcomingDays' => Setting::upcomingCensusDays(),
        ]);
    }
}
