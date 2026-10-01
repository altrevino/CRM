<nav class="mb-6 -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
    <div class="flex min-w-max gap-1 border-b border-slate-200">
        @foreach (['settings.general' => 'General', 'settings.users' => 'Usuarios', 'settings.species' => 'Especies', 'settings.payment-methods' => 'Formas de pago'] as $route => $label)
            <a href="{{ route($route) }}" wire:navigate class="-mb-px border-b-2 px-3 py-2.5 text-sm font-medium {{ request()->routeIs($route) ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">{{ $label }}</a>
        @endforeach
    </div>
</nav>
