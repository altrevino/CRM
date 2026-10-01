{{-- Lista de tareas con acción de completar. Requiere el trait ManagesTasks. Variables: $tasks, $showContext --}}
<ul class="divide-y divide-slate-100">
    @forelse ($tasks as $task)
        @php
            $overdue = $task->isOverdue();
            $today = $task->isDueToday();
        @endphp
        <li wire:key="t-{{ $task->id }}" class="flex items-start gap-3 px-5 py-3 {{ $overdue ? 'bg-rose-50/60' : '' }}">
            @if ($task->status === \App\Enums\TaskStatus::Pending)
                <button type="button" wire:click="completeTask('{{ $task->id }}')" class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2 {{ $overdue ? 'border-rose-400' : 'border-slate-300' }} text-transparent transition hover:border-emerald-500 hover:text-emerald-500" title="Marcar como completada" aria-label="Completar tarea">
                    <x-icon name="check-simple" class="size-3" />
                </button>
            @else
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white"><x-icon name="check-simple" class="size-3" /></span>
            @endif
            <div class="min-w-0 flex-1">
                <button type="button" x-on:click="$dispatch('open-task-form', { taskId: '{{ $task->id }}' })" class="text-left text-sm font-medium text-slate-800 hover:text-brand-700 {{ $task->status !== \App\Enums\TaskStatus::Pending ? 'line-through text-slate-400' : '' }}">{{ $task->title }}</button>
                <p class="mt-0.5 text-xs {{ $overdue ? 'font-medium text-rose-600' : ($today ? 'font-medium text-amber-700' : 'text-slate-500') }}">
                    {{ fecha($task->due_date) }} · {{ fecha_relativa($task->due_date) }}
                    @if ($task->assignee?->exists) · {{ $task->assignee->name }}@endif
                </p>
                @if (($showContext ?? false) && $task->opportunity)
                    <a href="{{ route('opportunities.show', $task->opportunity_id) }}" wire:navigate class="mt-0.5 block truncate text-xs text-brand-700 hover:underline">{{ $task->opportunity->ranch?->name }} · {{ $task->opportunity->title() }}</a>
                @elseif (($showContext ?? false) && $task->client_id && $task->relationLoaded('client') && $task->client)
                    <a href="{{ route('clients.show', $task->client_id) }}" wire:navigate class="mt-0.5 block truncate text-xs text-brand-700 hover:underline">{{ $task->client->name }}</a>
                @endif
                @if ($task->description)
                    <p class="mt-1 text-xs whitespace-pre-line text-slate-500">{{ $task->description }}</p>
                @endif
            </div>
            @if ($task->status === \App\Enums\TaskStatus::Pending)
                <button type="button" wire:click="cancelTask('{{ $task->id }}')" wire:confirm="¿Cancelar la tarea «{{ $task->title }}»?" class="shrink-0 rounded p-1 text-slate-300 hover:bg-slate-100 hover:text-slate-500" title="Cancelar tarea" aria-label="Cancelar tarea">
                    <x-icon name="x" class="size-4" />
                </button>
            @endif
        </li>
    @empty
        <li><x-empty icon="check" title="Sin tareas pendientes" description="Programa el siguiente seguimiento para no perder el contacto." /></li>
    @endforelse
</ul>
