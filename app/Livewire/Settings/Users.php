<?php

namespace App\Livewire\Settings;

use App\Enums\ActivityEvent;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Usuarios')]
class Users extends Component
{
    public bool $showModal = false;

    #[Locked]
    public ?string $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'usuario';

    public string $password = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->resetValidation();
        $this->reset('userId', 'name', 'email', 'password');
        $this->role = UserRole::User->value;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->password = '';
        $this->showModal = true;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->userId)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => [$this->userId ? 'nullable' : 'required', 'string', Password::min(8)->letters()->numbers()],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $this->authorize('update', $user);

            if ($user->is(auth()->user()) && $data['role'] !== UserRole::Admin->value) {
                $this->addError('role', 'No puedes quitarte el rol de administrador a ti mismo.');

                return;
            }

            $user->fill(collect($data)->except('password')->all());
            if ($data['password']) {
                $user->password = $data['password'];
            }
            $user->save();
            ActivityLogger::log(ActivityEvent::UserUpdated, $user, "Usuario {$user->name} editado");
        } else {
            $this->authorize('create', User::class);
            $user = User::create($data + ['is_active' => true]);
            ActivityLogger::log(ActivityEvent::UserCreated, $user, "Usuario {$user->name} creado ({$user->role->label()})");
        }

        $this->showModal = false;
        $this->dispatch('notify', message: 'Usuario guardado');
    }

    public function toggleActive(string $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('deactivate', $user);

        $user->is_active = ! $user->is_active;
        $user->save();
        ActivityLogger::log(ActivityEvent::UserUpdated, $user, "Usuario {$user->name} ".($user->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $user->is_active ? 'Usuario activado' : 'Usuario desactivado');
    }

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::orderByDesc('is_active')->orderBy('name')->get(),
            'roles' => UserRole::options(),
        ]);
    }
}
