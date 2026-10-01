<div>
    <x-modal :title="$ranchId ? 'Editar rancho' : 'Nuevo rancho'">
        <form id="ranch-form" wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                @include('livewire.partials.client-picker', ['label' => 'Cliente (propietario / contacto)'])
            </div>

            <x-field label="Nombre del rancho" for="rf-name" error="name" required class="sm:col-span-2">
                <input id="rf-name" type="text" wire:model.live.debounce.400ms="name" class="input @error('name') input-error @enderror" placeholder="Ej. Rancho El Venado" required maxlength="150">
            </x-field>

            @if ($this->duplicates->isNotEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:col-span-2">
                    <p class="flex items-center gap-1.5 font-medium"><x-icon name="alert" class="size-4" /> Este cliente ya tiene un rancho con nombre parecido:</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($this->duplicates as $dup)
                            <li><a href="{{ route('ranches.show', $dup) }}" wire:navigate class="link">{{ $dup->name }}</a> · {{ $dup->municipality }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-field label="Municipio" for="rf-mun" error="municipality" required>
                <input id="rf-mun" type="text" wire:model="municipality" list="rf-municipalities" class="input @error('municipality') input-error @enderror" placeholder="Ej. Anáhuac" required maxlength="100">
                <datalist id="rf-municipalities">
                    @foreach ($this->municipalities as $m)
                        <option value="{{ $m }}">
                    @endforeach
                </datalist>
            </x-field>
            <x-field label="Estado" for="rf-state" error="state_id" required>
                <x-select id="rf-state" wire:model="state_id" :options="$states" placeholder="Selecciona…" required />
            </x-field>

            <x-field label="Liga de Google Maps" for="rf-maps" error="maps_url" class="sm:col-span-2">
                <input id="rf-maps" type="url" wire:model="maps_url" class="input @error('maps_url') input-error @enderror" placeholder="https://maps.app.goo.gl/…">
            </x-field>

            <x-field label="Superficie total (ha)" for="rf-ha" error="total_hectares">
                <input id="rf-ha" type="number" step="0.01" min="0.01" wire:model="total_hectares" class="input @error('total_hectares') input-error @enderror" placeholder="1500">
            </x-field>
            <x-field label="Km desde Monterrey (viaje redondo)" for="rf-km" error="km_round_trip">
                <input id="rf-km" type="number" step="0.1" min="0" wire:model="km_round_trip" class="input @error('km_round_trip') input-error @enderror" placeholder="420">
            </x-field>

            <x-field label="Tipo de cerca" error="fence_type" class="sm:col-span-2">
                <div class="flex gap-2">
                    @foreach ($fenceTypes as $value => $label)
                        <label class="flex flex-1 cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium ring-1 ring-inset transition has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800 has-[:checked]:ring-brand-400 ring-slate-300 text-slate-600 hover:bg-slate-50">
                            <input type="radio" wire:model="fence_type" value="{{ $value }}" class="sr-only">{{ $label }}
                        </label>
                    @endforeach
                    <label class="flex flex-1 cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium ring-1 ring-inset transition has-[:checked]:bg-slate-100 ring-slate-300 text-slate-500 hover:bg-slate-50">
                        <input type="radio" wire:model="fence_type" value="" class="sr-only">Sin definir
                    </label>
                </div>
            </x-field>

            <x-field label="Notas" for="rf-notes" error="notes" class="sm:col-span-2">
                <textarea id="rf-notes" wire:model="notes" rows="3" class="input" placeholder="Accesos, contacto en sitio, observaciones…"></textarea>
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="ranch-form" class="btn-primary" wire:loading.attr="disabled">Guardar rancho</button>
        </x-slot:footer>
    </x-modal>
</div>
