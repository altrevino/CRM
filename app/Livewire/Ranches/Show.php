<?php

namespace App\Livewire\Ranches;

use App\Enums\PipelineStage;
use App\Livewire\Concerns\ManagesTasks;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Models\ActivityLog;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\Ranch;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use ManagesTasks, RefreshesOnSave;

    public Ranch $ranch;

    public int $activityLimit = 15;

    public function mount(Ranch $ranch): void
    {
        $this->authorize('view', $ranch);
        $this->ranch = $ranch;
    }

    #[Computed]
    public function opportunities()
    {
        return Opportunity::where('ranch_id', $this->ranch->id)
            ->withFinancials()
            ->with(['ranch', 'species'])
            ->orderByDesc('created_at')
            ->get();
    }

    /** Servicios futuros/abiertos vs. realizados y perdidos. */
    #[Computed]
    public function groups(): array
    {
        return [
            'scheduled' => $this->opportunities->filter(fn ($o) => $o->isScheduled())->sortBy('census_date'),
            'active' => $this->opportunities->filter(fn ($o) => ! $o->isScheduled() && ($o->stage->isOpen() || $o->stage === PipelineStage::Confirmed)),
            'done' => $this->opportunities->filter(fn ($o) => $o->stage === PipelineStage::CensusDone)->sortByDesc('census_date'),
            'lost' => $this->opportunities->filter(fn ($o) => $o->stage === PipelineStage::Lost),
        ];
    }

    #[Computed]
    public function quotes()
    {
        return Quote::whereIn('opportunity_id', $this->opportunities->pluck('id'))
            ->orderByDesc('issued_at')->orderByDesc('folio')->orderByDesc('version')->get();
    }

    #[Computed]
    public function tasks()
    {
        return $this->ranch->tasks()->pending()->with(['opportunity.ranch', 'assignee'])->orderBy('due_date')->get();
    }

    #[Computed]
    public function activity()
    {
        return ActivityLog::where('ranch_id', $this->ranch->id)
            ->with(['user', 'ranch', 'subject'])
            ->latest('created_at')->latest('id')
            ->limit($this->activityLimit + 1)->get();
    }

    public function loadMoreActivity(): void
    {
        $this->activityLimit += 20;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->ranch);

        if ($this->ranch->opportunities()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: el rancho tiene servicios registrados.', type: 'error');

            return;
        }

        $clientId = $this->ranch->client_id;
        $this->ranch->delete();
        session()->flash('notify', 'Rancho eliminado');
        $this->redirectRoute('clients.show', $clientId, navigate: true);
    }

    public function render()
    {
        $this->ranch->refresh()->load(['client', 'state']);

        return view('livewire.ranches.show')->title($this->ranch->name);
    }
}
