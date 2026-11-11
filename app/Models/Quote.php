<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasCreator;
use App\Services\Pricing\QuoteCalculator;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Quote extends Model
{
    use HasCreator, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'opportunity_id',
        'folio',
        'version',
        'issued_at',
        'hectares',
        'service_amount',
        'logistics_amount',
        'apply_vat',
        'vat_rate',
        'status',
        'notes',
        'price_list_id',
        'pricing_snapshot',
        'pricing_override_reason',
        'revision_of',
        'duplicated_from',
        'valid_until',
        'tentative_service_date',
        'payment_terms',
        'observations',
        'service_amount_calculated',
        'service_adjust_reason',
        'one_way_km',
        'route_km_calculated',
        'route_km',
        'route_adjust_reason',
        'km_rate',
        'travel_expenses',
        'discount_type',
        'discount_value',
        'discount_scope',
        'discount_base',
        'discount_amount',
        'discount_reason',
    ];

    protected $attributes = [
        'status' => 'borrador',
        'currency' => 'MXN',
        'version' => 1,
        'logistics_amount' => 0,
        'travel_expenses' => 0,
        'discount_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'issued_at' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'apply_vat' => 'boolean',
            'hectares' => 'decimal:2',
            'service_amount' => 'decimal:2',
            'logistics_amount' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'pricing_snapshot' => 'array',
            'emitted_at' => 'datetime',
            'valid_until' => 'date',
            'tentative_service_date' => 'date',
            'service_amount_calculated' => 'decimal:2',
            'service_adjusted_at' => 'datetime',
            'one_way_km' => 'decimal:2',
            'route_km_calculated' => 'decimal:2',
            'route_km' => 'decimal:2',
            'km_rate' => 'decimal:2',
            'travel_expenses' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_base' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Quote $quote) {
            $quote->recalculate();
            $quote->number = self::formatNumber($quote->folio, $quote->version);
            // Candado para el índice único: una sola aceptada por oportunidad.
            $quote->accepted_lock = $quote->status === QuoteStatus::Accepted ? 1 : null;
        });
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Subtotal = censo + traslado + viáticos − descuento; IVA sobre el subtotal.
     * Usa la misma fórmula que QuoteCalculator, en centavos.
     */
    public function recalculate(): void
    {
        if (! $this->apply_vat) {
            $this->vat_rate = 0;
        }

        $totals = QuoteCalculator::totals(
            Money::toCents($this->service_amount),
            Money::toCents($this->logistics_amount),
            Money::toCents($this->travel_expenses),
            Money::toCents($this->discount_amount),
            (bool) $this->apply_vat,
            (string) ($this->vat_rate ?? 0),
        );

        $this->subtotal = Money::decimal($totals['subtotal']);
        $this->vat_amount = Money::decimal($totals['vat']);
        $this->total = Money::decimal($totals['total']);
    }

    public static function formatNumber(int $folio, int $version): string
    {
        return 'COT-'.$folio.($version > 1 ? ' V'.$version : '');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function revisionOf(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'revision_of')->withTrashed();
    }

    public function serviceAdjuster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_adjusted_by');
    }

    /** Versión de tarifa usada (null en cotizaciones anteriores al tarifario). */
    public function pricingVersion(): ?int
    {
        return $this->pricing_snapshot['version'] ?? null;
    }

    public function pricingLabel(): string
    {
        return $this->pricingVersion() ? 'Tarifa v'.$this->pricingVersion() : 'Previa al tarifario';
    }

    /** Frase para avisos: «la Tarifa v1» o «precios previos al tarifario». */
    public function pricingPhrase(): string
    {
        return $this->pricingVersion() ? 'la '.$this->pricingLabel() : 'precios previos al tarifario';
    }

    public function usesCurrentPricing(): bool
    {
        return $this->price_list_id !== null && $this->price_list_id === PriceList::current()->id;
    }

    /** Vencida: emitida o enviada con vigencia ya pasada (no se guarda, se deriva). */
    public function isExpired(): bool
    {
        return $this->status->isOutstanding() && $this->valid_until !== null && $this->valid_until->lt(today());
    }

    public function statusLabel(): string
    {
        return $this->isExpired() ? 'Vencida' : $this->status->label();
    }

    public function statusClasses(): string
    {
        return $this->isExpired() ? 'bg-orange-50 text-orange-700 ring-orange-200' : $this->status->badgeClasses();
    }

    public function hasDiscount(): bool
    {
        return (float) $this->discount_amount > 0;
    }

    public function discountLabel(): string
    {
        return match ($this->discount_scope) {
            'census' => 'Descuento sobre servicio de censo',
            'travel' => 'Descuento sobre traslado',
            default => 'Descuento',
        };
    }

    /** Espectro_Cotizacion_COT-145_V2_Rancho_Las_Lomas.pdf */
    public function pdfFilename(): string
    {
        $ranch = $this->opportunity?->ranch?->name ?? '';
        $clean = fn (string $v) => trim(preg_replace('/[^A-Za-z0-9-]+/', '_', Str::ascii($v)), '_');

        return 'Espectro_Cotizacion_'.$clean($this->number).($ranch ? '_'.$clean($ranch) : '').'.pdf';
    }

    public function vatLabel(): string
    {
        return $this->apply_vat ? 'Con IVA '.rtrim(rtrim(number_format((float) $this->vat_rate, 2), '0'), '.').'%' : 'Sin IVA';
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $number = preg_replace('/^cot-?\s*/i', '', $term);

        $query->where(function (Builder $q) use ($term, $number) {
            $q->where('quotes.number', 'like', "%{$term}%")
                ->orWhere('quotes.number', 'like', "COT-{$number}%")
                ->orWhere('clients.name', 'like', "%{$term}%")
                ->orWhere('ranches.name', 'like', "%{$term}%");
        });
    }
}
