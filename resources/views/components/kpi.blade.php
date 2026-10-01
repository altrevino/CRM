@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'brand', 'href' => null])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'blue' => 'bg-blue-50 text-blue-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->class(['card flex flex-col gap-3 p-4 sm:p-5', 'transition hover:border-brand-200 hover:shadow-md' => $href]) }}>
    <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-medium tracking-wide text-slate-500 uppercase sm:text-[13px] sm:normal-case sm:tracking-normal">{{ $label }}</span>
        @if ($icon)
            <span class="hidden rounded-lg p-1.5 sm:inline-flex {{ $tones[$tone] }}"><x-icon :name="$icon" class="size-4" /></span>
        @endif
    </div>
    <div class="truncate text-lg font-semibold tracking-tight text-slate-900 tabular-nums sm:text-xl 2xl:text-2xl" title="{{ $value }}">{{ $value }}</div>
    @if ($hint)
        <div class="text-xs text-slate-500">{{ $hint }}</div>
    @endif
</{{ $tag }}>
