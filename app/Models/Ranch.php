<?php

namespace App\Models;

use App\Enums\FenceType;
use App\Enums\PipelineStage;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ranch extends Model
{
    use HasCreator, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'municipality',
        'state_id',
        'maps_url',
        'km_round_trip',
        'fence_type',
        'total_hectares',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fence_type' => FenceType::class,
            'total_hectares' => 'decimal:2',
            'km_round_trip' => 'decimal:1',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(MexicanState::class, 'state_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at')->latest('id');
    }

    public function location(): string
    {
        return collect([$this->municipality, $this->state?->name])->filter()->implode(', ');
    }

    public function scopeWithSummary(Builder $query): void
    {
        if (empty($query->getQuery()->columns)) {
            $query->select('ranches.*');
        }

        $query
            ->withCount('opportunities')
            ->addSelect([
                'last_census_date' => Opportunity::query()
                    ->whereColumn('opportunities.ranch_id', 'ranches.id')
                    ->where('stage', PipelineStage::CensusDone->value)
                    ->selectRaw('MAX(census_date)'),
                'next_census_date' => Opportunity::query()
                    ->whereColumn('opportunities.ranch_id', 'ranches.id')
                    ->where('stage', PipelineStage::Confirmed->value)
                    ->whereDate('census_date', '>=', today())
                    ->selectRaw('MIN(census_date)'),
            ]);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            $q->where('ranches.name', 'like', "%{$term}%")
                ->orWhere('ranches.municipality', 'like', "%{$term}%");
        });
    }
}
