<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/** Asigna automáticamente el usuario que crea el registro. */
trait HasCreator
{
    protected static function bootHasCreator(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_by) && Auth::id()) {
                $model->created_by = Auth::id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(['name' => 'Sistema']);
    }
}
