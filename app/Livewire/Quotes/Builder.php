<?php

namespace App\Livewire\Quotes;

use App\Enums\PipelineStage;
use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\MexicanState;
use App\Models\Opportunity;
use App\Models\PriceList;
use App\Models\Quote;
use App\Models\Ranch;
use App\Services\DuplicateDetector;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\QuoteCalculator;
use App\Services\QuoteService;
use App\Support\Phone;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cotización rápida: cliente → rancho → superficie y distancia → desglose → guardar y PDF.
 * Modos: create (nueva), edit (borrador), duplicate (nueva con tarifa vigente) y revision (V2, V3…).
 * El desglose se calcula siempre en el servidor con QuoteCalculator.
 */
#[Title('Cotización')]
class Builder extends Component
{
    #[Locked]
    public string $mode = 'create';

    #[Locked]
    public ?string $quoteId = null;

    #[Locked]
    public ?string $baseQuoteId = null;

    /** Edición de borrador: true cuando el usuario pidió recalcular con la tarifa vigente. */
    #[Locked]
    public bool $useCurrentPricing = false;

    // Cliente / rancho / servicio
    public string $clientMode = 'existing';

    public ?string $client_id = null;

    public string $clientSearch = '';

    public string $client_name = '';

    public string $client_phone = '';

    public string $ranchMode = 'existing';

    public ?string $ranch_id = null;

    public string $ranch_name = '';

    public string $municipality = '';

    public ?string $state_id = null;

    public string $opportunity_id = '';

    public string $service_type = 'completo';

    // Cálculo
    public string $hectares = '';

    public string $one_way_km = '';

    public bool $route_adjusted = false;

    public string $route_km = '';

    public string $route_adjust_reason = '';

    public string $travel_expenses = '';

    public bool $service_adjusted = false;

    public string $service_amount = '';

    public string $service_adjust_reason = '';

    public string $discount_type = '';

    public string $discount_value = '';

    public string $discount_scope = 'subtotal';

    public string $discount_reason = '';

    /** 'si' | 'no' — obligatorio elegir; nunca se asume IVA. */
    public string $apply_vat = '';

    // Datos del documento
    public string $issued_at = '';

    public string $valid_until = '';

    public string $tentative_service_date = '';

    public string $payment_terms = '';

    public string $observations = '';

    public string $notes = '';

    public string $folio = '';

    public string $version = '1';

    public ?string $kmHint = null;

    public function mount(?Quote $quote = null): void
    {
        $service = app(QuoteService::class);
        $current = PriceList::current();
        $this->issued_at = today()->toDateString();
        $this->valid_until = today()->addDays($current->validity_days)->toDateString();
        $this->payment_terms = (string) $current->payment_terms;
        $this->state_id = (string) (MexicanState::where('name', 'Nuevo León')->value('id') ?? '');

        if ($quote?->exists) {
            // Edición de borrador.
            $this->authorize('update', $quote);
            $this->mode = 'edit';
            $this->quoteId = $quote->id;
            $this->fillInputs($service->inputsFrom($quote));
            $this->useSelection($quote->opportunity);

            return;
        }

        $this->authorize('create', Quote::class);

        if ($baseId = request('duplicar') ?: request('revisar')) {
            $base = Quote::with('opportunity.ranch')->findOrFail($baseId);
            $this->authorize('view', $base);
            $this->baseQuoteId = $base->id;
            $this->mode = request('revisar') ? 'revision' : 'duplicate';
            $this->fillInputs($service->inputsFrom($base, withExceptions: $this->mode === 'revision'));
            $this->issued_at = today()->toDateString();
            $this->valid_until = today()->addDays($current->validity_days)->toDateString();
            $this->useSelection($base->opportunity, keepOpportunity: $this->mode === 'revision' || ! in_array($base->opportunity->stage, [PipelineStage::Lost, PipelineStage::CensusDone], true));

            return;
        }

        if ($opportunity = Opportunity::find(request('servicio'))) {
            $this->useSelection($opportunity);
            $this->prefillFromOpportunity($opportunity);
        } elseif ($ranch = Ranch::find(request('rancho'))) {
            $this->client_id = $ranch->client_id;
            $this->selectRanch($ranch->id);
        } elseif ($client = Client::find(request('cliente'))) {
            $this->selectClient($client->id);
        }
    }

