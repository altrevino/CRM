{{-- Lista de servicios con etapa, fechas y finanzas. Variables: $opportunities, $showRanch (bool) --}}
<ul class="divide-y divide-slate-100">
    @forelse ($opportunities as $opp)
        <li wire:key="opp-{{ $opp->id }}">
            <a href="{{ route('opportunities.show', $opp) }}" wire:navigate class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium text-slate-900">{{ $opp->title() }}</p>
                        <x-stage-badge :stage="$opp->stage" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($showRanch ?? false){{ $opp->ranch->name }} · @endif
                        {{ hectareas($opp->quoted_hectares) }}
                        @if ($opp->census_date) · Censo {{ fecha($opp->census_date) }}
                        @elseif ($opp->tentative_census_date) · Tentativo {{ fecha($opp->tentative_census_date) }}
                        @endif
                    </p>
                    @if ($opp->species->isNotEmpty())
                        <p class="mt-1 truncate text-xs text-slate-400">{{ $opp->species->pluck('name')->implode(', ') }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-6 sm:w-64 sm:justify-end">
                    @if ($opp->acceptedTotal() !== null)
                        <x-progress class="w-full" :paid="$opp->paidTotal()" :total="$opp->acceptedTotal()" compact />
                    @elseif ($opp->quotedTotal() !== null)
                        <div class="text-right text-sm"><p class="font-medium tabular-nums text-slate-800">{{ money($opp->quotedTotal()) }}</p><p class="text-xs text-slate-500">cotizado</p></div>
                    @else
                        <span class="text-xs text-slate-400">Sin cotización</span>
                    @endif
                </div>
            </a>
        </li>
    @empty
        <li><x-empty icon="briefcase" title="Sin servicios registrados" /></li>
    @endforelse
</ul>
