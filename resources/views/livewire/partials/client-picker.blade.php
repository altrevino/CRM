{{-- Selector de cliente con búsqueda. Requiere en el componente: $client_id, $clientSearch, computed client y clientResults, selectClient(). --}}
<x-field :label="$label ?? 'Cliente'" error="client_id" :required="$required ?? true">
    @if ($this->client)
        <div class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-slate-200">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">{{ $this->client->name }}</p>
                <p class="text-xs text-slate-500">{{ $this->client->formattedPhone() }}</p>
            </div>
            <button type="button" wire:click="$set('client_id', null)" class="btn-ghost btn-sm">Cambiar</button>
        </div>
    @else
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
            <input type="text" wire:model.live.debounce.300ms="clientSearch" class="input pl-9 @error('client_id') input-error @enderror" placeholder="Buscar cliente por nombre o teléfono…" autocomplete="off">
            @if ($this->clientResults->isNotEmpty())
                <ul class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-slate-200">
                    @foreach ($this->clientResults as $result)
                        <li>
                            <button type="button" wire:click="selectClient('{{ $result->id }}')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-slate-50">
                                <span class="font-medium text-slate-800">{{ $result->name }}</span>
                                <span class="text-xs text-slate-500">{{ $result->formattedPhone() }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @elseif (mb_strlen(trim($clientSearch)) >= 2)
                <p class="mt-1 text-xs text-slate-500">Sin coincidencias.</p>
            @endif
        </div>
    @endif
</x-field>
