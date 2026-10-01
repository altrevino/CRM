@props(['stage', 'size' => 'md'])
<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full font-medium ring-1 ring-inset whitespace-nowrap',
    $stage->badgeClasses(),
    'px-2.5 py-0.5 text-xs' => $size === 'md',
    'px-3 py-1 text-sm' => $size === 'lg',
]) }}>{{ $stage->label() }}</span>
