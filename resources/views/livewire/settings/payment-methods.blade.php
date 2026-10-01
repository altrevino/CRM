<div>
    <x-page-header title="Configuración" subtitle="Formas de pago disponibles al registrar pagos" />
    @include('livewire.settings.nav')

    <div class="card max-w-2xl">
        <form wire:submit="add" class="flex gap-2 border-b border-slate-100 p-4">
            <div class="flex-1">
                <input type="text" wire:model="name" class="input" placeholder="Nueva forma de pago" maxlength="60">
                @error('name')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary">Agregar</button>
        </form>
        <ul class="divide-y divide-slate-100">
            @foreach ($methods as $method)
                <li wire:key="pm-{{ $method->id }}" class="flex items-center gap-3 px-4 py-2.5 {{ $method->is_active ? '' : 'opacity-60' }}">
                    @if ($editingId === $method->id)
                        <form wire:submit="update" class="flex flex-1 gap-2">
                            <input type="text" wire:model="editingName" class="input" autofocus>
                            <button type="submit" class="btn-primary btn-sm">Guardar</button>
                            <button type="button" wire:click="$set('editingId', null)" class="btn-ghost btn-sm">Cancelar</button>
                        </form>
                    @else
                        <span class="flex-1 text-sm font-medium text-slate-800">{{ $method->name }}</span>
                        <span class="text-xs text-slate-400">{{ $method->payments_count }} pagos</span>
                        <button type="button" wire:click="edit({{ $method->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></button>
                        <button type="button" wire:click="toggle({{ $method->id }})" class="btn-ghost btn-sm">{{ $method->is_active ? 'Desactivar' : 'Activar' }}</button>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
