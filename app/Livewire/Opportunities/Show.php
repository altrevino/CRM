<?php

namespace App\Livewire\Opportunities;

use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Livewire\Concerns\ManagesTasks;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Models\Comment;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\AttentionService;
use App\Services\OpportunityService;
use App\Services\PaymentService;
use App\Services\QuoteService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Show extends Component
{
    use ManagesTasks, RefreshesOnSave;

    public Opportunity $opportunity;

    #[Url(except: 'resumen')]
    public string $tab = 'resumen';

    public string $comment = '';

    public bool $confirmingLost = false;

    public string $lostReason = '';

    public ?string $newCensusDate = null;

    public bool $showCompletedTasks = false;

    public function mount(Opportunity $opportunity): void
    {
        $this->authorize('view', $opportunity);
        $this->opportunity = $opportunity;
        if (! in_array($this->tab, ['resumen', 'cotizaciones', 'pagos', 'seguimientos', 'actividad'], true)) {
            $this->tab = 'resumen';
        }
    }

    #[Computed]
    public function record(): Opportunity
    {
        return Opportunity::withFinancials()
            ->with(['ranch.client', 'ranch.state', 'species', 'owner', 'acceptedQuote', 'creator'])
            ->findOrFail($this->opportunity->id);
    }

    #[Computed]
    public function quotes()
    {
        return Quote::where('opportunity_id', $this->opportunity->id)
            ->withCount('payments')->with('creator')
            ->orderByDesc('folio')->orderByDesc('version')->get();
    }

    #[Computed]
    public function payments()
    {
        return Payment::where('opportunity_id', $this->opportunity->id)
            ->with(['method', 'quote', 'creator'])
            ->orderByDesc('paid_at')->orderByDesc('created_at')->get();
    }

    #[Computed]
    public function tasks()
    {
        return $this->opportunity->tasks()
            ->when(! $this->showCompletedTasks, fn ($q) => $q->pending())
            ->with(['assignee', 'opportunity'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', ['pendiente'])
            ->orderBy('due_date')
            ->get();
    }

    #[Computed]
    public function activity()
    {
        return $this->opportunity->activityLogs()->with(['user', 'subject'])->limit(200)->get();
    }

    /** Alertas visuales del registro. @return list<array{tone: string, text: string}> */
    #[Computed]
    public function alerts(): array
    {
        $o = $this->record;
        $alerts = [];
        $pending = $this->opportunity->pendingTasks()->get();

        if ($pending->contains(fn ($t) => $t->isOverdue())) {
            $alerts[] = ['tone' => 'rose', 'text' => 'Tiene seguimientos vencidos.'];
        }
        if (($o->stage->isOpen() || $o->stage === PipelineStage::Confirmed) && $pending->isEmpty()) {
            $alerts[] = ['tone' => 'amber', 'text' => 'Sin próximo seguimiento programado.'];
        }
        if ($o->census_date && $o->stage === PipelineStage::Confirmed) {
            $days = (int) today()->diffInDays($o->census_date, false);
            if ($days < 0) {
                $alerts[] = ['tone' => 'slate', 'text' => 'La fecha de censo ya pasó. ¿Se realizó? Cambia la etapa a Censo realizado.'];
            } elseif ($days <= Setting::upcomingCensusDays()) {
                $alerts[] = ['tone' => 'blue', 'text' => 'Censo próximo: '.mb_strtolower(fecha_relativa($o->census_date)).'.'];
            }
        }
        if (in_array($o->stage, PipelineStage::won(), true) && $o->balance() > 0) {
            $alerts[] = ['tone' => 'amber', 'text' => 'Saldo pendiente de '.money($o->balance()).'.'];
        }
        if ($o->stage === PipelineStage::PendingDeposit && (int) $o->payments_count === 0) {
            $alerts[] = ['tone' => 'blue', 'text' => 'Pendiente anticipo sin pago registrado.'];
        }
        if (in_array($o->stage, [PipelineStage::Confirmed, PipelineStage::PendingDeposit], true) && ! $o->census_date) {
            $alerts[] = ['tone' => 'amber', 'text' => 'Falta asignar la fecha confirmada de censo.'];
        }
        if (app(AttentionService::class)->staleQuotesQuery()->where('quotes.opportunity_id', $o->id)->exists()) {
            $alerts[] = ['tone' => 'amber', 'text' => 'Cotización enviada hace '.Setting::quoteFollowupDays().'+ días sin actividad: requiere seguimiento.'];
        }

        return $alerts;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    // ---------------------------------------------------------------- Etapas

    public function changeStage(string $stage, OpportunityService $service): void
    {
        $this->authorize('update', $this->opportunity);
        $target = PipelineStage::from($stage);

        if ($target === PipelineStage::Lost) {
            $this->lostReason = '';
            $this->confirmingLost = true;

            return;
        }

        $service->changeStage($this->opportunity, $target);
        $this->dispatch('notify', message: 'Etapa: '.$target->label());
    }

    public function confirmLost(OpportunityService $service): void
    {
        $this->authorize('update', $this->opportunity);
        $this->validate(['lostReason' => ['nullable', 'string', 'max:255']]);
        $service->changeStage($this->opportunity, PipelineStage::Lost, $this->lostReason);
        $this->confirmingLost = false;
        $this->dispatch('notify', message: 'Oportunidad marcada como perdida');
    }

    public function setCensusDate(): void
    {
        $this->authorize('update', $this->opportunity);
        $this->validate(['newCensusDate' => ['required', 'date']], [], ['newCensusDate' => 'fecha de censo']);
        $this->opportunity->update(['census_date' => $this->newCensusDate]);
        $this->newCensusDate = null;
        $this->dispatch('notify', message: 'Fecha de censo asignada');
    }

    // ---------------------------------------------------------------- Cotizaciones

    public function markQuoteSent(string $id, QuoteService $service): void
    {
        $quote = $this->findQuote($id);
        $this->authorize('update', $quote);
        $service->markSent($quote);
        $this->dispatch('notify', message: "{$quote->number} marcada como enviada");
    }

    public function acceptQuote(string $id, QuoteService $service): void
    {
        $quote = $this->findQuote($id);
        $this->authorize('update', $quote);
        $service->accept($quote);
        $this->dispatch('notify', message: "{$quote->number} es la cotización vigente");
    }

    public function rejectQuote(string $id, QuoteService $service): void
    {
        $quote = $this->findQuote($id);
        $this->authorize('update', $quote);
        $service->reject($quote);
        $this->dispatch('notify', message: "{$quote->number} rechazada");
    }

    public function deleteQuote(string $id, QuoteService $service): void
    {
        $quote = $this->findQuote($id);
        $this->authorize('delete', $quote);

        try {
            $service->delete($quote);
            $this->dispatch('notify', message: "{$quote->number} eliminada");
        } catch (ValidationException $e) {
            $this->dispatch('notify', message: collect($e->errors())->flatten()->first(), type: 'error');
        }
    }

    private function findQuote(string $id): Quote
    {
        return Quote::where('opportunity_id', $this->opportunity->id)->findOrFail($id);
    }

    // ---------------------------------------------------------------- Pagos

    public function deletePayment(string $id, PaymentService $service): void
    {
        $payment = Payment::where('opportunity_id', $this->opportunity->id)->findOrFail($id);
        $this->authorize('delete', $payment);
        $service->delete($payment);
        $this->dispatch('notify', message: 'Pago eliminado');
    }

    // ---------------------------------------------------------------- Comentarios

    public function addComment(): void
    {
        $this->authorize('update', $this->opportunity);
        $this->validate(['comment' => ['required', 'string', 'min:2', 'max:5000']], [], ['comment' => 'comentario']);

        Comment::create(['opportunity_id' => $this->opportunity->id, 'body' => trim($this->comment)]);
        $this->comment = '';
        $this->dispatch('notify', message: 'Comentario agregado');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->opportunity);

        if ($this->opportunity->payments()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar un servicio con pagos. Márcalo como Perdido o elimina los pagos.', type: 'error');

            return;
        }

        $ranchId = $this->opportunity->ranch_id;
        $this->opportunity->delete();
        session()->flash('notify', 'Servicio eliminado');
        $this->redirectRoute('ranches.show', $ranchId, navigate: true);
    }

    public function render()
    {
        $this->opportunity->refresh();
        $o = $this->record;

        return view('livewire.opportunities.show', [
            'o' => $o,
            'stages' => PipelineStage::cases(),
            'quoteStatus' => QuoteStatus::class,
        ])->title($o->ranch->name.' · '.$o->title());
    }
}
