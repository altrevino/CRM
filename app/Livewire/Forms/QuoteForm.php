<?php

namespace App\Livewire\Forms;

use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\QuoteService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Alta, edición y nueva versión de cotizaciones. */
class QuoteForm extends Component
{
    public bool $showModal = false;

    /** create | edit | version */
    #[Locked]
    public string $mode = 'create';

    #[Locked]
    public ?string $opportunityId = null;

    #[Locked]
    public ?string $quoteId = null;

    public ?string $issued_at = null;

    public ?string $hectares = null;

    public ?string $price_per_hectare = null;

    public ?string $service_amount = null;

    public ?string $logistics_amount = '0';

    /** 'si' | 'no' | '' — obligatorio elegir; nunca se asume IVA. */
    public string $apply_vat = '';

    public ?string $vat_rate = null;

    public string $notes = '';

    /** Número manual opcional (migración). Vacío = consecutivo automático. */
    public ?string $folio = null;

    public ?string $version = '1';

    #[On('open-quote-form')]
    public function open(string $opportunityId, ?string $quoteId = null, string $mode = 'create'): void
    {
        $this->resetValidation();
        $this->reset();

        $opportunity = Opportunity::findOrFail($opportunityId);
        $this->opportunityId = $opportunity->id;
        $this->mode = in_array($mode, ['create', 'edit', 'version'], true) ? $mode : 'create';
        $this->vat_rate = (string) (float) Setting::vatRate();
        $this->issued_at = today()->toDateString();

        if ($quoteId) {
            $quote = Quote::where('opportunity_id', $opportunity->id)->findOrFail($quoteId);
            $this->authorize($this->mode === 'edit' ? 'update' : 'create', $this->mode === 'edit' ? $quote : Quote::class);
            $this->quoteId = $quote->id;
            $this->hectares = (string) (float) $quote->hectares;
            $this->service_amount = (string) (float) $quote->service_amount;
            $this->logistics_amount = (string) (float) $quote->logistics_amount;
            $this->apply_vat = $quote->apply_vat ? 'si' : 'no';
            $this->notes = (string) $quote->notes;
            if ($this->mode === 'edit') {
                $this->issued_at = $quote->issued_at->toDateString();
                if ($quote->apply_vat) {
                    $this->vat_rate = (string) (float) $quote->vat_rate;
                }
            }
        } else {
            $this->authorize('create', Quote::class);
            $this->mode = 'create';
            $this->hectares = $opportunity->quoted_hectares !== null ? (string) (float) $opportunity->quoted_hectares : null;
        }

        $this->showModal = true;
    }

    public function updatedPricePerHectare(): void
    {
        if (is_numeric($this->price_per_hectare) && is_numeric($this->hectares)) {
            $this->service_amount = (string) round((float) $this->price_per_hectare * (float) $this->hectares, 2);
        }
    }

    public function updatedHectares(): void
    {
        $this->updatedPricePerHectare();
    }

    #[Computed]
    public function opportunity(): ?Opportunity
    {
        return $this->opportunityId ? Opportunity::with('ranch.client')->find($this->opportunityId) : null;
    }

    #[Computed]
    public function nextNumber(): string
    {
        return Quote::formatNumber(Opportunity::nextQuoteFolio(), 1);
    }

    #[Computed]
    public function baseQuote(): ?Quote
    {
        return $this->quoteId ? Quote::find($this->quoteId) : null;
    }

    #[Computed]
    public function preview(): array
    {
        return Quote::preview(
            (float) $this->service_amount,
            (float) $this->logistics_amount,
            $this->apply_vat === 'si',
            (float) $this->vat_rate,
        );
    }

    protected function rules(): array
    {
        return [
            'issued_at' => ['required', 'date'],
            'hectares' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'service_amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'logistics_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'apply_vat' => ['required', 'in:si,no'],
            'vat_rate' => ['required_if:apply_vat,si', 'nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'folio' => ['nullable', 'integer', 'min:1', 'max:9999999'],
            'version' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    protected function messages(): array
    {
        return [
            'apply_vat.required' => 'Indica si la operación es Con IVA o Sin IVA.',
            'hectares.gt' => 'Las hectáreas deben ser mayores a 0.',
            'vat_rate.required_if' => 'Captura el porcentaje de IVA.',
            'folio.integer' => 'El folio debe ser solo el número, por ejemplo 145 para COT-145.',
        ];
    }

    public function save(QuoteService $service): void
    {
        $data = $this->validate();
        $data['apply_vat'] = $data['apply_vat'] === 'si';
        $data['logistics_amount'] = $data['logistics_amount'] ?: 0;
        if ($this->mode !== 'create') {
            unset($data['folio'], $data['version']);
        }

        $opportunity = Opportunity::findOrFail($this->opportunityId);

        if ($this->mode === 'edit') {
            $quote = Quote::findOrFail($this->quoteId);
            $this->authorize('update', $quote);
            $service->update($quote, $data);
            $message = "{$quote->number} actualizada";
        } elseif ($this->mode === 'version') {
            $this->authorize('create', Quote::class);
            $quote = $service->createVersion(Quote::findOrFail($this->quoteId), $data);
            $message = "{$quote->number} creada";
        } else {
            $this->authorize('create', Quote::class);
            $quote = $service->create($opportunity, $data);
            $message = "{$quote->number} creada";
        }

        $this->showModal = false;
        $this->dispatch('crm:refresh');
        $this->dispatch('notify', message: $message);
    }

    public function render()
    {
        return view('livewire.forms.quote-form');
    }
}
