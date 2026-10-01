<div>
    <x-page-header :title="$client->name" :breadcrumbs="['Clientes' => route('clients.index'), $client->name => null]">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                @if ($client->phone)
                    <a href="{{ \App\Support\Phone::telUrl($client->phone) }}" class="inline-flex items-center gap-1.5 hover:text-slate-800"><x-icon name="phone" class="size-4" /> {{ $client->formattedPhone() }}</a>
                @endif
                <span>Cliente desde {{ fecha($client->created_at) }}</span>
                <span>Modificado {{ fecha($client->updated_at) }}</span>
                <span>Registró: {{ $client->creator->name }}</span>
            </div>
        </x-slot:meta>
        <x-slot:actions>
            @if ($client->phone)
                <a href="{{ $client->whatsappUrl() }}" target="_blank" rel="noopener" class="btn bg-emerald-600 text-white shadow-sm hover:bg-emerald-700"><x-icon name="whatsapp" class="size-4" /> WhatsApp</a>
            @endif
            <a href="{{ route('opportunities.create', ['cliente' => $client->id]) }}" wire:navigate class="btn-primary"><x-icon name="plus" class="size-4" /> Nuevo servicio</a>
            <button type="button" x-on:click="$dispatch('open-client-form', { id: '{{ $client->id }}' })" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</button>
            @can('delete', $client)
                <button type="button" wire:click="delete" wire:confirm="¿Eliminar al cliente {{ $client->name }}?" class="btn-danger"><x-icon name="trash" class="size-4" /></button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-kpi label="Total vendido" :value="money($this->totals['sold'])" icon="cash" tone="emerald" hint="Confirmados y realizados" />
        <x-kpi label="Saldo pendiente" :value="money($this->totals['balance'])" icon="alert" :tone="$this->totals['balance'] > 0 ? 'amber' : 'slate'" hint="Pagado: {{ money($this->totals['paid']) }}" />
        <x-kpi label="Ranchos" :value="$this->ranches->count()" icon="map" tone="brand" />
        <x-kpi label="Servicios activos" :value="$this->totals['active']" icon="briefcase" tone="blue" hint="{{ $this->opportunities->count() }} en total" />
    </div>

    @if ($client->notes)
        <div class="card mt-6 p-4 text-sm whitespace-pre-line text-slate-600"><span class="font-medium text-slate-800">Notas:</span> {{ $client->notes }}</div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Ranchos --}}
            <section class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="font-semibold text-slate-900">Ranchos</h2>
                    <button type="button" x-on:click="$dispatch('open-ranch-form', { clientId: '{{ $client->id }}' })" class="btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> Agregar rancho</button>
                </div>
                @if ($this->ranches->isEmpty())
                    <x-empty icon="map" title="Sin ranchos registrados" description="Agrega el primer rancho de este cliente." />
                @else
                    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                        @foreach ($this->ranches as $ranch)
                            <a href="{{ route('ranches.show', $ranch) }}" wire:navigate wire:key="r-{{ $ranch->id }}" class="group rounded-xl border border-slate-200 p-4 transition hover:border-brand-300 hover:shadow-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-medium text-slate-900 group-hover:text-brand-700">{{ $ranch->name }}</p>
                                    @if ($ranch->fence_type)<x-badge>{{ $ranch->fence_type->label() }}</x-badge>@endif
                                </div>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $ranch->location() }}</p>
                                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span>{{ hectareas($ranch->total_hectares) }}</span>
                                    <span>{{ $ranch->opportunities_count }} {{ $ranch->opportunities_count === 1 ? 'servicio' : 'servicios' }}</span>
                                    @if ($ranch->next_census_date)<span class="font-medium text-emerald-700">Próximo censo {{ fecha($ranch->next_census_date) }}</span>@endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Servicios --}}
            <section class="card">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Servicios / oportunidades</h2></div>
                @include('livewire.partials.opportunity-list', ['opportunities' => $this->opportunities, 'showRanch' => true])
            </section>

            {{-- Cotizaciones y pagos --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="card">
                    <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Cotizaciones</h2></div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($this->quotes as $quote)
                            <li wire:key="q-{{ $quote->id }}">
                                <a href="{{ route('opportunities.show', [$quote->opportunity_id, 'tab' => 'cotizaciones']) }}" wire:navigate class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-900">{{ $quote->number }} <span class="font-normal text-slate-500">· {{ $quote->opportunity->ranch->name }}</span></p>
                                        <p class="text-xs text-slate-500">{{ fecha($quote->issued_at) }} · {{ $quote->vatLabel() }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium tabular-nums">{{ money($quote->total) }}</p>
                                        <x-badge :tone="$quote->status->badgeClasses()">{{ $quote->status->label() }}</x-badge>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li><x-empty icon="document" title="Sin cotizaciones" /></li>
                        @endforelse
                    </ul>
                </section>
                <section class="card">
                    <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Pagos</h2></div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($this->payments as $payment)
                            <li wire:key="p-{{ $payment->id }}">
                                <a href="{{ route('opportunities.show', [$payment->opportunity_id, 'tab' => 'pagos']) }}" wire:navigate class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-900">{{ money($payment->amount) }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ fecha($payment->paid_at) }} · {{ $payment->method->name }} · {{ $payment->opportunity->ranch->name }}</p>
                                    </div>
                                    <span class="text-xs text-slate-400">{{ $payment->quote?->number }}</span>
                                </a>
                            </li>
                        @empty
                            <li><x-empty icon="cash" title="Sin pagos registrados" /></li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="font-semibold text-slate-900">Próximas tareas</h2>
                    <button type="button" x-on:click="$dispatch('open-task-form', { clientId: '{{ $client->id }}' })" class="btn-ghost btn-sm"><x-icon name="plus" class="size-4" /> Tarea</button>
                </div>
                @include('livewire.partials.task-list', ['tasks' => $this->tasks, 'showContext' => true])
            </section>

            <section class="card">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Historial de actividad</h2></div>
                <div class="p-5">
                    @include('livewire.partials.timeline', ['logs' => $this->activity->take($activityLimit), 'showContext' => true])
                    @if ($this->activity->count() > $activityLimit)
                        <button type="button" wire:click="loadMoreActivity" class="btn-ghost btn-sm mt-3 w-full">Ver más actividad</button>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
