<div>
    @php
        $client = $o->ranch->client;
        $ranch = $o->ranch;
        $accepted = $o->acceptedTotal();
        $tabs = [
            'resumen' => 'Resumen',
            'cotizaciones' => 'Cotizaciones ('.$this->quotes->count().')',
            'pagos' => 'Pagos ('.$this->payments->count().')',
            'seguimientos' => 'Seguimientos ('.$o->pendingTasks()->count().')',
            'actividad' => 'Actividad',
        ];
    @endphp

    {{-- Encabezado --}}
    <div class="mb-6">
        <nav class="mb-2 flex flex-wrap items-center gap-1 text-sm text-slate-500" aria-label="Ruta">
            <a href="{{ route('clients.show', $client) }}" wire:navigate class="hover:text-slate-800">{{ $client->name }}</a>
            <x-icon name="chevron-right" class="size-3.5 text-slate-400" />
            <a href="{{ route('ranches.show', $ranch) }}" wire:navigate class="hover:text-slate-800">{{ $ranch->name }}</a>
            <x-icon name="chevron-right" class="size-3.5 text-slate-400" />
            <span class="text-slate-700">{{ $o->title() }}</span>
        </nav>
        <div class="flex flex-col gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $ranch->name }}</h1>
                <p class="mt-0.5 text-slate-600">{{ $client->name }} <span class="text-slate-400">·</span> {{ $o->service_type->label() }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-stage-badge :stage="$o->stage" size="lg" />
                    @if ($o->census_date)
                        <x-badge tone="emerald"><x-icon name="calendar" class="size-3.5" /> Censo {{ fecha($o->census_date) }}</x-badge>
                    @endif
                    @if ($o->stage === \App\Enums\PipelineStage::Lost && $o->lost_reason)
                        <span class="text-sm text-slate-500">Motivo: {{ $o->lost_reason }}</span>
                    @endif
                </div>
            </div>

            {{-- Acciones rápidas --}}
            <div class="flex flex-wrap gap-2">
                <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                    <button type="button" x-on:click="open = !open" class="btn-primary"><x-icon name="arrows" class="size-4" /> Cambiar etapa <x-icon name="chevron-down" class="size-4" /></button>
                    <div x-show="open" x-cloak x-transition.opacity class="absolute left-0 z-30 mt-1 w-56 rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200">
                        @foreach ($stages as $stage)
                            <button type="button" wire:click="changeStage('{{ $stage->value }}')" x-on:click="open = false" @disabled($stage === $o->stage)
                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:opacity-50">
                                <span class="size-2.5 rounded-full {{ $stage->dotClasses() }}"></span>{{ $stage->label() }}
                                @if ($stage === $o->stage)<span class="ml-auto text-xs text-slate-400">actual</span>@endif
                            </button>
                        @endforeach
                    </div>
                </div>
                <button type="button" x-on:click="$dispatch('open-quote-form', { opportunityId: '{{ $o->id }}' })" class="btn-secondary"><x-icon name="document" class="size-4" /> Cotización</button>
                <button type="button" x-on:click="$dispatch('open-payment-form', { opportunityId: '{{ $o->id }}' })" class="btn-secondary"><x-icon name="cash" class="size-4" /> Pago</button>
                <button type="button" x-on:click="$dispatch('open-task-form', { opportunityId: '{{ $o->id }}' })" class="btn-secondary"><x-icon name="check" class="size-4" /> Tarea</button>
                <button type="button" wire:click="setTab('actividad')" x-on:click="setTimeout(() => document.getElementById('comment-box')?.focus(), 250)" class="btn-secondary"><x-icon name="chat" class="size-4" /> Comentario</button>
                @if ($client->phone)
                    <a href="{{ $client->whatsappUrl() }}" target="_blank" rel="noopener" class="btn bg-emerald-600 text-white shadow-sm hover:bg-emerald-700" title="WhatsApp"><x-icon name="whatsapp" class="size-4" /><span>WhatsApp</span></a>
                @endif
                @if ($ranch->maps_url)
                    <a href="{{ $ranch->maps_url }}" target="_blank" rel="noopener" class="btn bg-blue-600 text-white shadow-sm hover:bg-blue-700" title="Google Maps"><x-icon name="map-pin" class="size-4" /><span>Maps</span></a>
                @endif
                <button type="button" x-on:click="$dispatch('open-opportunity-form', { id: '{{ $o->id }}' })" class="btn-ghost" title="Editar servicio"><x-icon name="pencil" class="size-4" /></button>
                @can('delete', $o)
                    <button type="button" wire:click="delete" wire:confirm="¿Eliminar este servicio? Solo es posible si no tiene pagos." class="btn-ghost text-rose-600" title="Eliminar servicio"><x-icon name="trash" class="size-4" /></button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if ($this->alerts)
        <div class="mb-6 flex flex-col gap-2">
            @foreach ($this->alerts as $alert)
                @php $tone = ['rose' => 'bg-rose-50 text-rose-800 ring-rose-200', 'amber' => 'bg-amber-50 text-amber-900 ring-amber-200', 'blue' => 'bg-blue-50 text-blue-800 ring-blue-200', 'slate' => 'bg-slate-100 text-slate-700 ring-slate-200'][$alert['tone']]; @endphp
                <div class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm ring-1 {{ $tone }}"><x-icon name="alert" class="size-4 shrink-0" /> {{ $alert['text'] }}</div>
            @endforeach
        </div>
    @endif

    {{-- Pestañas --}}
    <div class="mb-6 -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
        <nav class="flex min-w-max gap-1 border-b border-slate-200">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="setTab('{{ $key }}')"
                        class="-mb-px border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap transition {{ $tab === $key ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    @if ($tab === 'resumen')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="card p-5 lg:col-span-2">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-5 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Cliente</dt><dd class="mt-0.5"><a href="{{ route('clients.show', $client) }}" wire:navigate class="link">{{ $client->name }}</a></dd></div>
                    <div><dt class="text-slate-500">Rancho</dt><dd class="mt-0.5"><a href="{{ route('ranches.show', $ranch) }}" wire:navigate class="link">{{ $ranch->name }}</a></dd></div>
                    <div><dt class="text-slate-500">Municipio</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->municipality ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Estado</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->state?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Tipo de cerca</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->fence_type?->label() ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Tipo de servicio</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $o->service_type->label() }}</dd></div>
                    <div><dt class="text-slate-500">Superficie del rancho</dt><dd class="mt-0.5 font-medium text-slate-800">{{ hectareas($ranch->total_hectares) }}</dd></div>
                    <div><dt class="text-slate-500">Hectáreas cotizadas</dt><dd class="mt-0.5 font-medium text-slate-800">{{ hectareas($o->quoted_hectares) }}</dd></div>
                    <div><dt class="text-slate-500">Km redondo desde MTY</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $ranch->km_round_trip !== null ? number_format((float) $ranch->km_round_trip, 0).' km' : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Fecha tentativa</dt><dd class="mt-0.5 font-medium text-slate-800">{{ fecha($o->tentative_census_date) }}</dd></div>
                    <div>
                        <dt class="text-slate-500">Fecha de censo</dt>
                        <dd class="mt-0.5 font-medium {{ $o->census_date ? 'text-emerald-700' : 'text-slate-800' }}">{{ fecha($o->census_date) }}</dd>
                    </div>
                    <div><dt class="text-slate-500">Responsable</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $o->owner->name }}</dd></div>
                    <div><dt class="text-slate-500">Último contacto</dt><dd class="mt-0.5 font-medium text-slate-800">{{ fecha($o->last_contact_at) }}</dd></div>
                    <div>
                        <dt class="text-slate-500">Próximo seguimiento</dt>
                        @php $next = $o->nextFollowUp(); @endphp
                        <dd class="mt-0.5 font-medium {{ $next && \Illuminate\Support\Carbon::parse($next)->lt(today()) ? 'text-rose-600' : 'text-slate-800' }}">{{ $next ? fecha($next).' · '.fecha_relativa($next) : 'Sin programar' }}</dd>
                    </div>
                    <div><dt class="text-slate-500">Creado</dt><dd class="mt-0.5 text-slate-800">{{ fecha($o->created_at) }} · {{ $o->creator->name }}</dd></div>
                    <div class="col-span-2 sm:col-span-3">
                        <dt class="text-slate-500">Especies a censar</dt>
                        <dd class="mt-1 flex flex-wrap gap-1.5">
                            @forelse ($o->species as $sp)
                                <x-badge tone="brand">{{ $sp->name }}</x-badge>
                            @empty
                                <span class="text-slate-400">Sin especificar</span>
                            @endforelse
                        </dd>
                    </div>
                    @if ($o->notes)
                        <div class="col-span-2 sm:col-span-3"><dt class="text-slate-500">Notas</dt><dd class="mt-1 whitespace-pre-line text-slate-700">{{ $o->notes }}</dd></div>
                    @endif
                </dl>

                @if (! $o->census_date && in_array($o->stage, [\App\Enums\PipelineStage::Confirmed, \App\Enums\PipelineStage::PendingDeposit], true))
                    <form wire:submit="setCensusDate" class="mt-6 flex flex-col gap-2 rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200 sm:flex-row sm:items-end">
                        <x-field label="Asignar fecha confirmada de censo" for="quick-census" error="newCensusDate" class="flex-1">
                            <input id="quick-census" type="date" wire:model="newCensusDate" class="input" required>
                        </x-field>
                        <button type="submit" class="btn-primary">Guardar fecha</button>
                    </form>
                @endif
            </section>

            <div class="space-y-6">
                <section class="card p-5">
                    <h2 class="mb-3 font-semibold text-slate-900">Finanzas</h2>
                    @if ($o->acceptedQuote)
                        <p class="mb-3 text-sm text-slate-500">Cotización vigente <span class="font-medium text-slate-800">{{ $o->acceptedQuote->number }}</span> · {{ $o->acceptedQuote->vatLabel() }}</p>
                        <dl class="mb-4 space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-slate-500">Total</dt><dd class="font-semibold tabular-nums">{{ money($accepted) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Pagado</dt><dd class="font-medium tabular-nums text-emerald-700">{{ money($o->paidTotal()) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Saldo</dt><dd class="font-medium tabular-nums {{ $o->balance() > 0 ? 'text-amber-700' : 'text-slate-500' }}">{{ money($o->balance()) }}</dd></div>
                        </dl>
                        <x-progress :paid="$o->paidTotal()" :total="$accepted" compact />
                    @else
                        <p class="text-sm text-slate-500">Sin cotización aceptada.</p>
                        @if ($o->quotedTotal() !== null)
                            <p class="mt-1 text-sm">Última cotización: <span class="font-medium">{{ money($o->quotedTotal()) }}</span></p>
                        @endif
                        @if ($o->paidTotal() > 0)
                            <p class="mt-1 text-sm">Pagado: <span class="font-medium text-emerald-700">{{ money($o->paidTotal()) }}</span></p>
                        @endif
                    @endif
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" x-on:click="$dispatch('open-payment-form', { opportunityId: '{{ $o->id }}' })" class="btn-secondary btn-sm">Registrar pago</button>
                        <button type="button" wire:click="setTab('cotizaciones')" class="btn-ghost btn-sm">Ver cotizaciones</button>
                    </div>
                </section>

                <section class="card">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                        <h2 class="font-semibold text-slate-900">Próximos seguimientos</h2>
                        <button type="button" x-on:click="$dispatch('open-task-form', { opportunityId: '{{ $o->id }}' })" class="btn-ghost btn-sm"><x-icon name="plus" class="size-4" /></button>
                    </div>
                    @include('livewire.partials.task-list', ['tasks' => $this->tasks->where('status', \App\Enums\TaskStatus::Pending)->take(4)])
                </section>
            </div>
        </div>
    @endif

    @if ($tab === 'cotizaciones')
        <section class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                <h2 class="font-semibold text-slate-900">Cotizaciones</h2>
                <button type="button" x-on:click="$dispatch('open-quote-form', { opportunityId: '{{ $o->id }}' })" class="btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nueva cotización</button>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($this->quotes as $quote)
                    <li wire:key="quote-{{ $quote->id }}" class="p-5 {{ $quote->status === $quoteStatus::Accepted ? 'bg-emerald-50/40' : '' }}">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-base font-semibold text-slate-900">{{ $quote->number }}</p>
                                    <x-badge :tone="$quote->status->badgeClasses()">{{ $quote->status->label() }}</x-badge>
                                    @if ($quote->status === $quoteStatus::Accepted)<x-badge tone="emerald">Vigente</x-badge>@endif
                                    <x-badge :tone="$quote->apply_vat ? 'sky' : 'amber'">{{ $quote->vatLabel() }}</x-badge>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ fecha($quote->issued_at) }} · {{ hectareas($quote->hectares) }}
                                    @if ($quote->sent_at) · Enviada {{ fecha($quote->sent_at) }}@endif
                                    @if ($quote->accepted_at) · Aceptada {{ fecha($quote->accepted_at) }}@endif
                                    · {{ $quote->creator->name }}
                                </p>
                                @if ($quote->notes)<p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $quote->notes }}</p>@endif
                            </div>
                            <dl class="grid min-w-64 grid-cols-2 gap-x-6 gap-y-1 text-sm">
                                <dt class="text-slate-500">Servicio</dt><dd class="text-right tabular-nums">{{ money($quote->service_amount) }}</dd>
                                <dt class="text-slate-500">Logística</dt><dd class="text-right tabular-nums">{{ money($quote->logistics_amount) }}</dd>
                                <dt class="text-slate-500">Subtotal</dt><dd class="text-right tabular-nums">{{ money($quote->subtotal) }}</dd>
                                <dt class="text-slate-500">IVA</dt><dd class="text-right tabular-nums">{{ money($quote->vat_amount) }}</dd>
                                <dt class="font-semibold text-slate-900">Total</dt><dd class="text-right font-semibold tabular-nums text-slate-900">{{ money($quote->total) }}</dd>
                            </dl>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($quote->status === $quoteStatus::Draft)
                                <button type="button" wire:click="markQuoteSent('{{ $quote->id }}')" class="btn-secondary btn-sm">Marcar como enviada</button>
                            @endif
                            @if (in_array($quote->status, [$quoteStatus::Draft, $quoteStatus::Sent, $quoteStatus::Rejected, $quoteStatus::Replaced], true))
                                <button type="button" wire:click="acceptQuote('{{ $quote->id }}')" wire:confirm="¿Marcar {{ $quote->number }} como la cotización aceptada y vigente? Si existe otra aceptada pasará a Reemplazada." class="btn-sm btn bg-emerald-600 text-white hover:bg-emerald-700">Marcar como aceptada</button>
                            @endif
                            @if ($quote->status === $quoteStatus::Sent)
                                <button type="button" wire:click="rejectQuote('{{ $quote->id }}')" wire:confirm="¿Marcar {{ $quote->number }} como rechazada?" class="btn-secondary btn-sm">Rechazada</button>
                            @endif
                            @can('update', $quote)
                                <button type="button" x-on:click="$dispatch('open-quote-form', { opportunityId: '{{ $o->id }}', quoteId: '{{ $quote->id }}', mode: 'edit' })" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /> Editar</button>
                            @endcan
                            <button type="button" x-on:click="$dispatch('open-quote-form', { opportunityId: '{{ $o->id }}', quoteId: '{{ $quote->id }}', mode: 'version' })" class="btn-ghost btn-sm"><x-icon name="refresh" class="size-4" /> Nueva versión</button>
                            @can('adjust', $quote)
                                <button type="button" x-on:click="$dispatch('open-quote-adjust', { quoteId: '{{ $quote->id }}' })" class="btn-ghost btn-sm" title="Cambiar número o fechas (migración)"><x-icon name="tag" class="size-4" /> Número y fechas</button>
                            @endcan
                            @can('delete', $quote)
                                <button type="button" wire:click="deleteQuote('{{ $quote->id }}')" wire:confirm="¿Eliminar {{ $quote->number }}? Quedará registrado en el historial." class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4" /></button>
                            @endcan
                        </div>
                    </li>
                @empty
                    <li><x-empty icon="document" title="Sin cotizaciones" description="Crea la primera cotización de este servicio." /></li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($tab === 'pagos')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">Estado de cuenta</h2>
                @if ($o->acceptedQuote)
                    <p class="mb-3 text-sm text-slate-500">Contra {{ $o->acceptedQuote->number }} ({{ $o->acceptedQuote->vatLabel() }})</p>
                @endif
                <x-progress :paid="$o->paidTotal()" :total="$accepted" />
                <button type="button" x-on:click="$dispatch('open-payment-form', { opportunityId: '{{ $o->id }}' })" class="btn-primary mt-5 w-full"><x-icon name="plus" class="size-4" /> Registrar pago</button>
            </section>
            <section class="card lg:col-span-2">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Pagos registrados</h2></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($this->payments as $payment)
                        <li wire:key="pay-{{ $payment->id }}" class="flex items-start justify-between gap-3 px-5 py-3.5">
                            <div>
                                <p class="font-semibold text-slate-900 tabular-nums">{{ money($payment->amount) }}</p>
                                <p class="text-sm text-slate-500">{{ fecha($payment->paid_at) }} · {{ $payment->method->name }}@if ($payment->reference) · Ref. {{ $payment->reference }}@endif</p>
                                <p class="text-xs text-slate-400">Registró {{ $payment->creator->name }} @if ($payment->quote) · {{ $payment->quote->number }}@endif</p>
                                @if ($payment->notes)<p class="mt-1 text-sm text-slate-600">{{ $payment->notes }}</p>@endif
                            </div>
                            <div class="flex shrink-0 gap-1">
                                @can('update', $payment)
                                    <button type="button" x-on:click="$dispatch('open-payment-form', { opportunityId: '{{ $o->id }}', paymentId: '{{ $payment->id }}' })" class="btn-ghost btn-sm" title="Editar"><x-icon name="pencil" class="size-4" /></button>
                                @endcan
                                @can('delete', $payment)
                                    <button type="button" wire:click="deletePayment('{{ $payment->id }}')" wire:confirm="¿Eliminar el pago de {{ money($payment->amount) }}? Quedará registrado en el historial." class="btn-ghost btn-sm text-rose-600" title="Eliminar"><x-icon name="trash" class="size-4" /></button>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li><x-empty icon="cash" title="Sin pagos registrados" /></li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif

    @if ($tab === 'seguimientos')
        <section class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                <h2 class="font-semibold text-slate-900">Seguimientos</h2>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" wire:model.live="showCompletedTasks" class="rounded border-slate-300 text-brand-600"> Mostrar cerradas</label>
                    <button type="button" x-on:click="$dispatch('open-task-form', { opportunityId: '{{ $o->id }}' })" class="btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nueva tarea</button>
                </div>
            </div>
            @include('livewire.partials.task-list', ['tasks' => $this->tasks])
        </section>
    @endif

    @if ($tab === 'actividad')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="card p-5 lg:order-2">
                <h2 class="mb-3 font-semibold text-slate-900">Agregar comentario</h2>
                <form wire:submit="addComment">
                    <textarea id="comment-box" wire:model="comment" rows="4" class="input" placeholder="Ej. Cliente comentó que lo busquemos nuevamente en enero."></textarea>
                    @error('comment')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-slate-400">Los comentarios no se pueden editar ni borrar.</p>
                    <button type="submit" class="btn-primary mt-3 w-full">Guardar comentario</button>
                </form>
            </section>
            <section class="card p-5 lg:order-1 lg:col-span-2">
                <h2 class="mb-5 font-semibold text-slate-900">Línea de tiempo</h2>
                @include('livewire.partials.timeline', ['logs' => $this->activity])
            </section>
        </div>
    @endif

    {{-- Confirmación: marcar como perdido --}}
    <x-modal title="Marcar como perdido" subtitle="La oportunidad saldrá del pipeline activo." size="sm" model="confirmingLost">
        <form id="lost-form" wire:submit="confirmLost">
            <x-field label="Motivo (opcional)" for="lost-reason" error="lostReason">
                <input id="lost-reason" type="text" wire:model="lostReason" class="input" placeholder="Precio, eligió a otro proveedor, pospuso…" maxlength="255">
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.confirmingLost = false">Cancelar</button>
            <button type="submit" form="lost-form" class="btn bg-rose-600 text-white hover:bg-rose-700">Confirmar perdido</button>
        </x-slot:footer>
    </x-modal>
</div>
