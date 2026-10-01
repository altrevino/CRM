<div>
    <x-page-header title="Clientes" subtitle="Propietarios y contactos de ranchos">
        <x-slot:actions>
            <button type="button" wire:click="export" class="btn-secondary"><x-icon name="download" class="size-4" /> Exportar</button>
            <button type="button" x-on:click="$dispatch('open-client-form')" class="btn-primary"><x-icon name="plus" class="size-4" /> Nuevo cliente</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-4">
            <div class="relative w-full sm:min-w-56 sm:flex-1 xl:max-w-sm">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
                <input type="search" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Buscar por nombre o teléfono…">
            </div>
            <x-select wire:model.live="status" :options="$statusOptions" placeholder="Todos los clientes" class="sm:w-60" />
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters" class="btn-ghost btn-sm">Limpiar filtros</button>
            @endif
        </div>

        {{-- Tabla (escritorio) --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <x-sort-th field="name" label="Nombre" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <th class="table-th">Teléfono</th>
                        <x-sort-th field="ranches_count" label="Ranchos" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" />
                        <x-sort-th field="active_opportunities_count" label="Activas" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" class="hidden xl:table-cell" />
                        <x-sort-th field="total_sold" label="Total vendido" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" />
                        <th class="table-th text-right">Saldo</th>
                        <x-sort-th field="last_contact_at" label="Último contacto" :sort-field="$sortField" :sort-direction="$sortDirection" class="hidden xl:table-cell" />
                        <x-sort-th field="next_follow_up_at" label="Próx. seguimiento" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <th class="table-th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($clients as $client)
                        <tr wire:key="c-{{ $client->id }}" class="hover:bg-slate-50/70">
                            <td class="table-td min-w-40 whitespace-normal"><a href="{{ route('clients.show', $client) }}" wire:navigate class="font-medium text-slate-900 hover:text-brand-700">{{ $client->name }}</a></td>
                            <td class="table-td">
                                @if ($client->phone)
                                    <a href="{{ $client->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-emerald-700" title="Abrir WhatsApp">
                                        <x-icon name="whatsapp" class="size-4 text-emerald-600" /> {{ $client->formattedPhone() }}
                                    </a>
                                @endif
                            </td>
                            <td class="table-td text-right tabular-nums">{{ $client->ranches_count }}</td>
                            <td class="table-td hidden text-right tabular-nums xl:table-cell">{{ $client->active_opportunities_count }}</td>
                            <td class="table-td text-right tabular-nums">{{ money($client->total_sold) }}</td>
                            <td class="table-td text-right tabular-nums {{ $client->balance > 0 ? 'font-medium text-amber-700' : 'text-slate-400' }}">{{ money($client->balance) }}</td>
                            <td class="table-td hidden xl:table-cell">{{ fecha($client->last_contact_at) }}</td>
                            <td class="table-td">
                                @if ($client->next_follow_up_at)
                                    @php $overdue = \Illuminate\Support\Carbon::parse($client->next_follow_up_at)->lt(today()); @endphp
                                    <span class="{{ $overdue ? 'font-medium text-rose-600' : '' }}">{{ fecha($client->next_follow_up_at) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="table-td text-right">
                                <button type="button" x-on:click="$dispatch('open-client-form', { id: '{{ $client->id }}' })" class="btn-ghost btn-sm px-1.5" title="Editar"><x-icon name="pencil" class="size-4" /></button>
                                @can('delete', $client)
                                    <button type="button" wire:click="delete('{{ $client->id }}')" wire:confirm="¿Eliminar al cliente {{ $client->name }}? Esta acción queda registrada en el historial." class="btn-ghost btn-sm px-1.5 text-rose-600" title="Eliminar"><x-icon name="trash" class="size-4" /></button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty icon="users" title="No hay clientes con estos filtros" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tarjetas (móvil) --}}
        <ul class="divide-y divide-slate-100 md:hidden">
            @forelse ($clients as $client)
                <li wire:key="cm-{{ $client->id }}" class="flex items-center gap-3 p-4">
                    <a href="{{ route('clients.show', $client) }}" wire:navigate class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-900">{{ $client->name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $client->ranches_count }} {{ $client->ranches_count === 1 ? 'rancho' : 'ranchos' }} · {{ $client->active_opportunities_count }} activos</p>
                        @if ($client->balance > 0)
                            <p class="mt-1 text-xs font-medium text-amber-700">Saldo {{ money($client->balance) }}</p>
                        @endif
                    </a>
                    @if ($client->phone)
                        <a href="{{ $client->whatsappUrl() }}" target="_blank" rel="noopener" class="rounded-full bg-emerald-50 p-2.5 text-emerald-600" aria-label="WhatsApp"><x-icon name="whatsapp" class="size-5" /></a>
                    @endif
                </li>
            @empty
                <li><x-empty icon="users" title="No hay clientes con estos filtros" /></li>
            @endforelse
        </ul>

        <div class="border-t border-slate-100 px-4 py-3">{{ $clients->links() }}</div>
    </div>
</div>
