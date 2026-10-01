<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskStatus;
use App\Livewire\Concerns\ManagesTasks;
use App\Livewire\Concerns\RefreshesOnSave;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Seguimientos')]
class Index extends Component
{
    use ManagesTasks, RefreshesOnSave, WithPagination;

    /** pendientes | cerradas */
    #[Session]
    public string $scope = 'pendientes';

    #[Session]
    public string $assignee = '';

    #[Session]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Task::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    protected function base(): Builder
    {
        return Task::query()
            ->with(['opportunity.ranch', 'client', 'assignee', 'completer'])
            ->when($this->assignee === 'mias', fn ($q) => $q->where('assigned_to', auth()->id()))
            ->when($this->assignee && $this->assignee !== 'mias', fn ($q) => $q->where('assigned_to', $this->assignee))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->search}%")
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('ranch', fn ($r) => $r->where('name', 'like', "%{$this->search}%"))));
    }

    #[Computed]
    public function groups(): array
    {
        return [
            'overdue' => $this->base()->overdue()->orderBy('due_date')->get(),
            'today' => $this->base()->dueToday()->orderBy('created_at')->get(),
            'upcoming' => $this->base()->upcoming()->orderBy('due_date')->limit(100)->get(),
        ];
    }

    public function render()
    {
        return view('livewire.tasks.index', [
            'closed' => $this->scope === 'cerradas'
                ? $this->base()->whereIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])->latest('updated_at')->paginate(30)
                : null,
            'users' => ['mias' => 'Mis tareas'] + User::active()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }
}
