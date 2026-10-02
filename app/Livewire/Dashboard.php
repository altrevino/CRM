<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesTasks;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Models\ActivityLog;
use App\Models\Opportunity;
use App\Models\Task;
use App\Services\AttentionService;
use App\Services\MetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    use ManagesTasks, RefreshesOnSave;

    public int $year;

    public function mount(): void
    {
        $this->year = (int) today()->year;
    }

    #[Computed]
    public function kpis(): array
    {
        $m = app(MetricsService::class);

        return [
            'sales' => $m->confirmedSales(),
            'salesCount' => $m->confirmedSalesCount(),
            'wonWithoutQuote' => $m->wonWithoutAcceptedQuoteCount(),
            'hectares' => $m->confirmedHectares(),
            'scheduled' => $m->scheduledCensusCount(),
            'pipeline' => $m->pipelineAmount(),
            'openCount' => $m->openOpportunitiesCount(),
            'balance' => $m->pendingBalance(),
            'overdue' => $m->overdueTasksCount(),
        ];
    }

    #[Computed]
    public function attention()
    {
        return app(AttentionService::class)->sections();
    }

    #[Computed]
    public function followUps(): array
    {
        $with = ['opportunity.ranch', 'client', 'assignee'];

        return [
            'overdue' => Task::overdue()->with($with)->orderBy('due_date')->limit(8)->get(),
            'today' => Task::dueToday()->with($with)->limit(8)->get(),
            'upcoming' => Task::upcoming()->whereDate('due_date', '<=', today()->addDays(14))->with($with)->orderBy('due_date')->limit(8)->get(),
        ];
    }

    #[Computed]
    public function charts(): array
    {
        $m = app(MetricsService::class);
        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $byState = $m->opportunitiesByState();
        $byMunicipality = $m->opportunitiesByMunicipality();

        return [
            'stages' => $m->opportunitiesByStage(),
            'sales' => ['labels' => $months, 'values' => $m->salesByMonth($this->year)],
            'hectares' => ['labels' => $months, 'values' => $m->hectaresByMonth($this->year)],
            'states' => ['labels' => array_keys($byState), 'values' => array_values($byState)],
            'municipalities' => [
                'labels' => array_keys($byMunicipality),
                'values' => array_values($byMunicipality),
                'links' => array_map(fn ($m) => route('ranches.index', ['municipio' => $m]), array_keys($byMunicipality)),
            ],
            'indicators' => $m->indicators(),
        ];
    }

    #[Computed]
    public function upcomingCensus()
    {
        return Opportunity::scheduled()->withFinancials()
            ->whereDate('census_date', '>=', today())
            ->with(['ranch.client'])
            ->orderBy('census_date')->limit(6)->get();
    }

    #[Computed]
    public function recentActivity()
    {
        return ActivityLog::with(['user', 'ranch', 'subject'])->latest('created_at')->latest('id')->limit(8)->get();
    }

    public function setYear(int $year): void
    {
        $this->year = max(2000, min(2100, $year));
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
