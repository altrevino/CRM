<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\ActivityLogger;

class TaskObserver
{
    public function created(Task $task): void
    {
        ActivityLogger::log(ActivityEvent::TaskCreated, $task, "{$task->title} · vence ".fecha($task->due_date));
    }

    public function updated(Task $task): void
    {
        if (! $task->wasChanged('status')) {
            return;
        }

        [$event, $verb] = match ($task->status) {
            TaskStatus::Completed => [ActivityEvent::TaskCompleted, 'completada'],
            TaskStatus::Cancelled => [ActivityEvent::TaskCancelled, 'cancelada'],
            TaskStatus::Pending => [ActivityEvent::TaskReopened, 'reabierta'],
        };

        ActivityLogger::log($event, $task, "{$task->title} ({$verb})");
    }
}