    private function fillInputs(array $inputs): void
    {
        foreach ($inputs as $key => $value) {
            if (! property_exists($this, $key)) {
                continue;
            }
            $this->{$key} = match (true) {
                $key === 'apply_vat' => $value ? 'si' : 'no',
                is_bool($this->{$key}) => (bool) $value,
                default => (string) ($value ?? ''),
            };
        }
    }

    private function useSelection(Opportunity $opportunity, bool $keepOpportunity = true): void
    {
        $this->client_id = $opportunity->ranch->client_id;
        $this->ranch_id = $opportunity->ranch_id;
        $this->clientMode = 'existing';
        $this->ranchMode = 'existing';
        $this->opportunity_id = $keepOpportunity ? $opportunity->id : '';
        $this->service_type = $opportunity->service_type->value;
    }

    private function prefillFromOpportunity(Opportunity $opportunity): void
    {
        if ($this->hectares === '' && $opportunity->quoted_hectares) {
            $this->hectares = (string) (float) $opportunity->quoted_hectares;
        }
        if ($this->tentative_service_date === '' && ($date = $opportunity->census_date ?? $opportunity->tentative_census_date)) {
            $this->tentative_service_date = $date->toDateString();
        }
        $this->prefillFromRanch($opportunity->ranch);
    }

    private function prefillFromRanch(Ranch $ranch): void
    {
        if ($this->hectares === '' && $ranch->total_hectares) {
            $this->hectares = (string) (float) $ranch->total_hectares;
        }
        if ($this->one_way_km === '' && $ranch->km_round_trip) {
            $this->one_way_km = (string) round((float) $ranch->km_round_trip / 2, 2);
            $this->kmHint = 'Tomado del rancho: '.number_format((float) $ranch->km_round_trip, 0).' km de viaje redondo ÷ 2.';
        }
    }

    // ------------------------------------------------------------ Selección

    public function selectClient(string $id): void
    {
        $this->client_id = Client::whereKey($id)->value('id');
        $this->clientMode = 'existing';
        $this->clientSearch = '';
        $this->ranch_id = null;
        $this->opportunity_id = '';
        $this->ranchMode = Ranch::where('client_id', $this->client_id)->exists() ? 'existing' : 'new';
        if ($this->ranchMode === 'existing' && Ranch::where('client_id', $this->client_id)->count() === 1) {
            $this->selectRanch(Ranch::where('client_id', $this->client_id)->value('id'));
        }
    }

    public function newClient(): void
    {
        $this->clientMode = 'new';
        $this->client_id = null;
        $this->ranch_id = null;
        $this->ranchMode = 'new';
        $this->opportunity_id = '';
        if ($this->clientSearch && ! preg_match('/\d{4,}/', $this->clientSearch)) {
            $this->client_name = $this->clientSearch;
        }
    }

    public function clearClient(): void
    {
        $this->client_id = null;
        $this->ranch_id = null;
        $this->opportunity_id = '';
    }

    public function selectRanch(string $id): void
    {
        $ranch = Ranch::where('client_id', $this->client_id)->findOrFail($id);
        $this->ranch_id = $ranch->id;
        $this->ranchMode = 'existing';

        $open = $this->ranchOpportunities($ranch->id);
        $this->opportunity_id = $open->count() === 1 ? $open->first()->id : '';
        if ($this->opportunity_id) {
            $this->prefillFromOpportunity($open->first());
        } else {
            $this->prefillFromRanch($ranch);
        }
    }

    public function newRanch(): void
    {
        $this->ranchMode = 'new';
        $this->ranch_id = null;
        $this->opportunity_id = '';
    }

