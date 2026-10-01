<?php

namespace App\Models;

use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Enums\ServiceType;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Oportunidad / Servicio: registro central del CRM.
 *
 * @property-read float|null $accepted_total   Total de la cotización aceptada (scopeWithFinancials)
 * @property-read float|null $latest_quote_total Total de la última cotización abierta (scopeWithFinancials)
 * @property-read float      $paid_total       Suma de pagos (scopeWithFinancials)
 * @property-read string|null $next_follow_up_at Fecha de la tarea pendiente más próxima (scopeWithFinancials)
 */
class Opportunity extends Model
{
    use HasCreator, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ranch_id',
        'stage',
        'service_type',
        'quoted_hectares',
        'tentative_census_date',
        'census_date',
        'notes',
        'lost_reason',
        'last_contact_at',
        'owner_id',
    ];

    protected $attributes = [
        'stage' => 'prospecto',
        'currency' => 'MXN',
    ];

    protected function casts(): array
    {
        return [
            'stage' => PipelineStage::class,
            'service_type' => ServiceType::class,
            'quoted_hectares' => 'decimal:2',
            'tentative_census_date' => 'date',
            'census_date' => 'date',
            'last_contact_at' => 'date',
            'stage_changed_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- Relaciones

    public function ranch(): BelongsTo
    {
        return $this->belongsTo(Ranch::class)->withTrashed();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withDefault(['name' => 'Sin asignar']);
    }

    public function species(): BelongsToMany
    {
        return $this->belongsToMany(Species::class, 'opportunity_species')->orderBy('name');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->orderByDesc('folio')->orderByDesc('version');
    }

    public function acceptedQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->where('status', QuoteStatus::Accepted->value);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at')->orderByDesc('created_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('due_date');
    }

    public function pendingTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('status', TaskStatus::Pending->value)->orderBy('due_date');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at')->latest('id');
    }

    // ---------------------------------------------------------------- Scopes

    /** Censos programados: etapa Confirmado con fecha de censo. No existe tabla aparte. */
    public function scopeScheduled(Builder $query): void
    {
        $query->where('opportunities.stage', PipelineStage::Confirmed->value)
            ->whereNotNull('opportunities.census_date');
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('opportunities.stage', PipelineStage::values(PipelineStage::open()));
    }

    public function scopeWon(Builder $query): void
    {
        $query->whereIn('opportunities.stage', PipelineStage::values(PipelineStage::won()));
    }

    /** Agrega totales calculados con subconsultas (sin N+1). */
    public function scopeWithFinancials(Builder $query): void
    {
        if (empty($query->getQuery()->columns)) {
            $query->select('opportunities.*');
        }

        $query->addSelect([
            'accepted_total' => Quote::query()
                ->select('total')
                ->whereColumn('quotes.opportunity_id', 'opportunities.id')
                ->where('status', QuoteStatus::Accepted->value)
                ->limit(1),
            'latest_quote_total' => Quote::query()
                ->select('total')
                ->whereColumn('quotes.opportunity_id', 'opportunities.id')
                ->whereIn('status', [QuoteStatus::Draft->value, QuoteStatus::Sent->value])
                ->orderByDesc('folio')
                ->orderByDesc('version')
                ->limit(1),
            'paid_total' => Payment::query()
                ->selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('payments.opportunity_id', 'opportunities.id'),
            'payments_count' => Payment::query()
                ->selectRaw('COUNT(*)')
                ->whereColumn('payments.opportunity_id', 'opportunities.id'),
            'next_follow_up_at' => Task::query()
                ->selectRaw('MIN(due_date)')
                ->whereColumn('tasks.opportunity_id', 'opportunities.id')
                ->where('status', TaskStatus::Pending->value),
        ]);
    }

    /** Une rancho y cliente para poder buscar y ordenar por sus columnas. */
    public function scopeJoinRanchAndClient(Builder $query): void
    {
        if (empty($query->getQuery()->columns)) {
            $query->select('opportunities.*');
        }

        $query->join('ranches', 'ranches.id', '=', 'opportunities.ranch_id')
            ->join('clients', 'clients.id', '=', 'ranches.client_id');
    }

    // ---------------------------------------------------------------- Valores derivados

    /** Total de referencia: cotización aceptada o, si no existe, la última abierta. */
    public function quotedTotal(): ?float
    {
        $value = $this->accepted_total ?? $this->latest_quote_total ?? null;

        return $value === null ? null : (float) $value;
    }

    public function paidTotal(): float
    {
        return (float) ($this->paid_total ?? $this->payments()->sum('amount'));
    }

    public function acceptedTotal(): ?float
    {
        if (array_key_exists('accepted_total', $this->attributes)) {
            return $this->accepted_total === null ? null : (float) $this->accepted_total;
        }

        $total = $this->acceptedQuote?->total;

        return $total === null ? null : (float) $total;
    }

    /** Saldo = total aceptado − pagos. Null si no hay cotización aceptada. */
    public function balance(): ?float
    {
        $accepted = $this->acceptedTotal();

        return $accepted === null ? null : round(max(0, $accepted - $this->paidTotal()), 2);
    }

    public function paidPercent(): int
    {
        $accepted = $this->acceptedTotal();
        if (! $accepted) {
            return 0;
        }

        return (int) min(100, floor($this->paidTotal() / $accepted * 100));
    }

    public function nextFollowUp(): ?string
    {
        if (array_key_exists('next_follow_up_at', $this->attributes)) {
            return $this->next_follow_up_at;
        }

        return $this->pendingTasks()->min('due_date');
    }

    public function isScheduled(): bool
    {
        return $this->stage === PipelineStage::Confirmed && $this->census_date !== null;
    }

    public function title(): string
    {
        $year = ($this->census_date ?? $this->tentative_census_date)?->year ?? $this->created_at?->year;

        return trim('Censo '.mb_strtolower($this->service_type->label()).' '.$year);
    }

    /** Lista de faltantes para censos programados (información incompleta). @return list<string> */
    public function missingInfo(): array
    {
        $missing = [];
        if (! $this->quoted_hectares) {
            $missing[] = 'hectáreas';
        }
        if ($this->acceptedTotal() === null) {
            $missing[] = 'cotización aceptada';
        }
        if (! $this->ranch?->maps_url) {
            $missing[] = 'ubicación en Maps';
        }
        if (! $this->ranch?->fence_type) {
            $missing[] = 'tipo de cerca';
        }

        return $missing;
    }

    /** Siguiente folio de cotización disponible. */
    public static function nextQuoteFolio(): int
    {
        $max = (int) DB::table('quotes')->max('folio');

        return max($max + 1, (int) Setting::get('quote_folio_start'));
    }
}
