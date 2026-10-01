@props(['paid' => 0, 'total' => null, 'compact' => false])
@php
    $percent = $total ? min(100, (int) floor($paid / $total * 100)) : 0;
    $balance = $total ? max(0, $total - $paid) : null;
    $color = $percent >= 100 ? 'bg-emerald-500' : ($percent > 0 ? 'bg-brand-500' : 'bg-slate-300');
@endphp
<div {{ $attributes }}>
    @if ($total === null)
        <p class="text-sm text-slate-500">Sin cotización aceptada.</p>
    @else
        <div class="flex items-baseline justify-between gap-2 text-sm">
            <span class="font-semibold text-slate-900 tabular-nums">{{ money($paid) }} <span class="font-normal text-slate-500">/ {{ money($total) }}</span></span>
            <span class="font-medium tabular-nums {{ $percent >= 100 ? 'text-emerald-700' : 'text-slate-600' }}">{{ $percent }}% pagado</span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full {{ $color }} transition-all" style="width: {{ $percent }}%"></div>
        </div>
        @unless ($compact)
            <p class="mt-1.5 text-xs {{ $balance > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                {{ $balance > 0 ? 'Saldo: '.money($balance) : 'Liquidado' }}
            </p>
        @endunless
    @endif
</div>
