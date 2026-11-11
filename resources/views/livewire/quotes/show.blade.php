<div>
    @php
        $q = $this->quote;
        $o = $q->opportunity;
        $ranch = $o->ranch;
        $client = $ranch->client;
        $S = \App\Enums\QuoteStatus::class;
        $snap = $q->pricing_snapshot ?? [];
        $outdated = ! $q->usesCurrentPricing();
    @endphp

    @if ($autoDownload)
        <div x-data x-init="$nextTick(() => { const a = document.createElement('a'); a.href = @js(route('quotes.pdf', $q)); a.download = ''; document.body.appendChild(a); a.click(); a.remove(); $wire.autoDownload = false })"></div>
    @endif

    <x-page-header :title="$q->number" :breadcrumbs="['Cotizaciones' => route('quotes.index'), $q->number => null]">
        <x-slot:meta>
            <p class="mt-1 text-slate-600"><a href="{{ route('clients.show', $client) }}" wire:navigate class="hover:underline">{{ $client->name }}</a> · <a href="{{ route('ranches.show', $ranch) }}" wire:navigate class="hover:underline">{{ $ranch->name }}</a> · <a href="{{ route('opportunities.show', $o) }}" wire:navigate class="text-sm text-brand-700 hover:underline">ver servicio</a></p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-badge :tone="$q->statusClasses()">{{ $q->statusLabel() }}</x-badge>
                <x-badge :tone="$outdated ? 'amber' : 'brand'"><x-icon name="tag" class="size-3.5" /> {{ $q->pricingLabel() }}{{ $outdated ? ' · no vigente' : '' }}</x-badge>
                <x-badge :tone="$q->apply_vat ? 'sky' : 'amber'">{{ $q->vatLabel() }}</x-badge>
                @if ($q->revisionOf)<x-badge>Revisión de {{ $q->revisionOf->number }}</x-badge>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('quotes.pdf', $q) }}" class="btn-primary" data-testid="pdf-link"><x-icon name="download" class="size-4" /> Descargar PDF</a>
            @if ($client->phone)
                <a href="{{ $client->whatsappUrl() }}" target="_blank" rel="noopener" class="btn bg-emerald-600 text-white shadow-sm hover:bg-emerald-700" title="Abrir chat de WhatsApp (adjunta el PDF descargado)"><x-icon name="whatsapp" class="size-4" /> WhatsApp</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($q->status === $S::Draft && $outdated)
        <div class="mb-5 flex flex-col gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-2"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /><span>Este borrador usa <strong>{{ $q->pricingPhrase() }}</strong>; la vigente es la <strong>{{ $currentPriceList->label() }}</strong>. Actualízala antes de emitir o confirma el uso de la anterior con un motivo.</span></div>
            @can('update', $q)
                <button type="button" wire:click="refreshPricing" wire:confirm="Se recalcularán censo, traslado e IVA con la {{ $currentPriceList->label() }}. Los descuentos y ajustes se conservan si siguen siendo válidos. ¿Continuar?" class="btn-primary btn-sm shrink-0"><x-icon name="refresh" class="size-4" /> Actualizar a tarifa vigente</button>
            @endcan
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Acciones --}}
            <section class="card flex flex-wrap gap-2 p-4">
                @can('update', $q)
                    <a href="{{ route('quotes.edit', $q) }}" wire:navigate class="btn-secondary btn-sm"><x-icon name="pencil" class="size-4" /> Editar borrador</a>
                    <button type="button" wire:click="openEmit" class="btn-secondary btn-sm"><x-icon name="check" class="size-4" /> Emitir</button>
                @endcan
                @can('adjust', $q)
                    @if (in_array($q->status, [$S::Draft, $S::Issued], true))
                        <button type="button" wire:click="markSent" class="btn-secondary btn-sm">Marcar como enviada</button>
                    @endif
                    @if (in_array($q->status, [$S::Draft, $S::Issued, $S::Sent, $S::Rejected, $S::Replaced], true))
                        <button type="button" wire:click="accept" wire:confirm="¿Marcar {{ $q->number }} como aceptada? Si existe otra aceptada pasará a Reemplazada." class="btn btn-sm bg-emerald-600 text-white hover:bg-emerald-700">Marcar como aceptada</button>
                    @endif
                    @if (in_array($q->status, [$S::Issued, $S::Sent], true))
                        <button type="button" wire:click="reject" wire:confirm="¿Marcar {{ $q->number }} como rechazada?" class="btn-secondary btn-sm">Rechazada</button>
                    @endif
                @endcan
                @can('create', \App\Models\Quote::class)
                    @if ($q->status !== $S::Draft)
                        <a href="{{ route('quotes.create', ['revisar' => $q->id]) }}" wire:navigate class="btn-ghost btn-sm" title="Crea {{ \App\Models\Quote::formatNumber($q->folio, $this->family->max('version') + 1) }} y conserva esta versión"><x-icon name="refresh" class="size-4" /> Nueva revisión</a>
                    @endif
                    <a href="{{ route('quotes.create', ['duplicar' => $q->id]) }}" wire:navigate class="btn-ghost btn-sm"><x-icon name="document" class="size-4" /> Duplicar</a>
                @endcan
                @can('adjust', $q)
                    <button type="button" x-on:click="$dispatch('open-quote-adjust', { quoteId: '{{ $q->id }}' })" class="btn-ghost btn-sm"><x-icon name="tag" class="size-4" /> Número y fechas</button>
                @endcan
                @can('delete', $q)
                    <button type="button" wire:click="delete" wire:confirm="¿Eliminar {{ $q->number }}? Quedará registrado en el historial." class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4" /></button>
                @endcan
                <p class="w-full text-xs text-slate-500">Descargar el PDF no cambia el estado. Márcala como enviada cuando la mandes al cliente.</p>
            </section>

            {{-- Datos --}}
            <section class="card p-5">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Emisión</dt><dd class="font-medium">{{ fecha($q->issued_at) }}</dd></div>
                    <div><dt class="text-slate-500">Vigente hasta</dt><dd class="font-medium {{ $q->isExpired() ? 'text-rose-600' : '' }}">{{ $q->valid_until ? fecha($q->valid_until) : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Fecha tentativa</dt><dd class="font-medium">{{ $q->tentative_service_date ? fecha($q->tentative_service_date) : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Superficie</dt><dd class="font-medium">{{ hectareas($q->hectares) }}</dd></div>
                    <div><dt class="text-slate-500">Ubicación</dt><dd class="font-medium">{{ $ranch->location() }}</dd></div>
                    <div><dt class="text-slate-500">Creó</dt><dd class="font-medium">{{ $q->creator?->name }}</dd></div>
                    @if ($q->sent_at)<div><dt class="text-slate-500">Enviada</dt><dd class="font-medium">{{ fecha($q->sent_at) }}</dd></div>@endif
                    @if ($q->accepted_at)<div><dt class="text-slate-500">Aceptada</dt><dd class="font-medium">{{ fecha($q->accepted_at) }}</dd></div>@endif
                </dl>
                @if ($q->payment_terms)<div class="mt-4 text-sm"><p class="text-slate-500">Condiciones de pago</p><p class="whitespace-pre-line">{{ $q->payment_terms }}</p></div>@endif
                @if ($q->observations)<div class="mt-3 text-sm"><p class="text-slate-500">Observaciones (PDF)</p><p class="whitespace-pre-line">{{ $q->observations }}</p></div>@endif
            </section>

            {{-- Notas internas --}}
            @php
                $internal = array_filter([
                    $q->route_adjust_reason ? 'Kilómetros ajustados de '.number_format((float) $q->route_km_calculated, 2).' a '.number_format((float) $q->route_km, 2).' km: '.$q->route_adjust_reason : null,
                    $q->service_adjust_reason ? 'Precio del censo ajustado de '.money($q->service_amount_calculated).' a '.money($q->service_amount).' por '.($q->serviceAdjuster?->name ?? '—').($q->service_adjusted_at ? ' el '.fecha($q->service_adjusted_at) : '').': '.$q->service_adjust_reason : null,
                    $q->discount_reason ? $q->discountLabel().' de '.money($q->discount_amount).' (base '.money($q->discount_base).'): '.$q->discount_reason : null,
                    $q->pricing_override_reason ? 'Emitida con '.$q->pricingLabel().' no vigente: '.$q->pricing_override_reason : null,
                    $q->notes,
                ]);
            @endphp
            @if ($internal)
                <section class="card p-5">
                    <h2 class="mb-3 font-semibold text-slate-900">Notas internas <span class="text-xs font-normal text-slate-500">no salen en el PDF</span></h2>
                    <ul class="space-y-2 text-sm text-slate-700">
                        @foreach ($internal as $line)<li class="rounded-lg bg-slate-50 px-3 py-2 whitespace-pre-line ring-1 ring-slate-200">{{ $line }}</li>@endforeach
                    </ul>
                </section>
            @endif

            {{-- Versiones --}}
            @if ($this->family->count() > 1)
                <section class="card overflow-hidden">
                    <h2 class="px-5 pt-5 pb-3 font-semibold text-slate-900">Revisiones de {{ \App\Models\Quote::formatNumber($q->folio, 1) }}</h2>
                    <ul class="divide-y divide-slate-100 text-sm">
                        @foreach ($this->family as $rev)
                            <li wire:key="rev-{{ $rev->id }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 {{ $rev->id === $q->id ? 'bg-brand-50/50' : '' }}">
                                <a href="{{ route('quotes.show', $rev) }}" wire:navigate class="font-medium text-slate-900 hover:underline">{{ $rev->number }}</a>
                                <span class="flex items-center gap-2"><x-badge :tone="$rev->statusClasses()">{{ $rev->statusLabel() }}</x-badge><span class="text-xs text-slate-500">{{ $rev->pricingLabel() }}</span><span class="tabular-nums">{{ money($rev->total) }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">Historial</h2>
                @include('livewire.partials.timeline', ['logs' => $this->activity])
            </section>
        </div>

        {{-- Desglose --}}
        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Desglose</h2>
                <dl class="mt-4 space-y-2 text-sm" data-testid="breakdown">
                    <div class="flex justify-between gap-3"><dt class="text-slate-600">Servicio de censo térmico<span class="block text-xs text-slate-400">{{ hectareas($q->hectares) }}</span></dt><dd class="font-medium tabular-nums">{{ money($q->service_amount) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-600">Traslado y movilización
                        @if ($q->route_km !== null)<span class="block text-xs text-slate-400">{{ number_format((float) $q->one_way_km, 2) }} km ida · {{ number_format((float) $q->route_km, 2) }} km cotizados × {{ money($q->km_rate) }}</span>@endif
                    </dt><dd class="font-medium tabular-nums">{{ money($q->logistics_amount) }}</dd></div>
                    @if ((float) $q->travel_expenses > 0)<div class="flex justify-between gap-3"><dt class="text-slate-600">Viáticos</dt><dd class="font-medium tabular-nums">{{ money($q->travel_expenses) }}</dd></div>@endif
                    @if ($q->hasDiscount())<div class="flex justify-between gap-3 text-amber-800"><dt>{{ $q->discountLabel() }}{{ $q->discount_type === 'percent' ? ' '.(float) $q->discount_value.'%' : '' }}</dt><dd class="font-medium tabular-nums">−{{ money($q->discount_amount) }}</dd></div>@endif
                    <div class="flex justify-between gap-3 border-t border-slate-200 pt-2"><dt class="text-slate-600">Subtotal</dt><dd class="font-medium tabular-nums">{{ money($q->subtotal) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-600">IVA{{ $q->apply_vat ? ' '.(float) $q->vat_rate.'%' : '' }}</dt><dd class="font-medium tabular-nums">{{ $q->apply_vat ? money($q->vat_amount) : 'No aplica' }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-slate-200 pt-2 text-base"><dt class="font-semibold text-slate-900">Total</dt><dd class="font-semibold tabular-nums text-slate-900" data-testid="total">{{ money($q->total) }} MXN</dd></div>
                </dl>
            </section>
            @if ($snap)
                <section class="card p-5 text-sm">
                    <h2 class="font-semibold text-slate-900">{{ $q->pricingLabel() }}</h2>
                    <p class="mt-1 text-xs text-slate-500">Activa desde {{ isset($snap['activated_at']) ? fecha_hora(\Illuminate\Support\Carbon::parse($snap['activated_at'])) : '—' }}. Copia guardada en esta cotización; cambios posteriores al tarifario no la modifican.</p>
                    <ul class="mt-3 space-y-1 text-slate-600">
                        <li class="flex justify-between gap-2"><span>Hasta {{ number_format((float) $snap['minimum_hectares']) }} ha (mínimo)</span><span class="tabular-nums">{{ money($snap['minimum_price']) }}</span></li>
                        @foreach (\App\Models\PriceList::rowsFromSnapshot($snap) as $row)<li class="flex justify-between gap-2"><span>{{ $row['to'] ? 'Más de '.number_format($row['from']).' a '.number_format($row['to']).' ha' : 'Más de '.number_format($row['from']).' ha' }}</span><span class="tabular-nums">{{ money($row['price']) }}/ha</span></li>@endforeach
                        <li class="flex justify-between gap-2 border-t border-slate-100 pt-1"><span>Kilómetro</span><span class="tabular-nums">{{ money($snap['km_rate'] ?? 0) }}</span></li>
                    </ul>
                </section>
            @endif
        </aside>
    </div>

    @php $emitVerb = ['sent' => 'Emitir y marcar como enviada', 'accept' => 'Emitir y aceptar'][$then] ?? 'Emitir'; @endphp
    <x-modal :title="$emitVerb.' '.$q->number" model="showEmit" size="md">
        <div class="space-y-3 text-sm">
            <p>Al emitirla, los montos y condiciones quedan fijos. Para cambiarla después se crea una revisión. No se envía al cliente.</p>
            @if ($outdated)
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-amber-900 ring-1 ring-amber-200">Usa {{ $q->pricingPhrase() }} y la vigente es la <strong>{{ $currentPriceList->label() }}</strong>. Para conservar sus montos ({{ money($q->total) }}) indica el motivo. Si prefieres recalcularla, cierra y usa «Actualizar a tarifa vigente».</p>
                <x-field label="Motivo para conservar sus precios" for="emit-reason" error="pricing" required>
                    <input id="emit-reason" type="text" wire:model="overrideReason" class="input" placeholder="Cotización migrada del sistema anterior">
                </x-field>
            @endif
            @error('quote')<p class="text-rose-600">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showEmit = false">Cancelar</button>
            <button type="button" wire:click="emit" class="btn-primary" wire:loading.attr="disabled">{{ $emitVerb }}</button>
        </x-slot:footer>
    </x-modal>
</div>
