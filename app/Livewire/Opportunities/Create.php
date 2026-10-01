<?php

namespace App\Livewire\Opportunities;

use App\Enums\FenceType;
use App\Enums\PipelineStage;
use App\Enums\ServiceType;
use App\Livewire\Concerns\ManagesSpecies;
use App\Models\Client;
use App\Models\MexicanState;
use App\Models\Opportunity;
use App\Models\Ranch;
use App\Models\Task;
use App\Models\User;
use App\Services\DuplicateDetector;
use App\Support\Phone;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Flujo rápido: cliente (existente o nuevo) → rancho (existente o nuevo) → servicio, en una sola pantalla.
 */
#[Title('Nuevo servicio')]
class Create extends Component
{
    use ManagesSpecies;

    // Paso 1: cliente
    public string $clientMode = 'existing';

    public ?string $client_id = null;

    public string $clientSearch = '';

    public string $client_name = '';

    public string $client_phone = '';

    // Paso 2: rancho
    public string $ranchMode = 'existing';

    public ?string $ranch_id = null;

    public string $ranch_name = '';

    public string $municipality = '';

    public ?string $state_id = null;

    public ?string $total_hectares = null;

    public ?string $fence_type = null;

    public string $maps_url = '';

    public ?string $km_round_trip = null;

    // Paso 3: servicio
    public string $service_type = 'completo';

    public array $species_ids = [];

    public ?string $quoted_hectares = null;

    public ?string $tentative_census_date = null;

    public ?string $census_date = null;

    public ?string $owner_id = null;

    public string $stage = 'prospecto';

    public string $notes = '';

    public ?string $opportunityId = null;

    // Primer seguimiento (opcional)
    public ?string $followup_date = null;

    public string $followup_title = '';

    public function mount(): void
    {
        $this->authorize('create', Opportunity::class);
        $this->owner_id = Auth::id();
        $this->state_id = (string) (MexicanState::where('name', 'Nuevo León')->value('id') ?? '');

        if ($ranchId = request('rancho')) {
            $ranch = Ranch::find($ranchId);
            if ($ranch) {
                $this->client_id = $ranch->client_id;
                $this->ranch_id = $ranch->id;
                $this->quoted_hectares = $ranch->total_hectares !== null ? (string) (float) $ranch->total_hectares : null;
            }
        } elseif ($clientId = request('cliente')) {
            $this->client_id = Client::whereKey($clientId)->value('id');
        }

        if ($this->client_id && ! $this->ranch_id && ! Ranch::where('client_id', $this->client_id)->exists()) {
            $this->ranchMode = 'new';
        }
    }

    // ---------------------------------------------------------------- Cliente

    public function selectClient(string $id): void
    {
        $this->client_id = $id;
        $this->clientMode = 'existing';
        $this->clientSearch = '';
        $this->ranch_id = null;
        $this->ranchMode = Ranch::where('client_id', $id)->exists() ? 'existing' : 'new';
    }

    public function clearClient(): void
    {
        $this->client_id = null;
        $this->ranch_id = null;
    }

    public function newClient(): void
    {
        $this->clientMode = 'new';
        $this->client_id = null;
        $this->ranch_id = null;
        $this->ranchMode = 'new';
        if ($this->clientSearch && ! preg_match('/\d{4,}/', $this->clientSearch)) {
            $this->client_name = $this->clientSearch;
        }
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

        return Client::search($this->clientSearch)->withCount('ranches')->orderBy('name')->limit(8)->get();
    }

    #[Computed]
    public function clientDuplicates()
    {
        if ($this->clientMode !== 'new') {
            return collect();
        }

        return app(DuplicateDetector::class)->similarClients($this->client_name, $this->client_phone);
    }

    // ---------------------------------------------------------------- Rancho

    #[Computed]
    public function clientRanches()
    {
        return $this->client_id ? Ranch::where('client_id', $this->client_id)->with('state')->orderBy('name')->get() : collect();
    }

    public function selectRanch(string $id): void
    {
        $ranch = Ranch::where('client_id', $this->client_id)->findOrFail($id);
        $this->ranch_id = $ranch->id;
        $this->ranchMode = 'existing';
        if (! $this->quoted_hectares && $ranch->total_hectares) {
            $this->quoted_hectares = (string) (float) $ranch->total_hectares;
        }
    }

    public function newRanch(): void
    {
        $this->ranchMode = 'new';
        $this->ranch_id = null;
    }

    #[Computed]
    public function ranchDuplicates()
    {
        if ($this->ranchMode !== 'new' || ! $this->client_id) {
            return collect();
        }

        return app(DuplicateDetector::class)->similarRanches($this->client_id, $this->ranch_name);
    }

    public function updatedTotalHectares(): void
    {
        if (! $this->quoted_hectares && is_numeric($this->total_hectares)) {
            $this->quoted_hectares = $this->total_hectares;
        }
    }

    // ---------------------------------------------------------------- Guardar

