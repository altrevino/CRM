{{-- Las clases lg:collapsed:* solo aplican al menú de escritorio plegado; el menú móvil no se pliega. --}}
<div class="flex h-16 shrink-0 items-center gap-2.5 px-4 lg:collapsed:justify-center lg:collapsed:px-0">
    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm">
        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4 7 17M17 7l1.4-1.4" stroke-linecap="round"/></svg>
    </span>
    <div class="leading-tight lg:collapsed:hidden">
        <p class="text-sm font-semibold text-slate-900">Espectro</p>
        <p class="text-xs text-slate-500">Soluciones · CRM</p>
    </div>
</div>
<nav class="flex-1 overflow-y-auto px-2.5 py-3 lg:collapsed:px-2">
    <ul class="space-y-0.5">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <li>
                <a href="{{ route($item['route']) }}" wire:navigate title="{{ $item['label'] }}"
                   class="group relative flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm font-medium transition lg:collapsed:justify-center lg:collapsed:px-0 {{ $active ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <x-icon :name="$item['icon']" class="size-5 shrink-0 {{ $active ? 'text-brand-600' : 'text-slate-400 group-hover:text-slate-500' }}" />
                    <span class="flex-1 truncate lg:collapsed:hidden">{{ $item['label'] }}</span>
                    @if ($item['route'] === 'tasks.index' && $overdueCount)
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700 lg:collapsed:absolute lg:collapsed:top-0.5 lg:collapsed:right-0.5 lg:collapsed:px-1.5 lg:collapsed:text-[10px]" title="Seguimientos vencidos">{{ $overdueCount }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
<div class="border-t border-slate-100 p-2.5">
    <div class="flex items-center gap-2.5 rounded-lg px-1.5 py-1.5 lg:collapsed:flex-col lg:collapsed:px-0">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600" title="{{ auth()->user()->name }}">{{ auth()->user()->initials() }}</span>
        <div class="min-w-0 flex-1 leading-tight lg:collapsed:hidden">
            <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost p-2" title="Cerrar sesión" aria-label="Cerrar sesión">
                <x-icon name="logout" class="size-5" />
            </button>
        </form>
    </div>
    <button type="button"
            x-on:click="document.documentElement.classList.toggle('sidebar-collapsed'); try { localStorage.setItem('sidebar-collapsed', document.documentElement.classList.contains('sidebar-collapsed') ? '1' : '0') } catch (e) {}"
            class="mt-1 hidden w-full items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-800 lg:flex lg:collapsed:justify-center lg:collapsed:px-0"
            title="Plegar o expandir el menú" aria-label="Plegar o expandir el menú">
        <x-icon name="chevron-left" class="size-4 shrink-0 lg:collapsed:rotate-180" />
        <span class="lg:collapsed:hidden">Plegar menú</span>
    </button>
</div>