    public function updatedOpportunityId(): void
    {
        if ($this->opportunity_id && $opportunity = Opportunity::find($this->opportunity_id)) {
            $this->prefillFromOpportunity($opportunity);
        }
    }

    /** Recalcula el borrador con la tarifa vigente (se guarda al guardar). */
    public function updatePricing(): void
    {
        $this->useCurrentPricing = true;
        $this->payment_terms = (string) PriceList::current()->payment_terms ?: $this->payment_terms;
        $this->dispatch('notify', message: 'Precios recalculados con la '.PriceList::current()->label().'. Guarda para conservar el cambio.');
    }

    private function ranchOpportunities(?string $ranchId)
    {
        return $ranchId
            ? Opportunity::where('ranch_id', $ranchId)
                ->whereNotIn('stage', [PipelineStage::Lost->value, PipelineStage::CensusDone->value])
                ->latest()->get()
            : collect();
    }

    #[Computed]
    public function client(): ?Client
    {
        return $this->client_id ? Client::find($this->client_id) : null;
    }

    #[Computed]
    public function clientResults()
    {
        return mb_strlen(trim($this->clientSearch)) < 2
            ? collect()
            : Client::search($this->clientSearch)->withCount('ranches')->orderBy('name')->limit(8)->get();
    }

    #[Computed]
    public function clientDuplicates()
    {
        return $this->clientMode === 'new'
            ? app(DuplicateDetector::class)->similarClients($this->client_name, $this->client_phone)
            : collect();
    }

    #[Computed]
    public function clientRanches()
    {
        return $this->client_id ? Ranch::where('client_id', $this->client_id)->with('state')->orderBy('name')->get() : collect();
    }

    #[Computed]
    public function opportunities()
    {
        return $this->ranchOpportunities($this->ranch_id);
    }

    #[Computed]
    public function editing(): ?Quote
    {
        return $this->quoteId ? Quote::find($this->quoteId) : null;
    }

    #[Computed]
    public function baseQuote(): ?Quote
    {
        return $this->baseQuoteId ? Quote::find($this->baseQuoteId) : null;
    }

    // ------------------------------------------------------------ Cálculo

    /** Tarifa con la que se calcula: la del borrador (si no se actualizó) o la vigente. */
    #[Computed]
    public function tariff(): array
    {
        $editing = $this->editing;
        if ($editing && $editing->pricing_snapshot && ! $this->useCurrentPricing) {
            return $editing->pricing_snapshot;
        }

        return PriceList::current()->snapshot();
    }

    #[Computed]
    public function isOutdatedPricing(): bool
    {
        return (int) ($this->tariff['version'] ?? 0) !== PriceList::current()->version;
    }

    /** @return array{result: ?array, error: ?array{field: string, message: string}} */
    #[Computed]
    public function breakdown(): array
    {
        if (! is_numeric($this->hectares)) {
            return ['result' => null, 'error' => null];
        }

        try {
            return ['result' => QuoteCalculator::calculate($this->tariff, $this->pricingInputs()), 'error' => null];
        } catch (PricingException $e) {
            return ['result' => null, 'error' => ['field' => $e->field, 'message' => $e->getMessage()]];
        }
    }

    private function pricingInputs(): array
    {
        return [
            'hectares' => $this->hectares,
            'one_way_km' => $this->one_way_km === '' ? '0' : $this->one_way_km,
            'route_adjusted' => $this->route_adjusted,
            'route_km' => $this->route_km,
            'route_adjust_reason' => $this->route_adjust_reason,
            'travel_expenses' => $this->travel_expenses === '' ? '0' : $this->travel_expenses,
            'service_adjusted' => $this->service_adjusted,
            'service_amount' => $this->service_amount,
            'service_adjust_reason' => $this->service_adjust_reason,
            'discount_type' => $this->discount_type ?: null,
            'discount_value' => $this->discount_value,
            'discount_scope' => $this->discount_scope,
            'discount_reason' => $this->discount_reason,
            'apply_vat' => $this->apply_vat === 'si',
        ];
    }

