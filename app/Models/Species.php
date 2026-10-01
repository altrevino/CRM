<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Species extends Model
{
    protected $table = 'species';

    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'opportunity_species');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
