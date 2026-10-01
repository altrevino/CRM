<div>
    <x-page-header title="Seguimientos" subtitle="Tareas y recordatorios internos">
        <x-slot:actions>
            <button type="button" x-on:click="$dispatch('open-task-form')" class="btn-primary"><x-icon name="plus" class="size-4" /> Nueva tarea</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="inline-flex rounded-lg bg-slate-100 p-1">
            <button type="button" wire:click="$set('scope', 'pendientes')" class="rounded-md px-3 py-1.5 text-sm font-medium {{ $scope === 'pendientes' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Pendientes</button>
            <button type="button" wire:click="$set('scope', 'cerradas')" class="rounded-md px-3 py-1.5 text-sm font-medium {{ $scope === 'cerradas' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Cerradas</button>
        </div>
        <div class="relative sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
            <input type="search" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Tarea, cliente o rancho…">
        </div>
        <x-select wire:model.live="assignee" :options="$users" placeholder="Todos los responsables" class="sm:w-56" />
    </div>

    @if ($scope === 'pendientes')
        @php $groups = $this->groups; @endphp
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @foreach ([
                'overdue' => ['Vencidos', 'border-rose-300 bg-rose-50/40', 'text-rose-700', 'bg-rose-100 text-rose-700'],
                'today' => ['Hoy', 'border-amber-300', 'text-amber-800', 'bg-amber-100 text-amber-800'],
                'upcoming' => ['Próximos', 'border-slate-200', 'text-slate-800', 'bg-slate-100 text-slate-600'],
            ] as $key => [$label, $border, $text, $pill])
                <section class="card overflow-hidden border-t-4 {{ $border }}">
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <h2 class="font-semibold {{ $text }}">{{ $label }}</h2>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $pill }}">{{ $groups[$key]->count() }}</span>
                    </div>
                    <div class="border-t border-slate-100">
                        @include('livewire.partials.task-list', ['tasks' => $groups[$key], 'showContext' => true])
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="card">
            <ul class="divide-y divide-slate-100">
                @forelse ($closed as $task)
                    <li wire:key="ct-{{ $task->id }}" class="flex items-start justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-500 line-through">{{ $task->title }}</p>
                            <p class="text-xs text-slate-400">
                                <x-badge :tone="$task->status->badgeClasses()">{{ $task->status->label() }}</x-badge>
                                Vencía {{ fecha($task->due_date) }}
                                @if ($task->completed_at) · Completada {{ fecha_hora($task->completed_at) }} por {{ $task->completer?->name }}@endif
                                @if ($task->opportunity) · <a href="{{ route('opportunities.show', $task->opportunity_id) }}" wire:navigate class="hover:underline">{{ $task->opportunity->ranch?->name }}</a>@endif
                            </p>
                        </div>
                        <button type="button" wire:click="reopenTask('{{ $task->id }}')" class="btn-ghost btn-sm shrink-0">Reabrir</button>
                    </li>
                @empty
                    <li><x-empty icon="check" title="Sin tareas cerradas" /></li>
                @endforelse
            </ul>
            <div class="border-t border-slate-100 px-4 py-3">{{ $closed->links() }}</div>
        </div>
    @endif
</div>