    // ------------------------------------------------------------ Guardar

    protected function rules(): array
    {
        $selecting = in_array($this->mode, ['create', 'duplicate'], true);
        $newClient = $selecting && $this->clientMode === 'new';
        $newRanch = $selecting && ($newClient || $this->ranchMode === 'new');

        return [
            'client_id' => [Rule::requiredIf($selecting && ! $newClient), 'nullable', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'client_name' => [Rule::requiredIf($newClient), 'nullable', 'string', 'min:3', 'max:150'],
            'client_phone' => [Rule::requiredIf($newClient), 'nullable', 'string', 'max:25', function (string $attribute, mixed $value, Closure $fail) use ($newClient) {
                if ($newClient && ! Phone::isValid($value)) {
                    $fail('El teléfono no es válido. Captura 10 dígitos (ej. 81 1234 5678).');
                }
            }],
            'ranch_id' => [Rule::requiredIf($selecting && ! $newRanch), 'nullable', Rule::exists('ranches', 'id')->where('client_id', $this->client_id)->whereNull('deleted_at')],
            'ranch_name' => [Rule::requiredIf($newRanch), 'nullable', 'string', 'min:2', 'max:150'],
            'municipality' => [Rule::requiredIf($newRanch), 'nullable', 'string', 'max:100'],
            'state_id' => [Rule::requiredIf($newRanch), 'nullable', 'exists:mexican_states,id'],
            'opportunity_id' => ['nullable', Rule::exists('opportunities', 'id')->where('ranch_id', $this->ranch_id)->whereNull('deleted_at')],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'hectares' => ['required', 'numeric', 'gt:0', 'max:10000000', 'regex:/^\d+(\.\d{1,2})?$/'],
            'one_way_km' => ['nullable', 'numeric', 'min:0', 'max:100000', 'regex:/^\d+(\.\d{1,2})?$/'],
            'route_km' => ['nullable', 'numeric', 'min:0', 'max:200000'],
            'route_adjust_reason' => ['nullable', 'string', 'max:500'],
            'travel_expenses' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'service_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'service_adjust_reason' => ['nullable', 'string', 'max:500'],
            'discount_type' => ['nullable', Rule::in(array_keys(QuoteCalculator::DISCOUNT_TYPES))],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_scope' => ['nullable', Rule::in(array_keys(QuoteCalculator::DISCOUNT_SCOPES))],
            'discount_reason' => ['nullable', 'string', 'max:500'],
            'apply_vat' => ['required', 'in:si,no'],
            'issued_at' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issued_at'],
            'tentative_service_date' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:3000'],
            'observations' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'folio' => ['nullable', 'integer', 'min:1', 'max:9999999'],
            'version' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    protected function messages(): array
    {
        return [
            'apply_vat.required' => 'Indica si la operación es Con IVA o Sin IVA.',
            'hectares.gt' => 'La superficie debe ser mayor a 0 hectáreas.',
            'hectares.regex' => 'Usa máximo dos decimales en la superficie.',
            'one_way_km.min' => 'La distancia no puede ser negativa.',
            'one_way_km.regex' => 'Usa máximo dos decimales en la distancia.',
            'client_id.required' => 'Busca y selecciona un cliente, o crea uno nuevo.',
            'ranch_id.required' => 'Selecciona un rancho del cliente o crea uno nuevo.',
            'valid_until.after_or_equal' => 'La vigencia debe ser igual o posterior a la fecha de emisión.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'client_name' => 'nombre del cliente', 'client_phone' => 'teléfono', 'ranch_name' => 'nombre del rancho',
            'one_way_km' => 'distancia de ida', 'route_km' => 'kilómetros totales', 'travel_expenses' => 'viáticos',
            'service_amount' => 'precio ajustado', 'discount_value' => 'descuento', 'valid_until' => 'vigencia',
            'tentative_service_date' => 'fecha tentativa', 'observations' => 'observaciones', 'issued_at' => 'fecha de emisión',
        ];
    }

    public function save(bool $download = false): void
    {
        $this->validate();
        $service = app(QuoteService::class);
        $data = [...$this->pricingInputs(), ...$this->documentData()];

        $quote = DB::transaction(function () use ($service, $data) {
            if ($this->mode === 'edit') {
                $quote = Quote::findOrFail($this->quoteId);
                $this->authorize('update', $quote);

                return $service->updateDraft($quote, $data, $this->useCurrentPricing);
            }

            $this->authorize('create', Quote::class);

            if ($this->mode === 'revision') {
                $base = Quote::findOrFail($this->baseQuoteId);

                return $service->createRevision($base, $data);
            }

            $opportunity = $this->resolveOpportunity();
            if (! $opportunity->tentative_census_date && $this->tentative_service_date) {
                $opportunity->update(['tentative_census_date' => $this->tentative_service_date]);
            }

            return $service->create($opportunity, [...$data, 'folio' => $this->folio, 'version' => $this->version], $this->mode === 'duplicate' ? $this->baseQuoteId : null);
        });

        session()->flash('notify', "{$quote->number} guardada");
        if ($download) {
            session()->flash('download_pdf', true);
        }
        $this->redirectRoute('quotes.show', $quote, navigate: true);
    }

    private function documentData(): array
    {
        return [
            'issued_at' => $this->issued_at,
            'valid_until' => $this->valid_until,
            'tentative_service_date' => $this->tentative_service_date ?: null,
            'payment_terms' => $this->payment_terms,
            'observations' => $this->observations,
            'notes' => $this->notes,
        ];
    }

    /** Cliente, rancho y servicio existentes o nuevos (sin duplicar entidades). */
    private function resolveOpportunity(): Opportunity
    {
        $clientId = $this->client_id;
        if ($this->clientMode === 'new') {
            $clientId = Client::create([
                'name' => trim(preg_replace('/\s+/', ' ', $this->client_name)),
                'phone' => Phone::normalize($this->client_phone),
            ])->id;
        }

        $ranchId = $this->ranch_id;
        if ($this->clientMode === 'new' || $this->ranchMode === 'new') {
            $ranch = new Ranch([
                'name' => trim($this->ranch_name),
                'municipality' => trim($this->municipality),
                'state_id' => $this->state_id,
                'total_hectares' => $this->hectares,
                'km_round_trip' => is_numeric($this->one_way_km) ? (float) $this->one_way_km * 2 : null,
            ]);
            $ranch->client_id = $clientId;
            $ranch->save();
            $ranchId = $ranch->id;
        }

        if ($this->opportunity_id && $this->ranchMode === 'existing' && $this->clientMode === 'existing') {
            return Opportunity::where('ranch_id', $ranchId)->findOrFail($this->opportunity_id);
        }

        return Opportunity::create([
            'ranch_id' => $ranchId,
            'service_type' => $this->service_type,
            'quoted_hectares' => $this->hectares,
            'tentative_census_date' => $this->tentative_service_date ?: null,
            'owner_id' => Auth::id(),
            'last_contact_at' => today(),
        ]);
    }

    public function render()
    {
        $title = match ($this->mode) {
            'edit' => 'Editar '.($this->editing?->number ?? 'cotización'),
            'revision' => 'Revisión de '.($this->baseQuote?->number ?? 'cotización'),
            'duplicate' => 'Duplicar '.($this->baseQuote?->number ?? 'cotización'),
            default => 'Nueva cotización',
        };

        return view('livewire.quotes.builder', [
            'title' => $title,
            'states' => MexicanState::options(),
            'serviceTypes' => ServiceType::options(),
            'discountTypes' => QuoteCalculator::DISCOUNT_TYPES,
            'discountScopes' => QuoteCalculator::DISCOUNT_SCOPES,
            'currentVersion' => PriceList::current()->version,
            'nextNumber' => Quote::formatNumber(Opportunity::nextQuoteFolio(), 1),
        ])->title($title);
    }
}
