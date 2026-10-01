<div>
    <x-page-header title="Cotizaciones" subtitle="Todas las cotizaciones y sus versiones">
        <x-slot:actions>
            <button type="button" wire:click="export" class="btn-secondary"><x-icon name="download" class="size-4" /> Exportar</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-4">
            <div class="relative w-full sm:min-w-56 sm:flex-1 xl:max-w-xs">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
                <input type="search" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Número, cliente o rancho…">
            </div>
            <x-select wire:model.live="status" :options="$statuses" placeholder="Estatus" class="w-full sm:w-36" aria-label="Estatus de cotización" />
            <x-select wire:model.live="client" :options="$clients" placeholder="Cliente" class="w-full sm:w-48" aria-label="Cliente" />
            <x-select wire:model.live="state" :options="$states" placeholder="Entidad" class="w-full sm:w-40" aria-label="Estado de la república" />
            <x-select wire:model.live="vat" :options="['si' => 'Con IVA', 'no' => 'Sin IVA']" placeholder="Con y sin IVA" class="w-full sm:w-40" aria-label="IVA" />
            @include('livewire.partials.date-range')
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters" class="btn-ghost btn-sm">Limpiar filtros</button>
            @endif
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <x-sort-th field="number" label="Número" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="issued_at" label="Fecha" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="client_name" label="Cliente / Rancho" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="hectares" label="Ha" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" />
                        <x-sort-th field="subtotal" label="Subtotal" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" class="hidden xl:table-cell" />
                        <x-sort-th field="vat_amount" label="IVA" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" class="hidden xl:table-cell" />
                        <x-sort-th field="total" label="Total" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" />
                        <x-sort-th field="status" label="Estado" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($quotes as $quote)
                        <tr wire:key="q-{{ $quote->id }}" class="hover:bg-slate-50/70">
                            <td class="table-td"><a href="{{ route('opportunities.show', [$quote->opportunity_id, 'tab' => 'cotizaciones']) }}" wire:navigate class="font-medium text-slate-900 hover:text-brand-700">{{ $quote->number }}</a></td>
                            <td class="table-td">{{ fecha($quote->issued_at) }}</td>
                            <td class="table-td">
                                <a href="{{ route('clients.show', $quote->opportunity->ranch->client_id) }}" wire:navigate class="block max-w-60 truncate font-medium text-slate-800 hover:text-brand-700">{{ $quote->client_name }}</a>
                                <span class="block max-w-64 truncate text-xs text-slate-500">
                                    <a href="{{ route('ranches.show', $quote->opportunity->ranch_id) }}" wire:navigate class="hover:text-brand-700">{{ $quote->ranch_name }}</a>
                                    · {{ $quote->opportunity->service_type->label() }}
                                </span>
                            </td>
                            <td class="table-td text-right tabular-nums">{{ hectareas($quote->hectares, false) }}</td>
                            <td class="table-td hidden text-right tabular-nums xl:table-cell">{{ money($quote->subtotal) }}</td>
                            <td class="table-td hidden text-right tabular-nums xl:table-cell {{ $quote->apply_vat ? '' : 'text-amber-700' }}">{{ $quote->apply_vat ? money($quote->vat_amount) : 'Sin IVA' }}</td>
                            <td class="table-td text-right font-semibold tabular-nums text-slate-900">
                                {{ money($quote->total) }}
                                <span class="block text-xs font-normal {{ $quote->apply_vat ? 'text-slate-400' : 'text-amber-700' }} xl:hidden">{{ $quote->vatLabel() }}</span>
                            </td>
                            <td class="table-td">
                                <div class="flex flex-wrap items-center gap-1">
                                    <x-badge :tone="$quote->status->badgeClasses()">{{ $quote->status->label() }}</x-badge>
                                    @isset($staleIds[$quote->id])<x-badge tone="rose" title="Enviada sin actividad posterior">Requiere seguimiento</x-badge>@endisset
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty icon="document" title="No hay cotizaciones con estos filtros" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-slate-100 md:hidden">
            @forelse ($quotes as $quote)
                <li wire:key="qm-{{ $quote->id }}">
                    <a href="{{ route('opportunities.show', [$quote->opportunity_id, 'tab' => 'cotizaciones']) }}" wire:navigate class="block p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium text-slate-900">{{ $quote->number }}</p>
                            <p class="font-semibold tabular-nums">{{ money($quote->total) }}</p>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $quote->client_name }} · {{ $quote->ranch_name }} · {{ fecha($quote->issued_at) }}</p>
                        <div class="mt-2 flex gap-1.5">
                            <x-badge :tone="$quote->status->badgeClasses()">{{ $quote->status->label() }}</x-badge>
                            <x-badge :tone="$quote->apply_vat ? 'sky' : 'amber'">{{ $quote->vatLabel() }}</x-badge>
                            @isset($staleIds[$quote->id])<x-badge tone="rose">Requiere seguimiento</x-badge>@endisset
                        </div>
                    </a>
                </li>
            @empty
                <li><x-empty icon="document" title="No hay cotizaciones con estos filtros" /></li>
            @endforelse
        </ul>

        <div class="border-t border-slate-100 px-4 py-3">{{ $quotes->links() }}</div>
    </div>
</div>
