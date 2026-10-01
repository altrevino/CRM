<?php

namespace App\Livewire\Forms;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\QuoteService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Ajuste de número y fechas históricas de una cotización (migración desde otro sistema). */
class QuoteAdjustForm extends Component
{
    public bool $showModal = false;

    #[Locked]
    public ?string $quoteId = null;

    public ?string $folio = null;

    public ?string $version = null;

    public ?string $sent_at = null;

    public ?string $accepted_at = null;

    #[On('open-quote-adjust')]
    public function open(string $quoteId): void
    {
        $this->resetValidation();
        $quote = Quote::findOrFail($quoteId);
        $this->authorize('adjust', $quote);

        $this->quoteId = $quote->id;
        $this->folio = (string) $quote->folio;
        $this->version = (string) $quote->version;
        $this->sent_at = $quote->sent_at?->toDateString();
        $this->accepted_at = $quote->accepted_at?->toDateString();
        $this->showModal = true;
    }

    #[Computed]
    public function quote(): ?Quote
    {
        return $this->quoteId ? Quote::with('opportunity.ranch')->find($this->quoteId) : null;
    }

    protected function rules(): array
    {
        return [
            'folio' => ['required', 'integer', 'min:1', 'max:9999999'],
            'version' => ['required', 'integer', 'min:1', 'max:99'],
            'sent_at' => ['nullable', 'date'],
            'accepted_at' => ['nullable', 'date'],
        ];
    }

    protected function messages(): array
    {
        return ['folio.integer' => 'El folio debe ser solo el número, por ejemplo 145 para COT-145.'];
    }

    protected function validationAttributes(): array
    {
        return ['folio' => 'folio', 'version' => 'versión', 'sent_at' => 'fecha de envío', 'accepted_at' => 'fecha de aceptación'];
    }

    public function save(QuoteService $service): void
    {
        $data = $this->validate();
        $quote = Quote::findOrFail($this->quoteId);
        $this->authorize('adjust', $quote);

        if ($quote->status !== QuoteStatus::Accepted) {
            unset($data['accepted_at']);
        }

        $service->adjust($quote, $data);

        $this->showModal = false;
        $this->dispatch('crm:refresh');
        $this->dispatch('notify', message: $quote->fresh()->number.' actualizada');
    }

    public function render()
    {
        return view('livewire.forms.quote-adjust-form');
    }
}
