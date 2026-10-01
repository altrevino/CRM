<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/** Comentario manual. Inmutable: no se edita ni se elimina. */
class Comment extends Model
{
    use HasUlids;

    protected $fillable = ['opportunity_id', 'body'];

    protected static function booted(): void
    {
        static::creating(function (Comment $comment) {
            $comment->user_id ??= Auth::id();
        });

        static::updating(fn () => throw new LogicException('Los comentarios no pueden modificarse.'));
        static::deleting(fn () => throw new LogicException('Los comentarios no pueden eliminarse.'));
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Sistema']);
    }
}
