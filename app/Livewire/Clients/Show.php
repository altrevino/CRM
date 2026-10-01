<?php

namespace App\Livewire\Clients;

use App\Enums\PipelineStage;
use App\Livewire\Concerns\ManagesTasks;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Ranch;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use ManagesTasks, RefreshesOnSave;

    public Client $client;

    public int $activityLimit = 15;

    public function mount(Client $client): void
    {
        $this->authorize('view', $client);
        $this->client = $client;
    }

    #[Computed]
    public function ranches()
    {
        return Ranch::where('client_id', $this->client->id)->withSummary()->with('state')->orderBy('name')->get();
    }

    #[Computed]
    public function opportunities()
    {
        return Opportunity::query()
            ->whereIn('ranch_id', $this->ranches->pluck('id'))
            ->withFinancials()
            ->with(['ranch', 'species'])
            ->orderByRaw('CASE WHEN stage IN (?, ?) THEN 1 ELSE 0 END', [PipelineStage::CensusDone->value, PipelineStage::Lost->value])
            ->latest()
            ->get();
    }

    #[Computed]
    public function quotes()
    {
        return Quote::whereIn('opportunity_id', $this->opportunities->pluck('id'))
            ->with('opportunity.ranch')
            ->orderByDesc('issued_at')->orderByDesc('folio')->orderByDesc('version')
            ->limit(20)->get();
    }

    #[Computed]
    public function payments()
    {
        return Payment::whereIn('opportunity_id', $this->opportunities->pluck('id'))
            ->with(['method', 'opportunity.ranch', 'quote'])
            ->orderByDesc('paid_at')->limit(20)->get();
    }

    #[Computed]
    public function tasks()
    {
        return $this->client->tasks()->pending()->with(['opportunity.ranch', 'assignee'])->orderBy('due_date')->get();
    }

    #[Computed]
    public function activity()
    {
        return ActivityLog::where('client_id', $this->client->id)
            ->with(['user', 'ranch', 'subject'])
            ->latest('created_at')->latest('id')
            ->limit($this->activityLimit + 1)->get();
    }

    #[Computed]
    public function totals(): array
    {
        $won = $this->opportunities->filter(fn (Opportunity $o) => in_array($o->stage, PipelineStage::won(), true));

        return [
            'sold' => $won->sum(fn (Opportunity $o) => $o->acceptedTotal() ?? 0),
            'balance' => $won->sum(fn (Opportunity $o) => $o->balance() ?? 0),
            'paid' => $this->opportunities->sum(fn (Opportunity $o) => $o->paidTotal()),
            'active' => $this->opportunities->filter(fn (Opportunity $o) => $o->stage->isOpen() || $o->stage === PipelineStage::Confirmed)->count(),
        ];
    }

    public function loadMoreActivity(): void
    {
        $this->activityLimit += 20;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->client);

        if ($this->client->ranches()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: primero elimina o reasigna sus ranchos.', type: 'error');

            return;
        }

        $this->client->delete();
        session()->flash('notify', 'Cliente eliminado');
        $this->redirectRoute('clients.index', navigate: true);
    }

    public function render()
    {
        $this->client->refresh();

        return view('livewire.clients.show')->title($this->client->name);
    }
}
