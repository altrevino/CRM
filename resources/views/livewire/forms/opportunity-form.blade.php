<div>
    <x-modal title="Editar servicio">
        @if ($showModal)
            <form id="opportunity-form" wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @include('livewire.partials.opportunity-fields')
                <x-field label="Último contacto" for="of-contact" error="last_contact_at">
                    <input id="of-contact" type="date" wire:model="last_contact_at" class="input" max="{{ today()->toDateString() }}">
                </x-field>
            </form>
        @endif
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="opportunity-form" class="btn-primary" wire:loading.attr="disabled">Guardar cambios</button>
        </x-slot:footer>
    </x-modal>
</div>
