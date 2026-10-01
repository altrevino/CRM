<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected $attributes = [
        'status' => 'borrador',
        'currency' => 'MXN',
        'version' => 1,
        'logistics_amount' => 0,
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
     * Subtotal = servicio + logística.
     * Con IVA: IVA = subtotal × tasa; Total = subtotal + IVA. Sin IVA: IVA = 0.
     */
    public function recalculate(): void
    {
        $service = round((float) $this->service_amount, 2);
        $logistics = round((float) $this->logistics_amount, 2);
        $subtotal = round($service + $logistics, 2);

        if (! $this->apply_vat) {
            $this->vat_rate = 0;
        }

        $vat = $this->apply_vat ? round($subtotal * ((float) $this->vat_rate / 100), 2) : 0.0;

        $this->subtotal = $subtotal;
        $this->vat_amount = $vat;
        $this->total = round($subtotal + $vat, 2);
    }

    public static function formatNumber(int $folio, int $version): string
    {
        return 'COT-'.$folio.($version > 1 ? ' V'.$version : '');
    }

    /** Cálculo para vista previa en formularios (mismas reglas que recalculate()). */
    public static function preview(float $service, float $logistics, bool $applyVat, float $rate): array
    {
        $subtotal = round($service + $logistics, 2);
        $vat = $applyVat ? round($subtotal * $rate / 100, 2) : 0.0;

        return ['subtotal' => $subtotal, 'vat' => $vat, 'total' => round($subtotal + $vat, 2)];
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