    protected function rules(): array
    {
        $newClient = $this->clientMode === 'new';
        $newRanch = $newClient || $this->ranchMode === 'new';

        return [
            'client_id' => [Rule::requiredIf(! $newClient), 'nullable', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'client_name' => [Rule::requiredIf($newClient), 'nullable', 'string', 'min:3', 'max:150'],
            'client_phone' => [Rule::requiredIf($newClient), 'nullable', 'string', 'max:25', function (string $attribute, mixed $value, Closure $fail) use ($newClient) {
                if ($newClient && ! Phone::isValid($value)) {
                    $fail('El teléfono no es válido. Captura 10 dígitos (ej. 81 1234 5678).');
                }
            }],
            'ranch_id' => [Rule::requiredIf(! $newRanch), 'nullable', Rule::exists('ranches', 'id')->where('client_id', $this->client_id)->whereNull('deleted_at')],
            'ranch_name' => [Rule::requiredIf($newRanch), 'nullable', 'string', 'min:2', 'max:150'],
            'municipality' => [Rule::requiredIf($newRanch), 'nullable', 'string', 'max:100'],
            'state_id' => [Rule::requiredIf($newRanch), 'nullable', 'exists:mexican_states,id'],
            'total_hectares' => ['nullable', 'numeric', 'gt:0', 'max:9999999'],
            'fence_type' => ['nullable', Rule::enum(FenceType::class)],
            'maps_url' => ['nullable', 'url:http,https', 'max:2000', 'regex:/(google\.[a-z.]+\/maps|maps\.google\.|goo\.gl\/maps|maps\.app\.goo\.gl|g\.co\/kgs)/i'],
            'km_round_trip' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'species_ids' => ['array'],
            'species_ids.*' => ['integer', 'exists:species,id'],
            'quoted_hectares' => ['nullable', 'numeric', 'gt:0', 'max:9999999'],
            'tentative_census_date' => ['nullable', 'date'],
            'census_date' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'stage' => ['required', Rule::in(PipelineStage::values([...PipelineStage::open(), PipelineStage::Confirmed]))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'followup_date' => ['nullable', 'date'],
            'followup_title' => ['nullable', 'string', 'max:200'],
        ];
    }

    protected function messages(): array
    {
        return [
            'client_id.required' => 'Busca y selecciona un cliente, o crea uno nuevo.',
            'ranch_id.required' => 'Selecciona un rancho del cliente o crea uno nuevo.',
            'maps_url.regex' => 'La liga debe ser de Google Maps.',
            'total_hectares.gt' => 'La superficie debe ser mayor a 0.',
            'quoted_hectares.gt' => 'Las hectáreas deben ser mayores a 0.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'client_name' => 'nombre del cliente',
            'client_phone' => 'teléfono',
            'ranch_name' => 'nombre del rancho',
            'followup_date' => 'fecha de seguimiento',
            'followup_title' => 'título del seguimiento',
        ];
    }

    public function save(): void
    {
        $this->authorize('create', Opportunity::class);
        $data = $this->validate();

        $opportunity = DB::transaction(function () use ($data) {
            $clientId = $this->client_id;
            if ($this->clientMode === 'new') {
                $clientId = Client::create([
                    'name' => trim(preg_replace('/\s+/', ' ', $data['client_name'])),
                    'phone' => Phone::normalize($data['client_phone']),
                ])->id;
            }

            $ranchId = $this->ranch_id;
            if ($this->clientMode === 'new' || $this->ranchMode === 'new') {
                $ranch = new Ranch([
                    'name' => trim($data['ranch_name']),
                    'municipality' => trim($data['municipality']),
                    'state_id' => $data['state_id'],
                    'total_hectares' => $data['total_hectares'] ?: null,
                    'fence_type' => $data['fence_type'] ?: null,
                    'maps_url' => $data['maps_url'] ?: null,
                    'km_round_trip' => $data['km_round_trip'] ?: null,
                ]);
                $ranch->client_id = $clientId;
                $ranch->save();
                $ranchId = $ranch->id;
            }

            $opportunity = Opportunity::create([
                'ranch_id' => $ranchId,
                'stage' => $data['stage'],
                'service_type' => $data['service_type'],
                'quoted_hectares' => $data['quoted_hectares'] ?: null,
                'tentative_census_date' => $data['tentative_census_date'] ?: null,
                'census_date' => $data['census_date'] ?: null,
                'owner_id' => $data['owner_id'] ?: null,
                'notes' => $data['notes'] ?: null,
                'last_contact_at' => today(),
            ]);
            $opportunity->species()->sync($data['species_ids']);

            if ($data['followup_date']) {
                Task::create([
                    'title' => $data['followup_title'] ?: 'Dar seguimiento',
                    'due_date' => $data['followup_date'],
                    'opportunity_id' => $opportunity->id,
                    'assigned_to' => $data['owner_id'] ?: Auth::id(),
                ]);
            }

            return $opportunity;
        });

        session()->flash('notify', 'Servicio creado');
        $this->redirectRoute('opportunities.show', $opportunity, navigate: true);
    }

    public function render()
    {
        return view('livewire.opportunities.create', [
            'serviceTypes' => ServiceType::options(),
            'fenceTypes' => FenceType::options(),
            'states' => MexicanState::options(),
            'users' => User::active()->orderBy('name')->pluck('name', 'id')->all(),
            'stages' => collect([...PipelineStage::open(), PipelineStage::Confirmed])->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(),
            'speciesOptions' => $this->speciesOptions(),
            'ranchHectares' => $this->ranch_id
                ? $this->clientRanches->firstWhere('id', $this->ranch_id)?->total_hectares
                : (is_numeric($this->total_hectares) ? $this->total_hectares : null),
            'municipalities' => Ranch::query()->whereNotNull('municipality')->distinct()->orderBy('municipality')->limit(300)->pluck('municipality')->all(),
        ]);
    }
}
