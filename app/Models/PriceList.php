<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Services\Pricing\QuoteCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Versión del tarifario de cotizaciones. Las versiones son inmutables: guardar la
 * configuración crea una versión nueva y la marca como vigente.
 */
class PriceList extends Model
{
    use HasCreator;

    protected $fillable = [
        'version', 'is_current', 'activated_at', 'minimum_hectares', 'minimum_price', 'blocks', 'km_rate',
        'vat_rate', 'validity_days', 'payment_terms', 'service_scope', 'separate_charges', 'business', 'change_note',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'activated_at' => 'datetime',
            'blocks' => 'array',
            'business' => 'array',
            'minimum_hectares' => 'decimal:2',
            'minimum_price' => 'decimal:2',
            'km_rate' => 'decimal:2',
            'vat_rate' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Las versiones de tarifa no se modifican; se crea una versión nueva.'));
        static::saved(fn () => Cache::forget('price_list.current'));
    }

    public static function current(): self
    {
        $id = Cache::remember('price_list.current', 3600, fn () => static::where('is_current', true)->value('id'));
        $current = $id ? static::find($id) : null;

        if (! $current || ! $current->is_current) {
            Cache::forget('price_list.current');
            $current = static::where('is_current', true)->latest('version')->firstOrFail();
        }

        return $current;
    }

    public function label(): string
    {
        return 'Tarifa v'.$this->version;
    }

    /** Copia completa que se guarda en cada cotización. */
    public function snapshot(): array
    {
        return [
            'price_list_id' => $this->id,
            'version' => $this->version,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'minimum_hectares' => (string) $this->minimum_hectares,
            'minimum_price' => (string) $this->minimum_price,
            'blocks' => array_values($this->blocks),
            'km_rate' => (string) $this->km_rate,
            'vat_rate' => (string) $this->vat_rate,
            'validity_days' => (int) $this->validity_days,
            'payment_terms' => $this->payment_terms,
            'service_scope' => $this->service_scope,
            'separate_charges' => $this->separate_charges,
            'business' => $this->business,
        ];
    }

    /** Filas legibles de la tabla de bloques: desde, hasta, precio. */
    public function blockRows(): array
    {
        return self::rowsFromSnapshot($this->snapshot());
    }

    public static function rowsFromSnapshot(array $snapshot): array
    {
        $from = (float) $snapshot['minimum_hectares'];
        $rows = [];
        foreach ($snapshot['blocks'] as $block) {
            $to = $block['up_to'] === null || $block['up_to'] === '' ? null : (float) $block['up_to'];
            $rows[] = ['from' => $from, 'to' => $to, 'price' => (float) $block['price']];
            $from = $to;
        }

        return $rows;
    }

    /** Precio del censo para superficies de referencia (vista previa en Configuración). */
    public function referencePrices(array $areas = [500, 1000, 1500, 2000, 3000, 4000, 5000, 6000]): array
    {
        return collect($areas)->mapWithKeys(fn ($ha) => [$ha => QuoteCalculator::censusCents($this->snapshot(), $ha) / 100])->all();
    }
}
