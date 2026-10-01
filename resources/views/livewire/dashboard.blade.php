<div>
    @php
        $k = $this->kpis;
        $c = $this->charts;
        $ind = $c['indicators'];
        $pct = fn ($v) => $v === null ? '—' : number_format($v, 0).'%';
    @endphp

    <x-page-header title="Hola, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}" :subtitle="ucfirst(today()->translatedFormat('l j \\d\\e F \\d\\e Y'))" />

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <x-kpi label="Ventas confirmadas" :value="money($k['sales'])" icon="cash" tone="emerald" :hint="$k['salesCount'].' servicios'" />
        <x-kpi label="Hectáreas confirmadas" :value="hectareas($k['hectares'])" icon="map" tone="brand" hint="Confirmados y realizados" />
        <x-kpi label="Censos programados" :value="$k['scheduled']" icon="calendar" tone="blue" :href="route('census.index')" hint="Fechas futuras" />
        <x-kpi label="Pipeline" :value="money($k['pipeline'])" icon="kanban" tone="slate" :href="route('pipeline')" :hint="$k['openCount'].' oportunidades abiertas'" />
        <x-kpi label="Saldo pendiente" :value="money($k['balance'])" icon="alert" :tone="$k['balance'] > 0 ? 'amber' : 'slate'" :href="route('census.index', ['vista' => 'lista'])" hint="Por cobrar de ventas" />
        <x-kpi label="Seguimientos vencidos" :value="$k['overdue']" icon="clock" :tone="$k['overdue'] ? 'rose' : 'slate'" :href="route('tasks.index')" :hint="$k['overdue'] ? 'Atender hoy' : 'Al día'" />
    </div>

    {{-- Requieren atención --}}
    <section class="mt-8">
        <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-slate-900"><x-icon name="flag" class="size-5 text-rose-500" /> Requieren atención</h2>
        @if ($this->attention->isEmpty())
            <div class="card flex items-center gap-3 p-5 text-sm text-slate-600"><span class="rounded-full bg-emerald-100 p-2 text-emerald-700"><x-icon name="check-simple" class="size-4" /></span> Todo al día. No hay pendientes críticos.</div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->attention as $section)
                    @php
                        $tone = ['rose' => 'border-rose-400', 'amber' => 'border-amber-400', 'blue' => 'border-blue-400', 'slate' => 'border-slate-400'][$section['tone']];
                        $pill = ['rose' => 'bg-rose-100 text-rose-700', 'amber' => 'bg-amber-100 text-amber-800', 'blue' => 'bg-blue-100 text-blue-700', 'slate' => 'bg-slate-100 text-slate-700'][$section['tone']];
                    @endphp
                    <div wire:key="att-{{ $section['key'] }}" class="card overflow-hidden border-l-4 {{ $tone }}">
                        <div class="flex items-center justify-between gap-2 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">{{ $section['label'] }}</h3>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $pill }}">{{ $section['count'] }}</span>
                        </div>
                        <ul class="divide-y divide-slate-100 border-t border-slate-100">
                            @foreach ($section['items'] as $item)
                                <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-800">{{ $item['title'] }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $item['subtitle'] }}</p>
                                        <p class="truncate text-xs font-medium text-slate-600">{{ $item['meta'] }}</p>
                                    </div>
                                    <a href="{{ $item['url'] }}" wire:navigate class="btn-secondary btn-sm shrink-0">{{ $item['action'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Seguimientos --}}
    <section class="mt-8">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Seguimientos</h2>
            <a href="{{ route('tasks.index') }}" wire:navigate class="link text-sm">Ver todos</a>
        </div>
        @php $f = $this->followUps; @endphp
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ([
                'overdue' => ['Vencidos', 'border-rose-400 bg-rose-50/30', 'text-rose-700'],
                'today' => ['Hoy', 'border-amber-400', 'text-amber-800'],
                'upcoming' => ['Próximos 14 días', 'border-slate-300', 'text-slate-800'],
            ] as $key => [$label, $border, $text])
                <div class="card overflow-hidden border-t-4 {{ $border }}">
                    <div class="flex items-center justify-between px-5 py-3">
                        <h3 class="font-semibold {{ $text }}">{{ $label }}</h3>
                        <span class="text-sm font-semibold tabular-nums {{ $text }}">{{ $f[$key]->count() }}</span>
                    </div>
                    <div class="border-t border-slate-100">
                        @include('livewire.partials.task-list', ['tasks' => $f[$key], 'showContext' => true])
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Gráficas --}}
    <section class="mt-8 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card p-5">
            <h3 class="font-semibold text-slate-900">Oportunidades por etapa</h3>
            <p class="text-xs text-slate-500">Todas las oportunidades registradas</p>
            <div class="mt-4 h-64" wire:ignore x-data="barChart(@js(['labels' => $c['stages']['labels'], 'values' => $c['stages']['values'], 'colors' => $c['stages']['colors'], 'horizontal' => true, 'format' => 'number', 'links' => array_fill(0, count($c['stages']['labels']), route('pipeline'))]))">
                <canvas x-ref="canvas" role="img" aria-label="Oportunidades por etapa"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-slate-900">Ventas por mes</h3>
                    <p class="text-xs text-slate-500">Cotizaciones aceptadas (Confirmado o Censo realizado), por mes de aceptación</p>
                </div>
                <div class="flex items-center gap-1 text-sm">
                    <button type="button" wire:click="setYear({{ $year - 1 }})" class="btn-ghost p-1" aria-label="Año anterior"><x-icon name="chevron-left" class="size-4" /></button>
                    <span class="font-medium tabular-nums">{{ $year }}</span>
                    <button type="button" wire:click="setYear({{ $year + 1 }})" class="btn-ghost p-1" aria-label="Año siguiente"><x-icon name="chevron-right" class="size-4" /></button>
                </div>
            </div>
            <div class="mt-4 h-64" wire:key="sales-{{ $year }}" wire:ignore x-data="barChart(@js(['labels' => $c['sales']['labels'], 'values' => $c['sales']['values'], 'format' => 'money']))">
                <canvas x-ref="canvas" role="img" aria-label="Ventas por mes {{ $year }}"></canvas>
            </div>
            <p class="mt-2 text-right text-xs text-slate-500">Total {{ $year }}: <span class="font-semibold text-slate-700">{{ money(array_sum($c['sales']['values'])) }}</span></p>
        </div>

        <div class="card p-5">
            <h3 class="font-semibold text-slate-900">Hectáreas confirmadas por mes</h3>
            <p class="text-xs text-slate-500">Por mes de fecha de censo · {{ $year }}</p>
            <div class="mt-4 h-56" wire:key="ha-{{ $year }}" wire:ignore x-data="barChart(@js(['labels' => $c['hectares']['labels'], 'values' => $c['hectares']['values'], 'format' => 'number', 'unit' => 'ha', 'colors' => '#4cb3a4']))">
                <canvas x-ref="canvas" role="img" aria-label="Hectáreas por mes {{ $year }}"></canvas>
            </div>
            <p class="mt-2 text-right text-xs text-slate-500">Total {{ $year }}: <span class="font-semibold text-slate-700">{{ hectareas(array_sum($c['hectares']['values'])) }}</span></p>
        </div>

        <div class="card p-5">
            <h3 class="mb-4 font-semibold text-slate-900">Indicadores</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Ticket promedio</dt><dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ $ind['average_ticket'] !== null ? money($ind['average_ticket']) : '—' }}</dd></div>
                <div><dt class="text-slate-500">Promedio ha / censo</dt><dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ $ind['average_hectares'] !== null ? hectareas($ind['average_hectares']) : '—' }}</dd></div>
                <div><dt class="text-slate-500" title="Ganadas ÷ (ganadas + perdidas)">Tasa de cierre</dt><dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ $pct($ind['win_rate']) }}</dd><dd class="text-xs text-slate-400">{{ $ind['won'] }} ganadas · {{ $ind['lost'] }} perdidas</dd></div>
                <div><dt class="text-slate-500" title="Ganadas ÷ todas las oportunidades">Conversión global</dt><dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ $pct($ind['overall_conversion']) }}</dd><dd class="text-xs text-slate-400">de {{ $ind['total'] }} oportunidades</dd></div>
                <div><dt class="text-slate-500">Censos realizados</dt><dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ $ind['completed_census'] }}</dd></div>
                <div><dt class="text-slate-500">Censos programados</dt><dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ $ind['scheduled_census'] }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-slate-400">Fórmulas en docs/METRICAS.md.</p>
        </div>

        <div class="card p-5">
            <h3 class="font-semibold text-slate-900">Oportunidades por estado</h3>
            <p class="text-xs text-slate-500">Todas las etapas</p>
            <div class="mt-4 h-56" wire:ignore x-data="barChart(@js(['labels' => $c['states']['labels'], 'values' => $c['states']['values'], 'horizontal' => true, 'format' => 'number']))">
                <canvas x-ref="canvas" role="img" aria-label="Oportunidades por estado"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <h3 class="font-semibold text-slate-900">Oportunidades por municipio</h3>
            <p class="text-xs text-slate-500">Top 8 · clic para ver los ranchos</p>
            <div class="mt-4 h-56" wire:ignore x-data="barChart(@js(['labels' => $c['municipalities']['labels'], 'values' => $c['municipalities']['values'], 'links' => $c['municipalities']['links'], 'horizontal' => true, 'format' => 'number']))">
                <canvas x-ref="canvas" role="img" aria-label="Oportunidades por municipio"></canvas>
            </div>
        </div>
    </section>

    {{-- Próximos censos y actividad --}}
    <section class="mt-8 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                <h3 class="font-semibold text-slate-900">Próximos censos</h3>
                <a href="{{ route('census.index') }}" wire:navigate class="link text-sm">Calendario</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($this->upcomingCensus as $o)
                    <li wire:key="uc-{{ $o->id }}">
                        <a href="{{ route('opportunities.show', $o) }}" wire:navigate class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50">
                            <div class="w-12 shrink-0 rounded-lg bg-emerald-50 py-1 text-center ring-1 ring-emerald-200">
                                <p class="text-[10px] font-semibold text-emerald-700 uppercase">{{ $o->census_date->translatedFormat('M') }}</p>
                                <p class="text-lg leading-tight font-semibold text-emerald-900">{{ $o->census_date->day }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-slate-900">{{ $o->ranch->name }}</p>
                                <p class="truncate text-sm text-slate-500">{{ $o->ranch->client->name }} · {{ $o->ranch->municipality }} · {{ hectareas($o->quoted_hectares) }}</p>
                            </div>
                            <span class="text-xs font-medium {{ $o->balance() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $o->balance() > 0 ? 'Saldo '.money($o->balance()) : ($o->acceptedTotal() ? 'Pagado' : '') }}</span>
                        </a>
                    </li>
                @empty
                    <li><x-empty icon="calendar" title="Sin censos programados" /></li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-3.5"><h3 class="font-semibold text-slate-900">Última actividad</h3></div>
            <div class="p-5">
                @include('livewire.partials.timeline', ['logs' => $this->recentActivity, 'showContext' => true])
            </div>
        </div>
    </section>
</div>
