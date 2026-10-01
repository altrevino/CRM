{{-- Multi-select con búsqueda. Requiere en el componente: propiedad array $property y método addSpecies(string). --}}
@props(['property' => 'species_ids', 'options' => [], 'canCreate' => true])
<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="multiSelect({ property: @js($property), options: @js($options), createMethod: @js($canCreate ? 'addSpecies' : null) })"
    x-on:click.outside="open = false"
>
    <div class="input flex min-h-[38px] cursor-text flex-wrap items-center gap-1.5 py-1.5" x-on:click="open = true; $nextTick(() => $refs.search.focus())">
        <template x-for="id in selected" :key="id">
            <span class="inline-flex items-center gap-1 rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-800 ring-1 ring-brand-200 ring-inset">
                <span x-text="nameOf(id)"></span>
                <button type="button" x-on:click.stop="toggle(id)" class="text-brand-500 hover:text-brand-800" aria-label="Quitar">&times;</button>
            </span>
        </template>
        <input
            x-ref="search"
            x-model="search"
            x-on:focus="open = true"
            x-on:keydown.enter.prevent="filtered.length === 1 ? (toggle(filtered[0].id), search = '') : (canCreate && create())"
            type="text"
            class="min-w-[8rem] flex-1 border-0 p-0 text-sm focus:ring-0 focus:outline-none"
            placeholder="Buscar especie…"
        >
    </div>
    <div x-show="open" x-cloak x-transition.opacity class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-slate-200">
        <template x-for="option in filtered" :key="option.id">
            <button type="button" x-on:click="toggle(option.id)" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-slate-50">
                <span x-text="option.name"></span>
                <span x-show="isSelected(option.id)" class="text-brand-600">✓</span>
            </button>
        </template>
        <p x-show="filtered.length === 0 && !canCreate" class="px-3 py-2 text-slate-500">Sin coincidencias.</p>
        <button x-show="canCreate" type="button" x-on:click="create()" class="flex w-full items-center gap-2 px-3 py-2 text-left font-medium text-brand-700 hover:bg-brand-50">
            + Agregar «<span x-text="search.trim()"></span>» al catálogo
        </button>
    </div>
</div>
