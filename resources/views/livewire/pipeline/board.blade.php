<div>
    <x-page-header title="Pipeline" subtitle="Arrastra las tarjetas para cambiar de etapa. Cada movimiento queda en el historial.">
        <x-slot:actions>
            <a href="{{ route('opportunities.create') }}" wire:navigate class="btn-primary"><x-icon name="plus" class="size-4" /> Nuevo servicio</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="relative sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
            <input type="search" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Cliente, rancho o municipio…">
        </div>
        <x-select wire:model.live="owner" :options="$users" placeholder="Todos los responsables" class="sm:w-60" />
        <x-select wire:model.live="serviceType" :options="$serviceTypes" placeholder="Todos los servicios" class="sm:w-48" />
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model.live="showAllClosed" class="rounded border-slate-300 text-brand-600"> Incluir cerrados antiguos
        </label>
        @if ($search || $owner || $serviceType)
            <button type="button" wire:click="clearFilters" class="btn-ghost btn-sm">Limpiar filtros</button>
        @endif
    </div>

    <div class="-mx-4 overflow-x-auto px-4 pb-4 sm:-mx-5 sm:px-5">
        <div class="flex min-w-max gap-4">
            @foreach ($this->columns as $column)
                @php $stage = $column['stage']; @endphp
                <section wire:key="col-{{ $stage->value }}" class="flex w-72 shrink-0 flex-col rounded-2xl bg-slate-100/70 ring-1 ring-slate-200/70">
                    <header class="px-3 pt-3 pb-2">
                        <div class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <span class="size-2.5 rounded-full {{ $stage->dotClasses() }}"></span>{{ $stage->label() }}
                            </h2>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200">{{ $column['count'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 tabular-nums">{{ money($column['total']) }} · {{ hectareas($column['hectares']) }}</p>
                    </header>

                    <div class="flex min-h-24 flex-1 flex-col gap-2 overflow-y-auto px-2 pb-3 lg:max-h-[calc(100vh-17rem)]"
                         wire:sort="handleSort" wire:sort:group="pipeline" wire:sort:group-id="{{ $stage->value }}">
                        @foreach ($column['items'] as $opp)
                            @php
                                $next = $opp->next_follow_up_at;
                                $overdue = $next && \Illuminate\Support\Carbon::parse($next)->lt(today());
                                $noFollowUp = ! $next && ($stage->isOpen());
                            @endphp
                            <article wire:key="card-{{ $opp->id }}" wire:sort:item="{{ $opp->id }}"
                                     class="group cursor-grab rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-brand-300 active:cursor-grabbing {{ $overdue ? 'ring-rose-300' : '' }}">
                                <a href="{{ route('opportunities.show', $opp) }}" wire:navigate wire:sort:ignore class="block">
                                    <p class="truncate text-sm font-semibold text-slate-900 group-hover:text-brand-700">{{ $opp->ranch->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $opp->ranch->client->name }} · {{ $opp->ranch->municipality }}</p>
                                </a>
                                <div class="mt-2.5 flex items-center justify-between text-xs">
                                    <span class="text-slate-500">{{ hectareas($opp->quoted_hectares) }} · {{ $opp->service_type->label() }}</span>
                                    <span class="font-semibold tabular-nums text-slate-800">{{ $opp->quotedTotal() !== null ? money($opp->quotedTotal()) : '—' }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @if ($opp->census_date)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 text-[11px] font-medium text-emerald-700"><x-icon name="calendar" class="size-3" /> {{ fecha($opp->census_date) }}</span>
                                    @endif
                                    @if ($next)
                                        <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium {{ $overdue ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600' }}"><x-icon name="clock" class="size-3" /> {{ fecha($next) }}</span>
                                    @elseif ($noFollowUp)
                                        <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700">Sin seguimiento</span>
                                    @endif
                                    @if (in_array($stage, \App\Enums\PipelineStage::won(), true) && $opp->balance() > 0)
                                        <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700">Saldo {{ money($opp->balance()) }}</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                        @if ($column['count'] > count($column['items']))
                            <p class="px-2 text-center text-xs text-slate-500">+{{ $column['count'] - count($column['items']) }} más (usa los filtros)</p>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <x-modal title="¿Marcar como perdido?" subtitle="La oportunidad saldrá del pipeline activo." size="sm" model="confirmingLost" close="$wire.cancelLost()">
        <form id="board-lost-form" wire:submit="confirmLost">
            <x-field label="Motivo (opcional)" for="board-lost-reason" error="lostReason">
                <input id="board-lost-reason" type="text" wire:model="lostReason" class="input" placeholder="Precio, otro proveedor, pospuso…" maxlength="255">
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" wire:click="cancelLost">Cancelar</button>
            <button type="submit" form="board-lost-form" class="btn bg-rose-600 text-white hover:bg-rose-700">Confirmar perdido</button>
        </x-slot:footer>
    </x-modal>
</div>
