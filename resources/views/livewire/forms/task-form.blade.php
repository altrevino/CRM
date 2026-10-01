<div>
    @php $opp = $showModal ? $this->opportunity : null; @endphp
    <x-modal :title="$taskId ? 'Editar tarea' : 'Nueva tarea de seguimiento'" :subtitle="$opp ? $opp->ranch->name.' · '.$opp->ranch->client->name : null" size="md">
        <form id="task-form" wire:submit="save" class="space-y-4">
            <x-field label="¿Qué hay que hacer?" for="tf-title" error="title" required>
                <input id="tf-title" type="text" wire:model="title" class="input @error('title') input-error @enderror" placeholder="Ej. Llamar a Pepe para confirmar noviembre" required maxlength="200">
            </x-field>

            <x-field label="Fecha" for="tf-date" error="due_date" required>
                <input id="tf-date" type="date" wire:model="due_date" class="input @error('due_date') input-error @enderror" required>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ([1 => 'Mañana', 3 => 'En 3 días', 7 => 'En 1 semana', 15 => 'En 15 días', 30 => 'En 1 mes'] as $days => $label)
                        <button type="button" wire:click="setDue({{ $days }})" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-200">{{ $label }}</button>
                    @endforeach
                </div>
            </x-field>

            @if (! $opportunityId && ! $taskId)
                @include('livewire.partials.client-picker', ['label' => 'Cliente (opcional)', 'required' => false])
            @endif

            <x-field label="Responsable" for="tf-user" error="assigned_to">
                <x-select id="tf-user" wire:model="assigned_to" :options="$users" placeholder="Sin asignar" />
            </x-field>

            <x-field label="Descripción" for="tf-desc" error="description">
                <textarea id="tf-desc" wire:model="description" rows="3" class="input" placeholder="Detalles para quien atienda el seguimiento"></textarea>
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="task-form" class="btn-primary" wire:loading.attr="disabled">Guardar tarea</button>
        </x-slot:footer>
    </x-modal>
</div>
