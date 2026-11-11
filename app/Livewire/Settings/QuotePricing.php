<?php

namespace App\Livewire\Settings;

use App\Enums\ActivityEvent;
use App\Models\PriceList;
use App\Services\ActivityLogger;
use App\Services\Pricing\QuoteCalculator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Configuración → Cotizaciones. Cada guardado crea una versión nueva del tarifario;
 * las cotizaciones ya creadas conservan la versión con la que se calcularon.
 */
#[Title('Tarifas de cotización')]
class QuotePricing extends Component
{
    public string $minimum_hectares = '';

    public string $minimum_price = '';

    /** @var list<array{up_to: ?string, price: string}> */
    public array $blocks = [];

    public string $km_rate = '';

    public string $vat_rate = '';

    public string $validity_days = '';

    public string $payment_terms = '';

    public string $service_scope = '';

    public string $separate_charges = '';

    public array $business = ['name' => '', 'email' => '', 'whatsapp' => '', 'website' => '', 'address' => ''];

    public string $change_note = '';

    public function mount(): void
    {
        $this->authorize('manage-settings');
        $this->loadFrom(PriceList::current());
    }

    private function loadFrom(PriceList $list): void
    {
        $this->minimum_hectares = $this->plain($list->minimum_hectares);
        $this->minimum_price = $this->plain($list->minimum_price);
        $this->blocks = collect($list->blocks)->map(fn ($b) => [
            'up_to' => $b['up_to'] === null || $b['up_to'] === '' ? null : $this->plain($b['up_to']),
            'price' => $this->plain($b['price']),
        ])->all();
        $this->km_rate = $this->plain($list->km_rate);
        $this->vat_rate = $this->plain($list->vat_rate);
        $this->validity_days = (string) $list->validity_days;
        $this->payment_terms = (string) $list->payment_terms;
        $this->service_scope = (string) $list->service_scope;
        $this->separate_charges = (string) $list->separate_charges;
        $this->business = array_merge($this->business, array_map(fn ($v) => (string) $v, (array) $list->business));
        $this->change_note = '';
    }

