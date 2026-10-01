<?php

namespace App\Livewire\Forms;

use App\Enums\ServiceType;
use App\Livewire\Concerns\ManagesSpecies;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Edición de un servicio existente. La creación usa el flujo rápido Opportunities\Create. */
class OpportunityForm extends Component
{
    use ManagesSpecies;

    public bool $showModal = false;

    #[Locked]
    public ?string $opportunityId = null;

    public string $service_type = '';

    public array $species_ids = [];

    public ?string $quoted_hectares = null;

    public ?string $tentative_census_date = null;

    public ?string $census_date = null;

    public ?string $last_contact_at = null;

    public ?string $owner_id = null;

    public string $notes = '';

    public ?string $ranchHectares = null;

    #[On('open-opportunity-form')]
    public function open(string $id): void
    {
        $this->resetValidation();
        $opportunity = Opportunity::with(['species', 'ranch'])->findOrFail($id);
        $this->authorize('update', $opportunity);

        $this->opportunityId = $opportunity->id;
        $this->service_type = $opportunity->service_type->value;
        $this->species_ids = $opportunity->species->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->quoted_hectares = $opportunity->quoted_hectares !== null ? (string) (float) $opportunity->quoted_hectares : null;
        $this->tentative_census_date = $opportunity->tentative_census_date?->toDateString();
        $this->census_date = $opportunity->census_date?->toDateString();
        $this->last_contact_at = $opportunity->last_contact_at?->toDateString();
        $this->owner_id = $opportunity->owner_id;
        $this->notes = (string) $opportunity->notes;
        $this->ranchHectares = $opportunity->ranch?->total_hectares !== null ? (string) (float) $opportunity->ranch->total_hectares : null;

        $this->showModal = true;
    }

    protected function rules(): array
    {
        return [
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'species_ids' => ['array'],
            'species_ids.*' => ['integer', 'exists:species,id'],
            'quoted_hectares' => ['nullable', 'numeric', 'gt:0', 'max:9999999'],
            'tentative_census_date' => ['nullable', 'date'],
            'census_date' => ['nullable', 'date'],
            'last_contact_at' => ['nullable', 'date', 'before_or_equal:today'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function messages(): array
    {
        return ['quoted_hectares.gt' => 'Las hectáreas deben ser mayores a 0.'];
    }

    public function save(): void
    {
        $data = $this->validate();
        $opportunity = Opportunity::findOrFail($this->opportunityId);
        $this->authorize('update', $opportunity);

        DB::transaction(function () use ($opportunity, $data) {
            $opportunity->update([
                'service_type' => $data['service_type'],
                'quoted_hectares' => $data['quoted_hectares'] ?: null,
                'tentative_census_date' => $data['tentative_census_date'] ?: null,
                'census_date' => $data['census_date'] ?: null,
                'last_contact_at' => $data['last_contact_at'] ?: null,
                'owner_id' => $data['owner_id'] ?: null,
                'notes' => $data['notes'] ?: null,
            ]);
            $opportunity->species()->sync($data['species_ids']);
        });

        $this->showModal = false;
        $this->dispatch('crm:refresh');
        $this->dispatch('notify', message: 'Servicio actualizado');
    }

    public function render()
    {
        return view('livewire.forms.opportunity-form', [
            'serviceTypes' => ServiceType::options(),
            'users' => User::active()->orderBy('name')->pluck('name', 'id')->all(),
            'speciesOptions' => $this->showModal ? $this->speciesOptions() : [],
        ]);
    }
}
