<!DOCTYPE html>
<html lang="es-MX" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1f625b">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full">
@php
    $nav = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
        ['route' => 'pipeline', 'match' => 'pipeline', 'label' => 'Pipeline', 'icon' => 'kanban'],
        ['route' => 'clients.index', 'match' => 'clients.*', 'label' => 'Clientes', 'icon' => 'users'],
        ['route' => 'ranches.index', 'match' => 'ranches.*', 'label' => 'Ranchos', 'icon' => 'map'],
        ['route' => 'quotes.index', 'match' => 'quotes.*', 'label' => 'Cotizaciones', 'icon' => 'document'],
        ['route' => 'payments.index', 'match' => 'payments.*', 'label' => 'Pagos', 'icon' => 'cash'],
        ['route' => 'census.index', 'match' => 'census.*', 'label' => 'Censos programados', 'icon' => 'calendar'],
        ['route' => 'tasks.index', 'match' => 'tasks.*', 'label' => 'Seguimientos', 'icon' => 'check'],
    ];
    if (auth()->user()->can('manage-settings')) {
        $nav[] = ['route' => 'settings.general', 'match' => 'settings.*', 'label' => 'Configuración', 'icon' => 'cog'];
    }
    $overdueCount = \App\Models\Task::overdue()->count();
@endphp
<div x-data="{ sidebar: false }" class="min-h-full">
    {{-- Sidebar móvil --}}
    <div x-show="sidebar" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-slate-900/40" x-on:click="sidebar = false"></div>
        <div x-show="sidebar" x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             class="fixed inset-y-0 left-0 flex w-72 flex-col bg-white shadow-xl">
            @include('layouts.partials.sidebar')
        </div>
    </div>

    {{-- Sidebar escritorio --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white lg:flex">
        @include('layouts.partials.sidebar')
    </aside>

    <div class="lg:pl-64">
        {{-- Barra superior --}}
        <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/85 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button type="button" class="btn-ghost -ml-2 p-2 lg:hidden" x-on:click="sidebar = true" aria-label="Abrir menú">
                    <x-icon name="menu" />
                </button>
                <div class="min-w-0 flex-1">
                    <livewire:global-search />
                </div>
                <a href="{{ route('opportunities.create') }}" wire:navigate class="btn-primary">
                    <x-icon name="plus" class="size-4" />
                    <span class="hidden sm:inline">Nuevo servicio</span>
                </a>
            </div>
        </header>

        <main class="px-4 pt-6 pb-28 sm:px-6 lg:px-8 lg:pb-12">
            <div class="mx-auto max-w-7xl">
                {{ $slot }}
            </div>
        </main>
    </div>

    {{-- Navegación inferior móvil: lo más usado en campo --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
        <div class="grid grid-cols-5">
            @foreach ([
                ['dashboard', 'dashboard', 'Inicio', 'home'],
                ['pipeline', 'pipeline', 'Pipeline', 'kanban'],
                ['census.index', 'census.*', 'Censos', 'calendar'],
                ['tasks.index', 'tasks.*', 'Seguimientos', 'check'],
                ['clients.index', 'clients.*', 'Clientes', 'users'],
            ] as [$route, $match, $label, $icon])
                <a href="{{ route($route) }}" wire:navigate
                   class="relative flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium {{ request()->routeIs($match) ? 'text-brand-700' : 'text-slate-500' }}">
                    <x-icon :name="$icon" class="size-6" />
                    {{ $label }}
                    @if ($route === 'tasks.index' && $overdueCount)
                        <span class="absolute top-1 right-[calc(50%-1.25rem)] rounded-full bg-rose-500 px-1.5 text-[10px] leading-4 font-semibold text-white">{{ $overdueCount }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Formularios modales reutilizables (se abren por evento desde cualquier pantalla) --}}
    <livewire:forms.client-form />
    <livewire:forms.ranch-form />
    <livewire:forms.opportunity-form />
    <livewire:forms.quote-form />
    <livewire:forms.payment-form />
    <livewire:forms.task-form />

    {{-- Notificaciones --}}
    <div
        x-data="{ toasts: [], add(e) { const id = Date.now() + Math.random(); this.toasts.push({ id, message: e.detail.message ?? e.detail[0]?.message ?? e.detail, type: e.detail.type ?? e.detail[0]?.type ?? 'success' }); setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 4000) } }"
        x-init="@if (session('notify')) $nextTick(() => add({ detail: { message: @js(session('notify')) } })) @endif"
        x-on:notify.window="add($event)"
        class="pointer-events-none fixed inset-x-0 bottom-20 z-[60] flex flex-col items-center gap-2 px-4 sm:bottom-6 sm:items-end"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition class="pointer-events-auto flex max-w-sm items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium shadow-lg ring-1"
                 :class="toast.type === 'error' ? 'bg-rose-600 text-white ring-rose-700' : 'bg-slate-900 text-white ring-slate-800'">
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>
</div>
@livewireScriptConfig
</body>
</html>
