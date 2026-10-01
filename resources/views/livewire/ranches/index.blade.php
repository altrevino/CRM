<div>
    <x-page-header title="Ranchos" subtitle="Predios atendidos y por atender">
        <x-slot:actions>
            <button type="button" wire:click="export" class="btn-secondary"><x-icon name="download" class="size-4" /> Exportar</button>
            <button type="button" x-on:click="$dispatch('open-ranch-form')" class="btn-primary"><x-icon name="plus" class="size-4" /> Nuevo rancho</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="relative lg:col-span-2">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
                <input type="search" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Buscar rancho o municipio…">
            </div>
            <x-select wire:model.live="client" :options="$clients" placeholder="Todos los clientes" />
            <x-select wire:model.live="state" :options="$states" placeholder="Todos los estados" />
            <x-select wire:model.live="municipality" :options="$municipalities" placeholder="Todos los municipios" />
            <div class="flex gap-2">
                <x-select wire:model.live="fence" :options="$fenceTypes" placeholder="Cualquier cerca" />
                @if ($this->hasActiveFilters())
                    <button type="button" wire:click="clearFilters" class="btn-ghost btn-sm shrink-0" title="Limpiar filtros"><x-icon name="x" class="size-4" /></button>
                @endif
            </div>
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <x-sort-th field="name" label="Rancho" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="client_name" label="Cliente" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="municipality" label="Municipio" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="state_name" label="Estado" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <th class="table-th">Cerca</th>
                        <x-sort-th field="total_hectares" label="Superficie" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" />
                        <x-sort-th field="opportunities_count" label="Servicios" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" />
                        <x-sort-th field="last_census_date" label="Último censo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="next_census_date" label="Próximo censo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <th class="table-th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ranches as $ranch)
                        <tr wire:key="r-{{ $ranch->id }}" class="hover:bg-slate-50/70">
                            <td class="table-td"><a href="{{ route('ranches.show', $ranch) }}" wire:navigate class="font-medium text-slate-900 hover:text-brand-700">{{ $ranch->name }}</a></td>
                            <td class="table-td"><a href="{{ route('clients.show', $ranch->client_id) }}" wire:navigate class="hover:text-brand-700">{{ $ranch->client->name }}</a></td>
                            <td class="table-td">{{ $ranch->municipality }}</td>
                            <td class="table-td">{{ $ranch->state?->name }}</td>
                            <td class="table-td">{{ $ranch->fence_type?->label() ?? '—' }}</td>
                            <td class="table-td text-right tabular-nums">{{ hectareas($ranch->total_hectares) }}</td>
                            <td class="table-td text-right tabular-nums">{{ $ranch->opportunities_count }}</td>
                            <td class="table-td">{{ fecha($ranch->last_census_date) }}</td>
                            <td class="table-td {{ $ranch->next_census_date ? 'font-medium text-emerald-700' : '' }}">{{ fecha($ranch->next_census_date) }}</td>
                            <td class="table-td text-right">
                                @if ($ranch->maps_url)
                                    <a href="{{ $ranch->maps_url }}" target="_blank" rel="noopener" class="btn-ghost btn-sm" title="Abrir en Google Maps"><x-icon name="map-pin" class="size-4" /></a>
                                @endif
                                <button type="button" x-on:click="$dispatch('open-ranch-form', { id: '{{ $ranch->id }}' })" class="btn-ghost btn-sm" title="Editar"><x-icon name="pencil" class="size-4" /></button>
                                @can('delete', $ranch)
                                    <button type="button" wire:click="delete('{{ $ranch->id }}')" wire:confirm="¿Eliminar el rancho {{ $ranch->name }}?" class="btn-ghost btn-sm text-rose-600" title="Eliminar"><x-icon name="trash" class="size-4" /></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><x-empty icon="map" title="No hay ranchos con estos filtros" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-slate-100 md:hidden">
            @forelse ($ranches as $ranch)
                <li wire:key="rm-{{ $ranch->id }}" class="flex items-center gap-3 p-4">
                    <a href="{{ route('ranches.show', $ranch) }}" wire:navigate class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-900">{{ $ranch->name }}</p>
                        <p class="text-xs text-slate-500">{{ $ranch->client->name }} · {{ $ranch->location() }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ hectareas($ranch->total_hectares) }} · {{ $ranch->fence_type?->label() ?? 'Cerca sin definir' }}</p>
                    </a>
                    @if ($ranch->maps_url)
                        <a href="{{ $ranch->maps_url }}" target="_blank" rel="noopener" class="rounded-full bg-blue-50 p-2.5 text-blue-600" aria-label="Google Maps"><x-icon name="map-pin" class="size-5" /></a>
                    @endif
                </li>
            @empty
                <li><x-empty icon="map" title="No hay ranchos con estos filtros" /></li>
            @endforelse
        </ul>

        <div class="border-t border-slate-100 px-4 py-3">{{ $ranches->links() }}</div>
    </div>
</div>
