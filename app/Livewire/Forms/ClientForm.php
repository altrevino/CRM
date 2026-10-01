<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use App\Services\DuplicateDetector;
use App\Support\Phone;
use Closure;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ClientForm extends Component
{
    public bool $showModal = false;

    #[Locked]
    public ?string $clientId = null;

    public string $name = '';

    public string $phone = '';

    public string $notes = '';

    #[On('open-client-form')]
    public function open(?string $id = null): void
    {
        $this->resetValidation();
        $this->reset('clientId', 'name', 'phone', 'notes');

        if ($id) {
            $client = Client::findOrFail($id);
            $this->authorize('update', $client);
            $this->clientId = $client->id;
            $this->name = $client->name;
            $this->phone = $client->phone ? Phone::format($client->phone) : '';
            $this->notes = (string) $client->notes;
        } else {
            $this->authorize('create', Client::class);
        }

        $this->showModal = true;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'phone' => ['required', 'string', 'max:25', function (string $attribute, mixed $value, Closure $fail) {
                if (! Phone::isValid($value)) {
                    $fail('El teléfono no es válido. Captura 10 dígitos (ej. 81 1234 5678) o formato internacional (+1 …).');
                }
            }],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return Collection */
    #[Computed]
    public function duplicates()
    {
        if (mb_strlen(trim($this->name)) < 4 && ! Phone::isValid($this->phone)) {
            return collect();
        }

        return app(DuplicateDetector::class)->similarClients($this->name, $this->phone, $this->clientId);
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['phone'] = Phone::normalize($data['phone']);
        $data['name'] = trim(preg_replace('/\s+/', ' ', $data['name']));

        if ($this->clientId) {
            $client = Client::findOrFail($this->clientId);
            $this->authorize('update', $client);
            $client->update($data);
            $this->showModal = false;
            $this->dispatch('crm:refresh');
            $this->dispatch('notify', message: 'Cliente actualizado');

            return;
        }

        $this->authorize('create', Client::class);
        $client = Client::create($data);
        $this->showModal = false;
        session()->flash('notify', 'Cliente creado');
        $this->redirectRoute('clients.show', $client, navigate: true);
    }

    public function render()
    {
        return view('livewire.forms.client-form');
    }
}
