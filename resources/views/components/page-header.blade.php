@props(['title', 'subtitle' => null, 'breadcrumbs' => []])
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($breadcrumbs)
            <nav class="mb-2 flex flex-wrap items-center gap-1 text-sm text-slate-500" aria-label="Ruta">
                @foreach ($breadcrumbs as $label => $url)
                    @if ($url)
                        <a href="{{ $url }}" wire:navigate class="hover:text-slate-800">{{ $label }}</a>
                    @else
                        <span class="text-slate-700">{{ $label }}</span>
                    @endif
                    @unless ($loop->last)
                        <x-icon name="chevron-right" class="size-3.5 text-slate-400" />
                    @endunless
                @endforeach
            </nav>
        @endif
        <h1 class="truncate text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
        @if ($subtitle)
            <div class="mt-1 text-sm text-slate-500">{{ $subtitle }}</div>
        @endif
        {{ $meta ?? '' }}
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
