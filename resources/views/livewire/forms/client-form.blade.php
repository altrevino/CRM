<div>
    <x-modal :title="$clientId ? 'Editar cliente' : 'Nuevo cliente'" size="md">
        <form id="client-form" wire:submit="save" class="space-y-4">
            <x-field label="Nombre completo" for="cf-name" error="name" required>
                <input id="cf-name" type="text" wire:model.live.debounce.400ms="name" class="input @error('name') input-error @enderror" placeholder="Ej. Juan Pérez Garza" autocomplete="off" required minlength="3" maxlength="150">
            </x-field>
            <x-field label="Teléfono" for="cf-phone" error="phone" required hint="10 dígitos. Se usa para el botón de WhatsApp.">
                <input id="cf-phone" type="tel" inputmode="tel" wire:model.live.debounce.400ms="phone" class="input @error('phone') input-error @enderror" placeholder="81 1234 5678" required maxlength="25">
            </x-field>

            @if ($this->duplicates->isNotEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm">
                    <p class="flex items-center gap-1.5 font-medium text-amber-900"><x-icon name="alert" class="size-4" /> Posible duplicado</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($this->duplicates as $match)
                            <li class="flex items-center justify-between gap-2">
                                <a href="{{ route('clients.show', $match['client']) }}" wire:navigate class="link truncate">{{ $match['client']->name }}</a>
                                <span class="shrink-0 text-xs text-amber-800">{{ $match['reason'] }} · {{ $match['client']->formattedPhone() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-amber-800">Puedes guardar de todos modos si es una persona distinta.</p>
                </div>
            @endif

            <x-field label="Notas" for="cf-notes" error="notes">
                <textarea id="cf-notes" wire:model="notes" rows="3" class="input" placeholder="Cómo nos conoció, preferencias de contacto…"></textarea>
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="client-form" class="btn-primary" wire:loading.attr="disabled">Guardar cliente</button>
        </x-slot:footer>
    </x-modal>
</div>
