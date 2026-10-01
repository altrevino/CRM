<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskForm extends Component
{
    public bool $showModal = false;

    #[Locked]
    public ?string $taskId = null;

    #[Locked]
    public ?string $opportunityId = null;

    public ?string $client_id = null;

    public string $clientSearch = '';

    public string $title = '';

    public string $description = '';

    public ?string $due_date = null;

    public ?string $assigned_to = null;

    #[On('open-task-form')]
    public function open(?string $opportunityId = null, ?string $clientId = null, ?string $taskId = null): void
    {
        $this->resetValidation();
        $this->reset();

        if ($taskId) {
            $task = Task::findOrFail($taskId);
            $this->authorize('update', $task);
            $this->taskId = $task->id;
            $this->opportunityId = $task->opportunity_id;
            $this->client_id = $task->client_id;
            $this->title = $task->title;
            $this->description = (string) $task->description;
            $this->due_date = $task->due_date->toDateString();
            $this->assigned_to = $task->assigned_to;
        } else {
            $this->authorize('create', Task::class);
            $this->opportunityId = $opportunityId;
            $this->client_id = $clientId;
            $this->due_date = today()->addDay()->toDateString();
            $this->assigned_to = Auth::id();
        }

        $this->showModal = true;
    }

    public function setDue(int $days): void
    {
        $this->due_date = today()->addDays($days)->toDateString();
    }

    public function selectClient(string $id): void
    {
        $this->client_id = $id;
        $this->clientSearch = '';
    }

    #[Computed]
    public function opportunity(): ?Opportunity
    {
        return $this->opportunityId ? Opportunity::with('ranch.client')->find($this->opportunityId) : null;
    }

    #[Computed]
    public function client(): ?Client
    {
        return $this->client_id ? Client::find($this->client_id) : null;
    }

    #[Computed]
    public function clientResults()
    {
        if (mb_strlen(trim($this->clientSearch)) < 2) {
            return collect();
        }

        return Client::search($this->clientSearch)->orderBy('name')->limit(8)->get();
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['required', 'date'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'client_id' => ['nullable', Rule::exists('clients', 'id')],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $payload = [
            'title' => trim($data['title']),
            'description' => $data['description'] ?: null,
            'due_date' => $data['due_date'],
            'assigned_to' => $data['assigned_to'] ?: null,
        ];

        if ($this->taskId) {
            $task = Task::findOrFail($this->taskId);
            $this->authorize('update', $task);
            $task->update($payload);
            $message = 'Tarea actualizada';
        } else {
            $this->authorize('create', Task::class);
            $task = new Task($payload);
            if ($this->opportunityId) {
                $task->opportunity_id = $this->opportunityId;
            } else {
                $task->client_id = $data['client_id'] ?: null;
            }
            $task->save();
            $message = 'Seguimiento programado para el '.fecha($task->due_date);
        }

        $this->showModal = false;
        $this->dispatch('crm:refresh');
        $this->dispatch('notify', message: $message);
    }

    public function render()
    {
        return view('livewire.forms.task-form', [
            'users' => User::active()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }
}
