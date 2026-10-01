<?php

namespace App\Livewire\Pipeline;

use App\Enums\PipelineStage;
use App\Enums\ServiceType;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pipeline')]
class Board extends Component
{
    use RefreshesOnSave;

    private const CARDS_PER_COLUMN = 60;

    #[Session]
    public string $search = '';

    #[Session]
    public string $owner = '';

    #[Session]
    public string $serviceType = '';

    /** Muestra Censo realizado y Perdido de cualquier fecha (por defecto, últimos 90 días). */
    #[Session]
    public bool $showAllClosed = false;

    #[Locked]
    public ?string $pendingLostId = null;

    public bool $confirmingLost = false;

    public string $lostReason = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Opportunity::class);
    }

    #[Computed]
    public function columns(): array
    {
        $base = Opportunity::query()
            ->joinRanchAndClient()
            ->withFinancials()
            ->with(['ranch.client'])
            ->whereNull('ranches.deleted_at')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('clients.name', 'like', "%{$this->search}%")
                ->orWhere('ranches.name', 'like', "%{$this->search}%")
                ->orWhere('ranches.municipality', 'like', "%{$this->search}%")))
            ->when($this->owner, fn ($q) => $q->where('opportunities.owner_id', $this->owner))
            ->when($this->serviceType, fn ($q) => $q->where('opportunities.service_type', $this->serviceType));

        $columns = [];
        foreach (PipelineStage::cases() as $stage) {
            $query = (clone $base)->where('opportunities.stage', $stage->value);

            if (in_array($stage, [PipelineStage::CensusDone, PipelineStage::Lost], true) && ! $this->showAllClosed) {
                $query->where('opportunities.stage_changed_at', '>=', now()->subDays(90));
            }

            $query->when(
                $stage === PipelineStage::Confirmed,
                fn ($q) => $q->orderByRaw('CASE WHEN opportunities.census_date IS NULL THEN 1 ELSE 0 END')->orderBy('opportunities.census_date'),
                fn ($q) => $q->orderByDesc('opportunities.stage_changed_at'),
            );

            $count = (clone $query)->count();
            $items = $query->limit(self::CARDS_PER_COLUMN)->get();

            $columns[] = [
                'stage' => $stage,
                'count' => $count,
                'items' => $items,
                'total' => $items->sum(fn (Opportunity $o) => $o->quotedTotal() ?? 0),
                'hectares' => $items->sum(fn (Opportunity $o) => (float) $o->quoted_hectares),
            ];
        }

        return $columns;
    }

    /** Drag & drop: wire:sort llama con (id, posición, etapa destino). */
    public function handleSort(string $id, int $position, string $stage, OpportunityService $service): void
    {
        $opportunity = Opportunity::findOrFail($id);
        $this->authorize('update', $opportunity);
        $target = PipelineStage::tryFrom($stage);

        if (! $target || $opportunity->stage === $target) {
            return;
        }

        if ($target === PipelineStage::Lost) {
            $this->pendingLostId = $opportunity->id;
            $this->lostReason = '';
            $this->confirmingLost = true;

            return;
        }

        $service->changeStage($opportunity, $target);
        $this->dispatch('notify', message: $opportunity->ranch->name.' → '.$target->label());
    }

    public function confirmLost(OpportunityService $service): void
    {
        $this->validate(['lostReason' => ['nullable', 'string', 'max:255']]);
        $opportunity = Opportunity::findOrFail($this->pendingLostId);
        $this->authorize('update', $opportunity);
        $service->changeStage($opportunity, PipelineStage::Lost, $this->lostReason);
        $this->reset('pendingLostId', 'confirmingLost', 'lostReason');
        $this->dispatch('notify', message: 'Oportunidad marcada como perdida');
    }

    public function cancelLost(): void
    {
        $this->reset('pendingLostId', 'confirmingLost', 'lostReason');
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'owner', 'serviceType');
    }

    public function render()
    {
        return view('livewire.pipeline.board', [
            'users' => User::active()->orderBy('name')->pluck('name', 'id')->all(),
            'serviceTypes' => ServiceType::options(),
        ]);
    }
}
