<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'client_id',
        'ranch_id',
        'opportunity_id',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'event' => ActivityEvent::class,
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Sistema']);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function ranch(): BelongsTo
    {
        return $this->belongsTo(Ranch::class)->withTrashed();
    }
}
