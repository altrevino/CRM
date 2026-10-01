<div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false" class="relative max-w-xl"
     x-on:keydown.window.slash="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) { $event.preventDefault(); $refs.q.focus() }">
    <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
    <input
        x-ref="q"
        type="search"
        wire:model.live.debounce.300ms="query"
        x-on:focus="open = true"
        x-on:input="open = true"
        class="input bg-slate-50 pl-9 ring-slate-200"
        placeholder="Buscar cliente, rancho, municipio, teléfono o cotización…"
        autocomplete="off"
        aria-label="Buscador global"
    >
    @if (mb_strlen(trim($query)) >= 2)
        <div x-show="open" x-cloak x-transition.opacity class="absolute z-40 mt-2 max-h-[70vh] w-full min-w-[18rem] overflow-y-auto rounded-xl bg-white py-2 shadow-xl ring-1 ring-slate-200">
            @forelse ($this->results as $group => $items)
                <div class="px-2 py-1">
                    <p class="px-2 pb-1 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $group }}</p>
                    @foreach ($items as $item)
                        <a href="{{ $item['url'] }}" wire:navigate x-on:click="open = false" class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                            <span class="rounded-md bg-slate-100 p-1.5 text-slate-500"><x-icon :name="$item['icon']" class="size-4" /></span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-800">{{ $item['title'] }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $item['meta'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @empty
                <p class="px-4 py-3 text-sm text-slate-500">Sin resultados para «{{ $query }}».</p>
            @endforelse
        </div>
    @endif
</div>
