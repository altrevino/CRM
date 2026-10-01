<div>
    <x-page-header title="Pagos" subtitle="Pagos registrados en todos los servicios">
        <x-slot:actions>
            <button type="button" wire:click="export" class="btn-secondary"><x-icon name="download" class="size-4" /> Exportar</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="relative lg:col-span-2">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
                <input type="search" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Cliente, rancho o referencia…">
            </div>
            <x-select wire:model.live="client" :options="$clients" placeholder="Todos los clientes" />
            <x-select wire:model.live="method" :options="$methods" placeholder="Todas las formas de pago" />
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="dateFrom" class="input" aria-label="Desde" title="Desde">
                <input type="date" wire:model.live="dateTo" class="input" aria-label="Hasta" title="Hasta">
            </div>
        </div>
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 text-sm">
            <span class="text-slate-500">Total filtrado: <span class="font-semibold text-slate-900 tabular-nums">{{ money($total) }}</span></span>
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="clearFilters" class="btn-ghost btn-sm">Limpiar filtros</button>
            @endif
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <x-sort-th field="paid_at" label="Fecha" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="client_name" label="Cliente" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sort-th field="ranch_name" label="Rancho" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <th class="table-th">Cotización</th>
                        <th class="table-th">Forma de pago</th>
                        <th class="table-th">Referencia</th>
                        <x-sort-th field="amount" label="Monto" :sort-field="$sortField" :sort-direction="$sortDirection" align="right" />
                        <th class="table-th">Registró</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr wire:key="p-{{ $payment->id }}" class="hover:bg-slate-50/70">
                            <td class="table-td">{{ fecha($payment->paid_at) }}</td>
                            <td class="table-td"><a href="{{ route('clients.show', $payment->opportunity->ranch->client_id) }}" wire:navigate class="hover:text-brand-700">{{ $payment->client_name }}</a></td>
                            <td class="table-td"><a href="{{ route('opportunities.show', [$payment->opportunity_id, 'tab' => 'pagos']) }}" wire:navigate class="font-medium hover:text-brand-700">{{ $payment->ranch_name }}</a></td>
                            <td class="table-td">{{ $payment->quote?->number ?? '—' }}</td>
                            <td class="table-td">{{ $payment->method->name }}</td>
                            <td class="table-td">{{ $payment->reference ?: '—' }}</td>
                            <td class="table-td text-right font-semibold tabular-nums text-slate-900">{{ money($payment->amount) }}</td>
                            <td class="table-td text-slate-500">{{ $payment->creator->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty icon="cash" title="No hay pagos con estos filtros" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-slate-100 md:hidden">
            @forelse ($payments as $payment)
                <li wire:key="pm-{{ $payment->id }}">
                    <a href="{{ route('opportunities.show', [$payment->opportunity_id, 'tab' => 'pagos']) }}" wire:navigate class="flex items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $payment->ranch_name }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->client_name }} · {{ fecha($payment->paid_at) }} · {{ $payment->method->name }}</p>
                        </div>
                        <p class="font-semibold tabular-nums">{{ money($payment->amount) }}</p>
                    </a>
                </li>
            @empty
                <li><x-empty icon="cash" title="No hay pagos con estos filtros" /></li>
            @endforelse
        </ul>

        <div class="border-t border-slate-100 px-4 py-3">{{ $payments->links() }}</div>
    </div>
</div>
