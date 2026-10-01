<div>
    <x-page-header title="Censos programados" subtitle="Servicios en etapa Confirmado con fecha de censo asignada">
        <x-slot:actions>
            <div class="inline-flex rounded-lg bg-slate-100 p-1">
                <button type="button" wire:click="$set('mode', 'calendario')" class="rounded-md px-3 py-1.5 text-sm font-medium {{ $mode === 'calendario' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}"><x-icon name="calendar" class="mr-1 inline size-4" />Calendario</button>
                <button type="button" wire:click="$set('mode', 'lista')" class="rounded-md px-3 py-1.5 text-sm font-medium {{ $mode === 'lista' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}"><x-icon name="list" class="mr-1 inline size-4" />Lista</button>
            </div>
            <button type="button" wire:click="export" class="btn-secondary"><x-icon name="download" class="size-4" /> Exportar</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative sm:w-80">
            <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
            <input type="search" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Cliente, rancho o municipio…">
        </div>
        @if ($mode === 'lista')
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" wire:model.live="includePast" class="rounded border-slate-300 text-brand-600"> Incluir fechas pasadas</label>
        @endif
    </div>

    @if ($mode === 'calendario')
        @php $cal = $this->calendar; @endphp
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h2 class="text-lg font-semibold text-slate-900">{{ $cal['label'] }}</h2>
                <div class="flex items-center gap-1">
                    <button type="button" wire:click="goToToday" class="btn-secondary btn-sm">Hoy</button>
                    <button type="button" wire:click="previousMonth" class="btn-ghost p-1.5" aria-label="Mes anterior"><x-icon name="chevron-left" /></button>
                    <button type="button" wire:click="nextMonth" class="btn-ghost p-1.5" aria-label="Mes siguiente"><x-icon name="chevron-right" /></button>
                </div>
            </div>
            <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50/60 text-center text-xs font-semibold text-slate-500">
                @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d)
                    <div class="py-2">{{ $d }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach ($cal['weeks'] as $week)
                    @foreach ($week as $day)
                        <div class="min-h-16 border-r border-b border-slate-100 p-1 sm:min-h-28 sm:p-1.5 {{ $day['inMonth'] ? 'bg-white' : 'bg-slate-50/70' }} {{ $loop->last ? 'border-r-0' : '' }}">
                            <div class="mb-1 flex justify-end">
                                <span class="flex size-6 items-center justify-center rounded-full text-xs {{ $day['isToday'] ? 'bg-brand-600 font-semibold text-white' : ($day['inMonth'] ? 'text-slate-700' : 'text-slate-400') }}">{{ $day['date']->day }}</span>
                            </div>
                            <div class="space-y-1">
                                @foreach ($day['events'] as $event)
                                    <a href="{{ route('opportunities.show', $event) }}" wire:navigate wire:key="ev-{{ $event->id }}"
                                       class="block rounded-md bg-emerald-50 px-1.5 py-1 text-[11px] leading-tight ring-1 ring-emerald-200 transition hover:bg-emerald-100"
                                       title="{{ $event->ranch->name }} · {{ $event->ranch->client->name }} · {{ $event->ranch->municipality }} · {{ hectareas($event->quoted_hectares) }} · {{ $event->service_type->label() }}">
                                        <span class="block truncate font-semibold text-emerald-900">{{ $event->ranch->name }}</span>
                                        <span class="hidden truncate text-emerald-800 sm:block">{{ $event->ranch->client->name }}</span>
                                        <span class="hidden truncate text-emerald-700 lg:block">{{ $event->ranch->municipality }} · {{ hectareas($event->quoted_hectares) }} · {{ $event->service_type->label() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Agenda del mes (útil en celular) --}}
        <div class="card mt-6">
            <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Agenda de {{ $cal['label'] }}</h2></div>
            <ul class="divide-y divide-slate-100">
                @forelse ($cal['monthEvents'] as $event)
                    <li wire:key="ag-{{ $event->id }}">
                        <a href="{{ route('opportunities.show', $event) }}" wire:navigate class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50">
                            <div class="w-12 shrink-0 text-center">
                                <p class="text-xs font-medium text-slate-500 uppercase">{{ $event->census_date->translatedFormat('D') }}</p>
                                <p class="text-xl font-semibold text-slate-900">{{ $event->census_date->day }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-slate-900">{{ $event->ranch->name }}</p>
                                <p class="truncate text-sm text-slate-500">{{ $event->ranch->client->name }} · {{ $event->ranch->municipality }} · {{ hectareas($event->quoted_hectares) }} · {{ $event->service_type->label() }}</p>
                            </div>
                            @if ($event->balance() > 0)
                                <x-badge tone="amber">Saldo {{ money($event->balance()) }}</x-badge>
                            @endif
                        </a>
                    </li>
                @empty
                    <li><x-empty icon="calendar" title="Sin censos programados este mes" /></li>
                @endforelse
            </ul>
        </div>
    @else
        <div class="card">
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60">
                        <tr>
                            <th class="table-th">Fecha</th>
                            <th class="table-th">Cliente</th>
                            <th class="table-th">Rancho</th>
                            <th class="table-th">Municipio</th>
                            <th class="table-th">Estado</th>
                            <th class="table-th">Cerca</th>
                            <th class="table-th">Servicio</th>
                            <th class="table-th text-right">Hectáreas</th>
                            <th class="table-th text-right">Total</th>
                            <th class="table-th text-right">Pagado</th>
                            <th class="table-th text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $o)
                            @php
                                $days = (int) today()->diffInDays($o->census_date, false);
                                $soon = $days >= 0 && $days <= $upcomingDays;
                                $missing = $o->missingInfo();
                            @endphp
                            <tr wire:key="cl-{{ $o->id }}" class="{{ $days < 0 ? 'bg-slate-50' : ($soon ? 'bg-emerald-50/50' : '') }} hover:bg-slate-50/70">
                                <td class="table-td">
                                    <a href="{{ route('opportunities.show', $o) }}" wire:navigate class="font-semibold text-slate-900 hover:text-brand-700">{{ fecha($o->census_date) }}</a>
                                    <p class="text-xs {{ $days < 0 ? 'text-slate-500' : ($soon ? 'font-medium text-emerald-700' : 'text-slate-400') }}">{{ $days < 0 ? 'Pasó: marcar realizado' : fecha_relativa($o->census_date) }}</p>
                                </td>
                                <td class="table-td">{{ $o->ranch->client->name }}</td>
                                <td class="table-td">
                                    <a href="{{ route('ranches.show', $o->ranch_id) }}" wire:navigate class="hover:text-brand-700">{{ $o->ranch->name }}</a>
                                    @if ($missing)<p class="text-xs text-amber-700" title="Información incompleta">Falta: {{ implode(', ', $missing) }}</p>@endif
                                </td>
                                <td class="table-td">{{ $o->ranch->municipality }}</td>
                                <td class="table-td">{{ $o->ranch->state?->name }}</td>
                                <td class="table-td">{{ $o->ranch->fence_type?->label() ?? '—' }}</td>
                                <td class="table-td">{{ $o->service_type->label() }}</td>
                                <td class="table-td text-right tabular-nums">{{ hectareas($o->quoted_hectares, false) }}</td>
                                <td class="table-td text-right tabular-nums">{{ $o->quotedTotal() !== null ? money($o->quotedTotal()) : '—' }}</td>
                                <td class="table-td text-right tabular-nums text-emerald-700">{{ money($o->paidTotal()) }}</td>
                                <td class="table-td text-right tabular-nums {{ $o->balance() > 0 ? 'font-semibold text-amber-700' : 'text-slate-400' }}">{{ $o->balance() !== null ? money($o->balance()) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11"><x-empty icon="calendar" title="Sin censos programados" description="Un servicio aparece aquí al estar Confirmado y tener fecha de censo." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <ul class="divide-y divide-slate-100 lg:hidden">
                @forelse ($items as $o)
                    @php
                        $days = (int) today()->diffInDays($o->census_date, false);
                        $soon = $days >= 0 && $days <= $upcomingDays;
                        $missing = $o->missingInfo();
                    @endphp
                    <li wire:key="cm-{{ $o->id }}" class="p-4 {{ $soon ? 'bg-emerald-50/50' : '' }}">
                        <a href="{{ route('opportunities.show', $o) }}" wire:navigate class="block">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-slate-900">{{ fecha($o->census_date) }} <span class="text-xs font-normal text-slate-500">· {{ $days < 0 ? 'pasó' : fecha_relativa($o->census_date) }}</span></p>
                                <span class="text-sm font-medium tabular-nums">{{ hectareas($o->quoted_hectares) }}</span>
                            </div>
                            <p class="mt-0.5 font-medium text-slate-800">{{ $o->ranch->name }}</p>
                            <p class="text-sm text-slate-500">{{ $o->ranch->client->name }} · {{ $o->ranch->location() }} · {{ $o->service_type->label() }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if ($o->balance() > 0)<x-badge tone="amber">Saldo {{ money($o->balance()) }}</x-badge>@endif
                                @if ($missing)<x-badge tone="rose">Falta: {{ implode(', ', $missing) }}</x-badge>@endif
                            </div>
                        </a>
                        <div class="mt-3 flex gap-2">
                            @if ($o->ranch->client->phone)<a href="{{ $o->ranch->client->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-icon name="whatsapp" class="size-4 text-emerald-600" /> WhatsApp</a>@endif
                            @if ($o->ranch->maps_url)<a href="{{ $o->ranch->maps_url }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-icon name="map-pin" class="size-4 text-blue-600" /> Maps</a>@endif
                            <button type="button" x-on:click="$dispatch('open-payment-form', { opportunityId: '{{ $o->id }}' })" class="btn-secondary btn-sm"><x-icon name="cash" class="size-4" /> Pago</button>
                        </div>
                    </li>
                @empty
                    <li><x-empty icon="calendar" title="Sin censos programados" /></li>
                @endforelse
            </ul>
        </div>
        <p class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
            <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-emerald-100 ring-1 ring-emerald-200"></span> Próximos {{ $upcomingDays }} días</span>
            <span class="text-amber-700">Saldo en ámbar = pago pendiente</span>
            <span class="text-amber-700">«Falta» = información incompleta</span>
        </p>
    @endif
</div>