    private function plain(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    #[Computed]
    public function current(): PriceList
    {
        return PriceList::current();
    }

    #[Computed]
    public function versions()
    {
        return PriceList::with('creator')->latest('version')->limit(15)->get();
    }

    /** Vista previa con lo capturado (antes de guardar). */
    #[Computed]
    public function preview(): ?array
    {
        if ($this->blockErrors() !== [] || ! is_numeric($this->minimum_hectares) || ! is_numeric($this->minimum_price)) {
            return null;
        }

        $snapshot = $this->snapshotFromForm();

        return collect([250, 500, 500.5, 1000, 1500, 2000, 3000, 4000, 5000, 6000])
            ->mapWithKeys(fn ($ha) => [(string) $ha => QuoteCalculator::censusCents($snapshot, $ha) / 100])
            ->all();
    }

    public function addBlock(): void
    {
        $last = array_pop($this->blocks);
        $previousTo = collect($this->blocks)->last()['up_to'] ?? $this->minimum_hectares;
        $this->blocks[] = ['up_to' => (string) ((float) $previousTo + 1000), 'price' => $last['price'] ?? ''];
        $this->blocks[] = ['up_to' => null, 'price' => $last['price'] ?? ''];
    }

    public function removeBlock(int $index): void
    {
        if (count($this->blocks) <= 1 || $index === array_key_last($this->blocks)) {
            return;
        }
        array_splice($this->blocks, $index, 1);
    }

    protected function rules(): array
    {
        return [
            'minimum_hectares' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'minimum_price' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'blocks' => ['required', 'array', 'min:1', 'max:30'],
            'blocks.*.price' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'km_rate' => ['required', 'numeric', 'min:0', 'max:100000'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'payment_terms' => ['nullable', 'string', 'max:3000'],
            'service_scope' => ['nullable', 'string', 'max:3000'],
            'separate_charges' => ['nullable', 'string', 'max:2000'],
            'business.name' => ['required', 'string', 'max:150'],
            'business.email' => ['nullable', 'email', 'max:150'],
            'business.whatsapp' => ['nullable', 'string', 'max:40'],
            'business.website' => ['nullable', 'string', 'max:150'],
            'business.address' => ['nullable', 'string', 'max:300'],
            'change_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'minimum_hectares' => 'superficie cubierta por la tarifa mínima',
            'minimum_price' => 'tarifa mínima',
            'blocks.*.price' => 'precio por hectárea',
            'km_rate' => 'tarifa por kilómetro',
            'vat_rate' => 'IVA',
            'validity_days' => 'vigencia',
            'business.name' => 'nombre comercial',
            'business.email' => 'correo',
        ];
    }

    /**
     * Bloques continuos: cada bloque empieza donde termina el anterior (el primero en la
     * superficie mínima), los límites crecen y solo el último queda abierto.
     *
     * @return array<string, string>
     */
    private function blockErrors(): array
    {
        $errors = [];
        $previous = is_numeric($this->minimum_hectares) ? (float) $this->minimum_hectares : null;
        $last = array_key_last($this->blocks);

        foreach ($this->blocks as $i => $block) {
            $upTo = $block['up_to'] ?? null;
            if ($i === $last) {
                if ($upTo !== null && $upTo !== '') {
                    $errors["blocks.$i.up_to"] = 'El último bloque debe quedar abierto ("en adelante").';
                }

                continue;
            }
            if (! is_numeric($upTo)) {
                $errors["blocks.$i.up_to"] = 'Captura el límite superior del bloque.';
            } elseif ($previous !== null && (float) $upTo <= $previous) {
                $errors["blocks.$i.up_to"] = 'Debe ser mayor a '.hectareas($previous).' (inicio del bloque). Así no hay huecos ni traslapes.';
            } else {
                $previous = (float) $upTo;
            }
        }

        return $errors;
    }

    private function snapshotFromForm(): array
    {
        return [
            'minimum_hectares' => $this->minimum_hectares,
            'minimum_price' => $this->minimum_price,
            'blocks' => collect($this->blocks)->map(fn ($b) => [
                'up_to' => ($b['up_to'] === null || $b['up_to'] === '') ? null : (string) $b['up_to'],
                'price' => (string) $b['price'],
            ])->values()->all(),
        ];
    }

    public function save(): void
    {
        $this->authorize('manage-settings');
        $data = $this->validate();

        if ($errors = $this->blockErrors()) {
            foreach ($errors as $key => $message) {
                $this->addError($key, $message);
            }

            return;
        }

        $list = DB::transaction(function () use ($data) {
            $previous = PriceList::where('is_current', true)->lockForUpdate()->get();
            PriceList::whereIn('id', $previous->pluck('id'))->toBase()->update(['is_current' => false]);

            return PriceList::create([
                'version' => (int) PriceList::max('version') + 1,
                'is_current' => true,
                'activated_at' => now(),
                'minimum_hectares' => $data['minimum_hectares'],
                'minimum_price' => $data['minimum_price'],
                'blocks' => $this->snapshotFromForm()['blocks'],
                'km_rate' => $data['km_rate'],
                'vat_rate' => $data['vat_rate'],
                'validity_days' => (int) $data['validity_days'],
                'payment_terms' => $data['payment_terms'] ?: null,
                'service_scope' => $data['service_scope'] ?: null,
                'separate_charges' => $data['separate_charges'] ?: null,
                'business' => array_map(fn ($v) => trim((string) $v), $data['business']),
                'change_note' => $data['change_note'] ?: null,
            ]);
        });

        ActivityLogger::log(ActivityEvent::SettingsUpdated, null, "Tarifa de cotizaciones actualizada a {$list->label()}".($list->change_note ? ": {$list->change_note}" : ''));
        unset($this->current, $this->versions);
        $this->change_note = '';
        $this->dispatch('notify', message: "{$list->label()} guardada y vigente para cotizaciones nuevas");
    }

    public function render()
    {
        return view('livewire.settings.quote-pricing');
    }
}
