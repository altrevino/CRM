<div>
    <x-page-header :title="$ranch->name" :breadcrumbs="['Clientes' => route('clients.index'), $ranch->client->name => route('clients.show', $ranch->client_id), $ranch->name => null]">
        <x-slot:meta>
            <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500"><x-icon name="map-pin" class="size-4" /> {{ $ranch->location() ?: 'Sin ubicación' }}</p>
        </x-slot:meta>
        <x-slot:actions>
            @if ($ranch->maps_url)
                <a href="{{ $ranch->maps_url }}" target="_blank" rel="noopener" class="btn bg-blue-600 text-white shadow-sm hover:bg-blue-700"><x-icon name="map-pin" class="size-4" /> Google Maps</a>
            @endif
            @if ($ranch->client->phone)
                <a href="{{ $ranch->client->whatsappUrl() }}" target="_blank" rel="noopener" class="btn bg-emerald-600 text-white shadow-sm hover:bg-emerald-700"><x-icon name="whatsapp" class="size-4" /><span class="hidden sm:inline">WhatsApp</span></a>
            @endif
            <a href="{{ route('opportunities.create', ['rancho' => $ranch->id]) }}" wire:navigate class="btn-primary"><x-icon name="plus" class="size-4" /> Nuevo servicio</a>
            <button type="button" x-on:click="$dispatch('open-ranch-form', { id: '{{ $ranch->id }}' })" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</button>
            @can('delete', $ranch)
                <button type="button" wire:click="delete" wire:confirm="¿Eliminar el rancho {{ $ranch->name }}?" class="btn-danger"><x-icon name="trash" class="size-4" /></button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">Información general</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Cliente</dt><dd class="mt-0.5 font-medium"><a href="{{ route('clients.show', $ranch->client_id) }}" wire:navigate class="link">{{ $ranch->client->name }}</a></dd></div>
                    <div><dt class="text-slate-500">Teléfono</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->client->formattedPhone() ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Municipio / Estado</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->location() ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Superficie total</dt><dd class="mt-0.5 font-medium text-slate-800">{{ hectareas($ranch->total_hectares) }}</dd></div>
                    <div><dt class="text-slate-500">Tipo de cerca</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->fence_type?->label() ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Km desde Monterrey (redondo)</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->km_round_trip !== null ? number_format((float) $ranch->km_round_trip, 0).' km' : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Alta</dt><dd class="mt-0.5 text-slate-800">{{ fecha($ranch->created_at) }}</dd></div>
                    <div><dt class="text-slate-500">Última modificación</dt><dd class="mt-0.5 text-slate-800">{{ fecha($ranch->updated_at) }}</dd></div>
                </dl>
                @if ($ranch->notes)
                    <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm whitespace-pre-line text-slate-700"><p class="mb-1 font-medium text-slate-900">Notas</p>{{ $ranch->notes }}</div>
                @endif
            </section>

            @php $groups = $this->groups; @endphp
            <section class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="font-semibold text-slate-900">Censos programados</h2>
                    <x-badge tone="emerald">{{ $groups['scheduled']->count() }}</x-badge>
                </div>
                @include('livewire.partials.opportunity-list', ['opportunities' => $groups['scheduled']])
            </section>

            <section class="card">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Servicios en proceso</h2></div>
                @include('livewire.partials.opportunity-list', ['opportunities' => $groups['active']])
            </section>

            <section class="card">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Censos realizados</h2></div>
                @include('livewire.partials.opportunity-list', ['opportunities' => $groups['done']])
            </section>

            @if ($groups['lost']->isNotEmpty())
                <section class="card">
                    <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Perdidos</h2></div>
                    @include('livewire.partials.opportunity-list', ['opportunities' => $groups['lost']])
                </section>
            @endif

            <section class="card">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Cotizaciones históricas</h2></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($this->quotes as $quote)
                        <li wire:key="q-{{ $quote->id }}">
                            <a href="{{ route('opportunities.show', [$quote->opportunity_id, 'tab' => 'cotizaciones']) }}" wire:navigate class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $quote->number }}</p>
                                    <p class="text-xs text-slate-500">{{ fecha($quote->issued_at) }} · {{ hectareas($quote->hectares) }} · {{ $quote->vatLabel() }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium tabular-nums">{{ money($quote->total) }}</span>
                                    <x-badge :tone="$quote->status->badgeClasses()">{{ $quote->status->label() }}</x-badge>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li><x-empty icon="document" title="Sin cotizaciones" /></li>
                    @endforelse
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="font-semibold text-slate-900">Próximos seguimientos</h2>
                </div>
                @include('livewire.partials.task-list', ['tasks' => $this->tasks, 'showContext' => true])
            </section>
            <section class="card">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Actividad reciente</h2></div>
                <div class="p-5">
                    @include('livewire.partials.timeline', ['logs' => $this->activity->take($activityLimit), 'showContext' => false])
                    @if ($this->activity->count() > $activityLimit)
                        <button type="button" wire:click="loadMoreActivity" class="btn-ghost btn-sm mt-3 w-full">Ver más actividad</button>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
