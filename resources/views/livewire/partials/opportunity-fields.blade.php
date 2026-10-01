{{-- Campos compartidos entre el flujo rápido y la edición del servicio. --}}
<x-field label="Tipo de servicio" error="service_type" required class="sm:col-span-2">
    <div class="grid grid-cols-3 gap-2">
        @foreach ($serviceTypes as $value => $label)
            <label class="flex cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 ring-inset transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800 has-[:checked]:ring-brand-400">
                <input type="radio" wire:model="service_type" value="{{ $value }}" class="sr-only">{{ $label }}
            </label>
        @endforeach
    </div>
</x-field>

<x-field label="Especies a censar" error="species_ids" class="sm:col-span-2" hint="Busca y selecciona varias. Si no existe, agrégala al catálogo desde aquí.">
    <x-species-select property="species_ids" :options="$speciesOptions" wire:key="species-{{ $opportunityId ?? 'new' }}" />
</x-field>

<x-field label="Hectáreas cotizadas" for="of-ha" error="quoted_hectares"
         :hint="$ranchHectares ? 'Superficie total del rancho: '.hectareas($ranchHectares) : null">
    <div class="flex gap-2">
        <input id="of-ha" type="number" step="0.01" min="0.01" wire:model="quoted_hectares" class="input @error('quoted_hectares') input-error @enderror" placeholder="1200">
        @if ($ranchHectares)
            <button type="button" class="btn-secondary btn-sm shrink-0" wire:click="$set('quoted_hectares', '{{ $ranchHectares }}')" title="Usar superficie total">Total</button>
        @endif
    </div>
</x-field>
<x-field label="Responsable" for="of-owner" error="owner_id">
    <x-select id="of-owner" wire:model="owner_id" :options="$users" placeholder="Sin asignar" />
</x-field>

<x-field label="Fecha tentativa de censo" for="of-tent" error="tentative_census_date">
    <input id="of-tent" type="date" wire:model="tentative_census_date" class="input @error('tentative_census_date') input-error @enderror">
</x-field>
<x-field label="Fecha confirmada de censo" for="of-census" error="census_date" hint="Con etapa Confirmado aparece en Censos programados.">
    <input id="of-census" type="date" wire:model="census_date" class="input @error('census_date') input-error @enderror">
</x-field>

<x-field label="Notas" for="of-notes" error="notes" class="sm:col-span-2">
    <textarea id="of-notes" wire:model="notes" rows="3" class="input" placeholder="Condiciones, requerimientos del cliente, temporada…"></textarea>
</x-field>
