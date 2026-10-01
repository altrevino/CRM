@props(['tone' => 'slate'])
@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-200',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap', $tones[$tone] ?? $tone]) }}>{{ $slot }}</span>
