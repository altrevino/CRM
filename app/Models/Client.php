<?php

namespace App\Models;

use App\Enums\PipelineStage;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasCreator;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    use HasCreator, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = ['name', 'phone', 'notes'];

    public function ranches(): HasMany
    {
        return $this->hasMany(Ranch::class)->orderBy('name');
    }

    public function opportunities(): HasManyThrough
    {
        return $this->hasManyThrough(Opportunity::class, Ranch::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at')->latest('id');
    }

    public function whatsappUrl(): ?string
    {
        return Phone::whatsappUrl($this->phone);
    }

    public function formattedPhone(): string
    {
        return Phone::format($this->phone);
    }

    /**
     * Agrega columnas calculadas para listados: ranchos, servicios activos,
     * total vendido, saldo, último contacto y próximo seguimiento.
     */
    public function scopeWithSummary(Builder $query): void
    {
        if (empty($query->getQuery()->columns)) {
            $query->select('clients.*');
        }

        $won = PipelineStage::values(PipelineStage::won());
        $active = PipelineStage::values([...PipelineStage::open(), PipelineStage::Confirmed]);

        $opportunities = fn () => DB::table('opportunities')
            ->join('ranches', 'ranches.id', '=', 'opportunities.ranch_id')
            ->whereColumn('ranches.client_id', 'clients.id')
            ->whereNull('opportunities.deleted_at')
            ->whereNull('ranches.deleted_at');

        $query
            ->withCount('ranches')
            ->addSelect([
                'active_opportunities_count' => $opportunities()
                    ->whereIn('opportunities.stage', $active)
                    ->selectRaw('COUNT(*)'),
                'total_sold' => $opportunities()
                    ->join('quotes', 'quotes.opportunity_id', '=', 'opportunities.id')
                    ->whereIn('opportunities.stage', $won)
                    ->where('quotes.status', 'aceptada')
                    ->whereNull('quotes.deleted_at')
                    ->selectRaw('COALESCE(SUM(quotes.total), 0)'),
                'total_paid_on_sold' => $opportunities()
                    ->join('payments', 'payments.opportunity_id', '=', 'opportunities.id')
                    ->whereIn('opportunities.stage', $won)
                    ->whereNull('payments.deleted_at')
                    ->selectRaw('COALESCE(SUM(payments.amount), 0)'),
                'last_contact_at' => $opportunities()->selectRaw('MAX(opportunities.last_contact_at)'),
                'next_follow_up_at' => Task::query()
                    ->whereColumn('tasks.client_id', 'clients.id')
                    ->where('tasks.status', TaskStatus::Pending->value)
                    ->selectRaw('MIN(tasks.due_date)'),
            ]);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) ($this->total_sold ?? 0) - (float) ($this->total_paid_on_sold ?? 0));
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $digits = preg_replace('/\D+/', '', $term);

        $query->where(function (Builder $q) use ($term, $digits) {
            $q->where('clients.name', 'like', "%{$term}%");
            if (strlen($digits) >= 4) {
                $q->orWhere('clients.phone', 'like', "%{$digits}%");
            }
        });
    }
}
