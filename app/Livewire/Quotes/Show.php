<?php

namespace App\Livewire\Quotes;

use App\Enums\QuoteStatus;
use App\Models\ActivityLog;
use App\Models\PriceList;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Ficha de una cotización: desglose, notas internas, versión de tarifa, PDF y acciones de estado. */
class Show extends Component
{
    #[Locked]
    public string $quoteId;

    public bool $showEmit = false;

    /** Acción que sigue a la emisión cuando se marca enviada/aceptada un borrador con tarifa no vigente. */
    #[Locked]
    public string $then = '';

    public string $overrideReason = '';

    /** Descargar el PDF automáticamente al llegar desde «Guardar y descargar». */
    public bool $autoDownload = false;

    public function mount(Quote $quote): void
    {
        $this->authorize('view', $quote);
        $this->quoteId = $quote->id;
        $this->autoDownload = (bool) session('download_pdf');

        // Desde la ficha del servicio: ?accion=enviar|aceptar abre la confirmación con motivo.
        $action = ['enviar' => 'sent', 'aceptar' => 'accept'][request('accion')] ?? null;
        if ($action && $quote->status === QuoteStatus::Draft && ! $quote->usesCurrentPricing() && Gate::allows('update', $quote)) {
            $this->openEmit($action);
        }
    }

    #[Computed]
    public function quote(): Quote
    {
        return Quote::with(['opportunity.ranch.client', 'opportunity.ranch.state', 'priceList', 'revisionOf', 'serviceAdjuster', 'creator'])
            ->findOrFail($this->quoteId);
    }

    #[Computed]
    public function family()
    {
        return Quote::with('priceList')
            ->where('opportunity_id', $this->quote->opportunity_id)
            ->where('folio', $this->quote->folio)
            ->orderByDesc('version')
            ->get();
    }

    #[Computed]
    public function activity()
    {
        return ActivityLog::with(['user', 'subject'])
            ->where('subject_type', $this->quote->getMorphClass())
            ->where('subject_id', $this->quote->id)
            ->latest('id')
            ->limit(30)
            ->get();
    }

    #[On('crm:refresh')]
    public function refreshQuote(): void
    {
        unset($this->quote, $this->family, $this->activity);
    }

    public function refreshPricing(QuoteService $service): void
    {
        $this->authorize('update', $this->quote);
        $this->run(fn () => $service->refreshPricing($this->quote), 'Precios actualizados a '.PriceList::current()->label());
    }

    public function openEmit(string $then = ''): void
    {
        $this->authorize('update', $this->quote);
        $this->then = in_array($then, ['sent', 'accept'], true) ? $then : '';
        $this->overrideReason = '';
        $this->resetValidation();
        $this->showEmit = true;
    }

    public function emit(QuoteService $service): void
    {
        $this->authorize('update', $this->quote);
        try {
            $service->emit($this->quote, $this->overrideReason);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }
        $this->showEmit = false;
        $then = $this->then;
        $this->then = '';
        $this->refreshQuote();

        match ($then) {
            'sent' => $this->run(fn () => $service->markSent($this->quote), "{$this->quote->number} emitida y marcada como enviada"),
            'accept' => $this->run(fn () => $service->accept($this->quote), "{$this->quote->number} emitida y aceptada"),
            default => $this->dispatch('notify', message: "{$this->quote->number} emitida"),
        };
    }

    /** Un borrador con tarifa no vigente se emite primero con motivo (en el modal). */
    private function needsOverride(): bool
    {
        return $this->quote->status === QuoteStatus::Draft && ! $this->quote->usesCurrentPricing();
    }

    public function markSent(QuoteService $service): void
    {
        $this->authorize('adjust', $this->quote);
        if ($this->needsOverride()) {
            $this->openEmit('sent');

            return;
        }
        $this->run(fn () => $service->markSent($this->quote), 'Marcada como enviada');
    }

    public function accept(QuoteService $service): void
    {
        $this->authorize('adjust', $this->quote);
        if ($this->needsOverride()) {
            $this->openEmit('accept');

            return;
        }
        $this->run(fn () => $service->accept($this->quote), 'Cotización aceptada');
    }

    public function reject(QuoteService $service): void
    {
        $this->authorize('adjust', $this->quote);
        $this->run(fn () => $service->reject($this->quote), 'Cotización rechazada');
    }

    public function delete(QuoteService $service)
    {
        $quote = $this->quote;
        $this->authorize('delete', $quote);
        try {
            $service->delete($quote);
        } catch (ValidationException $e) {
            $this->dispatch('notify', message: collect($e->errors())->flatten()->first(), type: 'error');

            return null;
        }
        session()->flash('notify', "{$quote->number} eliminada");

        return $this->redirectRoute('opportunities.show', $quote->opportunity_id, navigate: true);
    }

    private function run(callable $action, string $message): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            $this->dispatch('notify', message: collect($e->errors())->flatten()->first(), type: 'error');

            return;
        }
        $this->refreshQuote();
        $this->dispatch('notify', message: $message);
    }

    public function render()
    {
        return view('livewire.quotes.show', [
            'currentPriceList' => PriceList::current(),
        ])->title($this->quote->number);
    }
}
