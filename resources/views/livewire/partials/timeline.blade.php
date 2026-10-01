{{-- Línea de tiempo de actividad. Variables: $logs, $showContext --}}
@if ($logs->isEmpty())
    <x-empty icon="clock" title="Sin actividad registrada" />
@else
    <ol class="relative space-y-5 before:absolute before:top-2 before:bottom-2 before:left-4 before:w-px before:bg-slate-200">
        @foreach ($logs as $log)
            <li wire:key="log-{{ $log->id }}" class="relative flex gap-3">
                <span class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full ring-4 ring-white {{ $log->event->tone() }}">
                    <x-icon :name="$log->event->icon()" class="size-4" />
                </span>
                <div class="min-w-0 flex-1 pt-0.5">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-2">
                        <p class="text-sm font-medium text-slate-800">{{ $log->event->label() }}</p>
                        <time class="text-xs text-slate-400" datetime="{{ $log->created_at->toIso8601String() }}">{{ fecha_hora($log->created_at) }}</time>
                    </div>
                    @if ($log->event === \App\Enums\ActivityEvent::CommentAdded)
                        <p class="mt-1 rounded-lg rounded-tl-none bg-amber-50 px-3 py-2 text-sm whitespace-pre-line text-slate-700 ring-1 ring-amber-100">{{ $log->subject?->body ?? $log->description }}</p>
                    @else
                        <p class="mt-0.5 text-sm text-slate-600">{{ $log->description }}</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-400">
                        {{ $log->user->name }}
                        @if (($showContext ?? false) && $log->opportunity_id)
                            · <a href="{{ route('opportunities.show', $log->opportunity_id) }}" wire:navigate class="hover:text-brand-700 hover:underline">{{ $log->ranch?->name ?? 'Servicio' }}</a>
                        @endif
                    </p>
                </div>
            </li>
        @endforeach
    </ol>
@endif
