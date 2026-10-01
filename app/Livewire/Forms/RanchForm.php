<?php

namespace App\Livewire\Forms;

use App\Enums\FenceType;
use App\Models\Client;
use App\Models\MexicanState;
use App\Models\Ranch;
use App\Services\DuplicateDetector;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class RanchForm extends Component
{
    public bool $showModal = false;

    #[Locked]
    public ?string $ranchId = null;

    public ?string $client_id = null;

    public string $clientSearch = '';

    public string $name = '';

    public string $municipality = '';

    public ?string $state_id = null;

    public string $maps_url = '';

    public ?string $km_round_trip = null;

    public ?string $fence_type = null;

    public ?string $total_hectares = null;

    public string $notes = '';

    #[On('open-ranch-form')]
    public function open(?string $id = null, ?string $clientId = null): void
    {
        $this->resetValidation();
        $this->reset();

        if ($id) {
            $ranch = Ranch::findOrFail($id);
            $this->authorize('update', $ranch);
            $this->ranchId = $ranch->id;
            $this->fill($ranch->only(['client_id', 'name', 'municipality', 'maps_url', 'notes']));
            $this->state_id = $ranch->state_id ? (string) $ranch->state_id : null;
            $this->fence_type = $ranch->fence_type?->value;
            $this->km_round_trip = $ranch->km_round_trip !== null ? (string) (float) $ranch->km_round_trip : null;
            $this->total_hectares = $ranch->total_hectares !== null ? (string) (float) $ranch->total_hectares : null;
            $this->municipality = (string) $ranch->municipality;
            $this->maps_url = (string) $ranch->maps_url;
            $this->notes = (string) $ranch->notes;
        } else {
            $this->authorize('create', Ranch::class);
            $this->client_id = $clientId;
            $this->state_id = (string) (MexicanState::where('name', 'Nuevo León')->value('id') ?? '');
        }

        $this->showModal = true;
    }

    public function selectClient(string $id): void
    {
        $this->client_id = $id;
        $this->clientSearch = '';
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

    #[Computed]
    public function duplicates()
    {
        return app(DuplicateDetector::class)->similarRanches($this->client_id, $this->name, $this->ranchId);
    }

    #[Computed]
    public function municipalities(): array
    {
        return Ranch::query()->whereNotNull('municipality')->distinct()->orderBy('municipality')->limit(300)->pluck('municipality')->all();
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'municipality' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'exists:mexican_states,id'],
            'maps_url' => ['nullable', 'url:http,https', 'max:2000', 'regex:/(google\.[a-z.]+\/maps|maps\.google\.|goo\.gl\/maps|maps\.app\.goo\.gl|g\.co\/kgs)/i'],
            'km_round_trip' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'fence_type' => ['nullable', Rule::enum(FenceType::class)],
            'total_hectares' => ['nullable', 'numeric', 'gt:0', 'max:9999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'client_id.required' => 'Selecciona el cliente al que pertenece el rancho.',
            'maps_url.regex' => 'La liga debe ser de Google Maps (google.com/maps o maps.app.goo.gl).',
            'total_hectares.gt' => 'La superficie debe ser mayor a 0 hectáreas.',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['maps_url'] = $data['maps_url'] ?: null;
        $data['fence_type'] = $data['fence_type'] ?: null;
        $data['name'] = trim($data['name']);
        $data['municipality'] = trim($data['municipality']);

        if ($this->ranchId) {
            $ranch = Ranch::findOrFail($this->ranchId);
            $this->authorize('update', $ranch);
            $ranch->update($data);
            $this->showModal = false;
            $this->dispatch('crm:refresh');
            $this->dispatch('notify', message: 'Rancho actualizado');

            return;
        }

        $this->authorize('create', Ranch::class);
        $ranch = Ranch::create($data);
        $this->showModal = false;
        session()->flash('notify', 'Rancho creado');
        $this->redirectRoute('ranches.show', $ranch, navigate: true);
    }

    public function render()
    {
        return view('livewire.forms.ranch-form', [
            'states' => MexicanState::options(),
            'fenceTypes' => FenceType::options(),
        ]);
    }
}
