<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Task extends Model
{
    use HasCreator, HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'status',
        'opportunity_id',
        'client_id',
        'ranch_id',
        'assigned_to',
    ];

    protected $attributes = [
        'status' => 'pendiente',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Cliente y rancho se derivan de la oportunidad cuando existe.
        static::saving(function (Task $task) {
            if ($task->opportunity_id && $task->isDirty('opportunity_id')) {
                $opportunity = Opportunity::withTrashed()->with('ranch')->find($task->opportunity_id);
                $task->ranch_id = $opportunity?->ranch_id;
                $task->client_id = $opportunity?->ranch?->client_id;
            } elseif ($task->ranch_id && $task->isDirty('ranch_id')) {
                $task->client_id = Ranch::withTrashed()->whereKey($task->ranch_id)->value('client_id');
            }
        });
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault(['name' => 'Sin asignar']);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === TaskStatus::Pending && $this->due_date->lt(today());
    }

    public function isDueToday(): bool
    {
        return $this->status === TaskStatus::Pending && $this->due_date->isSameDay(today());
    }

    public function complete(): void
    {
        $this->forceFill([
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ])->save();

        if ($this->opportunity) {
            $this->opportunity->forceFill(['last_contact_at' => today()])->saveQuietly();
        }
    }

    public function cancel(): void
    {
        $this->forceFill(['status' => TaskStatus::Cancelled, 'completed_at' => null, 'completed_by' => null])->save();
    }

    public function reopen(): void
    {
        $this->forceFill(['status' => TaskStatus::Pending, 'completed_at' => null, 'completed_by' => null])->save();
    }

    public function scopePending(Builder $query): void
    {
        $query->where('tasks.status', TaskStatus::Pending->value);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->pending()->whereDate('tasks.due_date', '<', today());
    }

    public function scopeDueToday(Builder $query): void
    {
        $query->pending()->whereDate('tasks.due_date', today());
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->pending()->whereDate('tasks.due_date', '>', today());
    }
}
