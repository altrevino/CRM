{{-- Modal controlado por una propiedad booleana pública del componente Livewire padre ($showModal por defecto). --}}
@props(['title', 'subtitle' => null, 'size' => 'lg', 'model' => 'showModal', 'close' => null])
@php
    $width = ['sm' => 'sm:max-w-md', 'md' => 'sm:max-w-lg', 'lg' => 'sm:max-w-2xl', 'xl' => 'sm:max-w-4xl'][$size];
    $close ??= '$wire.'.$model.' = false';
@endphp
<div
    x-data
    x-show="$wire.{{ $model }}"
    x-cloak
    x-on:keydown.escape.window="if ($wire.{{ $model }}) { {{ $close }} }"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div x-show="$wire.{{ $model }}" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px]" x-on:click="{{ $close }}"></div>
    <div class="flex min-h-full items-end justify-center sm:items-center sm:p-6">
        <div
            x-show="$wire.{{ $model }}"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="relative w-full {{ $width }} rounded-t-2xl bg-white shadow-xl sm:rounded-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" class="btn-ghost -mr-2 p-1.5" x-on:click="{{ $close }}" aria-label="Cerrar">
                    <x-icon name="x" />
                </button>
            </div>
            <div class="max-h-[75vh] overflow-y-auto px-5 py-5 sm:px-6">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3.5 sm:flex-row sm:justify-end sm:px-6 rounded-b-2xl">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
