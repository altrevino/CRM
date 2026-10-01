<?php

namespace App\Livewire\Concerns;

use App\Models\Task;

/** Acciones rápidas sobre tareas desde cualquier lista. */
trait ManagesTasks
{
    public function completeTask(string $id): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $task->complete();
        $this->dispatch('notify', message: 'Tarea completada');
    }

    public function cancelTask(string $id): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $task->cancel();
        $this->dispatch('notify', message: 'Tarea cancelada');
    }

    public function reopenTask(string $id): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $task->reopen();
        $this->dispatch('notify', message: 'Tarea reabierta');
    }
}
