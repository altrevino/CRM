<div>
    @php
        $b = $this->breakdown;
        $r = $b['result'];
        $selecting = in_array($mode, ['create', 'duplicate'], true);
        $tariffVersion = $this->tariff['version'] ?? null;
    @endphp

    <x-page-header :title="$title" :breadcrumbs="['Cotizaciones' => route('quotes.index'), $title => null]">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <x-badge :tone="$this->isOutdatedPricing ? 'amber' : 'brand'"><x-icon name="tag" class="size-3.5" /> {{ $tariffVersion ? 'Tarifa v'.$tariffVersion : 'Previa al tarifario' }}</x-badge>
                @if (! $this->isOutdatedPricing)<span class="text-slate-500">vigente</span>@endif
            </div>
        </x-slot:meta>
    </x-page-header>

    @if (in_array($mode, ['duplicate', 'revision'], true))
        <div class="mb-5 flex items-start gap-2 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-900 ring-1 ring-sky-200">
            <x-icon name="refresh" class="mt-0.5 size-4 shrink-0" />
            <div>
                Precios actualizados con la <strong>Tarifa v{{ $currentVersion }}</strong> vigente.
                @if ($mode === 'duplicate')
                    No se copiaron descuentos ni ajustes excepcionales de {{ $this->baseQuote?->number }}.
                @else
                    Se conservan los descuentos y ajustes de {{ $this->baseQuote?->number }}; revísalos antes de guardar. La versión anterior se conserva como reemplazada.
                @endif
            </div>
        </div>
    @endif

    @if ($mode === 'edit' && $this->isOutdatedPricing)
        <div class="mb-5 flex flex-col gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-2">
                <x-icon name="alert" class="mt-0.5 size-4 shrink-0" />
                <span>Este borrador usa <strong>{{ $tariffVersion ? 'la Tarifa v'.$tariffVersion : 'precios previos al tarifario' }}</strong>; la vigente es la <strong>v{{ $currentVersion }}</strong>. Para emitirlo deberás actualizarla o confirmar el uso de la anterior con un motivo.</span>
            </div>
            <button type="button" wire:click="updatePricing" class="btn-primary btn-sm shrink-0"><x-icon name="refresh" class="size-4" /> Actualizar a tarifa vigente</button>
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- 1. Cliente y rancho --}}
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">1. Cliente y rancho</h2>
                @if (! $selecting)
                    @php $sel = $this->editing?->opportunity ?? $this->baseQuote?->opportunity; @endphp
                    <p class="text-sm"><span class="font-medium text-slate-900">{{ $sel?->ranch?->client?->name }}</span> · {{ $sel?->ranch?->name }} <span class="text-slate-500">· {{ $sel?->ranch?->location() }}</span></p>
                @else
                    @if ($clientMode === 'existing')
                        @if ($this->client)
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-brand-50/60 px-4 py-3 ring-1 ring-brand-200">
                                <div><p class="font-medium text-slate-900">{{ $this->client->name }}</p><p class="text-sm text-slate-500">{{ $this->client->formattedPhone() }}</p></div>
                                <button type="button" wire:click="clearClient" class="btn-ghost btn-sm">Cambiar</button>
                            </div>
                        @else
                            <div class="relative">
                                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
                                <input type="text" wire:model.live.debounce.300ms="clientSearch" class="input pl-9 @error('client_id') input-error @enderror" placeholder="Buscar cliente por nombre o teléfono…" autocomplete="off">
                            </div>
                            @error('client_id')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            @if ($this->clientResults->isNotEmpty())
                                <ul class="mt-2 divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                                    @foreach ($this->clientResults as $result)
                                        <li><button type="button" wire:click="selectClient('{{ $result->id }}')" class="flex w-full items-center justify-between px-4 py-2.5 text-left hover:bg-slate-50"><span class="font-medium text-slate-800">{{ $result->name }}</span><span class="text-xs text-slate-500">{{ $result->formattedPhone() }} · {{ $result->ranches_count }} ranchos</span></button></li>
                                    @endforeach
                                </ul>
                            @endif
                            <button type="button" wire:click="newClient" class="btn-secondary btn-sm mt-3"><x-icon name="plus" class="size-4" /> Cliente nuevo</button>
                        @endif
                    @else
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-field label="Nombre del cliente" for="qb-cname" error="client_name" required><input id="qb-cname" type="text" wire:model.live.debounce.400ms="client_name" class="input"></x-field>
                            <x-field label="Teléfono" for="qb-cphone" error="client_phone" required><input id="qb-cphone" type="tel" wire:model.live.debounce.400ms="client_phone" class="input" placeholder="81 1234 5678"></x-field>
                        </div>
                        @if ($this->clientDuplicates->isNotEmpty())
                            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm">
                                <p class="font-medium text-amber-900">¿Es alguno de estos clientes?</p>
                                @foreach ($this->clientDuplicates as $match)
                                    <div class="mt-1.5 flex items-center justify-between gap-2"><span>{{ $match['client']->name }} <span class="text-xs text-amber-800">· {{ $match['reason'] }}</span></span><button type="button" wire:click="selectClient('{{ $match['client']->id }}')" class="btn-secondary btn-sm">Usar este</button></div>
                                @endforeach
                            </div>
                        @endif
                        <button type="button" wire:click="$set('clientMode', 'existing')" class="btn-ghost btn-sm mt-3">← Buscar cliente existente</button>
                    @endif

                    @if ($client_id || $clientMode === 'new')
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            @if ($clientMode === 'existing' && $ranchMode === 'existing')
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($this->clientRanches as $ranch)
                                        <button type="button" wire:click="selectRanch('{{ $ranch->id }}')" wire:key="qr-{{ $ranch->id }}" class="rounded-xl px-4 py-3 text-left ring-1 transition {{ $ranch_id === $ranch->id ? 'bg-brand-50 ring-2 ring-brand-500' : 'ring-slate-200 hover:bg-slate-50' }}">
                                            <p class="font-medium text-slate-900">{{ $ranch->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $ranch->location() }} · {{ hectareas($ranch->total_hectares) }}</p>
                                        </button>
                                    @endforeach
                                </div>
                                @error('ranch_id')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                <button type="button" wire:click="newRanch" class="btn-secondary btn-sm mt-3"><x-icon name="plus" class="size-4" /> Rancho nuevo</button>

                                @if ($ranch_id)
                                    <x-field label="Servicio" for="qb-opp" error="opportunity_id" class="mt-4" hint="La cotización queda ligada a este servicio del rancho.">
                                        <select id="qb-opp" wire:model.live="opportunity_id" class="input">
                                            @foreach ($this->opportunities as $opp)
                                                <option value="{{ $opp->id }}">{{ $opp->title() }} · {{ $opp->stage->label() }}</option>
                                            @endforeach
                                            <option value="">Nuevo servicio</option>
                                        </select>
                                    </x-field>
                                @endif
                            @else
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <x-field label="Nombre del rancho" for="qb-rname" error="ranch_name" required class="sm:col-span-3"><input id="qb-rname" type="text" wire:model="ranch_name" class="input" placeholder="Rancho Las Lomas"></x-field>
                                    <x-field label="Municipio" for="qb-mun" error="municipality" required><input id="qb-mun" type="text" wire:model="municipality" class="input"></x-field>
                                    <x-field label="Estado" for="qb-state" error="state_id" required><x-select id="qb-state" wire:model="state_id" :options="$states" placeholder="Selecciona…" /></x-field>
                                </div>
                                @if ($clientMode === 'existing' && $this->clientRanches->isNotEmpty())
                                    <button type="button" wire:click="$set('ranchMode', 'existing')" class="btn-ghost btn-sm mt-3">← Elegir rancho existente</button>
                                @endif
                            @endif

                            @if (! $opportunity_id)
                                <x-field label="Tipo de servicio" error="service_type" class="mt-4">
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach ($serviceTypes as $value => $label)
                                            <label class="flex cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 ring-inset hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800 has-[:checked]:ring-brand-400"><input type="radio" wire:model="service_type" value="{{ $value }}" class="sr-only">{{ $label }}</label>
                                        @endforeach
                                    </div>
                                </x-field>
                            @endif
                        </div>
                    @endif
                @endif
            </section>

            {{-- 2. Superficie y traslado --}}
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">2. Superficie y traslado</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="Superficie a censar (ha)" for="qb-ha" error="hectares" required>
                        <input id="qb-ha" type="number" step="0.01" min="0.01" wire:model.live.debounce.400ms="hectares" class="input text-lg font-semibold @error('hectares') input-error @enderror" placeholder="2000">
                    </x-field>
                    <x-field label="Distancia de ida al rancho (km)" for="qb-km" error="one_way_km" :hint="$kmHint ?? 'Solo ida. El sistema la duplica para el viaje redondo. 0 = sin traslado.'">
                        <input id="qb-km" type="number" step="0.01" min="0" wire:model.live.debounce.400ms="one_way_km" class="input text-lg font-semibold @error('one_way_km') input-error @enderror" placeholder="200">
                    </x-field>
                </div>
                @if ($r)
                    <div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center text-sm ring-1 ring-slate-200">
                        <div><p class="text-xs text-slate-500">Ida</p><p class="font-semibold tabular-nums">{{ number_format((float) $r['one_way_km'], 2) }} km</p></div>
                        <div><p class="text-xs text-slate-500">Recorrido cotizado {{ $r['route_adjusted'] ? '(ajustado)' : '(ida y vuelta)' }}</p><p class="font-semibold tabular-nums {{ $r['route_adjusted'] ? 'text-amber-700' : '' }}">{{ number_format((float) $r['route_km'], 2) }} km</p></div>
                        <div><p class="text-xs text-slate-500">× {{ money($r['km_rate']) }}/km</p><p class="font-semibold tabular-nums">{{ money($r['logistics_amount']) }}</p></div>
                    </div>
                @endif
                <label class="mt-4 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model.live="route_adjusted" class="rounded border-slate-300 text-brand-600"> Ajustar kilómetros totales (ruta compartida o recorrido distinto)
                </label>
                @if ($route_adjusted)
                    <div class="mt-3 grid grid-cols-1 gap-4 rounded-xl bg-amber-50/60 p-3 ring-1 ring-amber-200 sm:grid-cols-3">
                        <x-field label="Km totales a cotizar" for="qb-rkm" error="route_km" required>
                            <input id="qb-rkm" type="number" step="0.01" min="0" wire:model.live.debounce.400ms="route_km" class="input">
                        </x-field>
                        <x-field label="Motivo (nota interna)" for="qb-rreason" error="route_adjust_reason" required class="sm:col-span-2">
                            <input id="qb-rreason" type="text" wire:model.live.debounce.500ms="route_adjust_reason" class="input" placeholder="Ruta compartida con el servicio de Rancho El Venado">
                        </x-field>
                    </div>
                @endif
            </section>

            {{-- 3. Viáticos, descuento y ajustes --}}
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">3. Viáticos, descuento y ajustes</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="Viáticos (opcional)" for="qb-via" error="travel_expenses" hint="Concepto separado del traslado.">
                        <input id="qb-via" type="number" step="0.01" min="0" wire:model.live.debounce.400ms="travel_expenses" class="input" placeholder="0">
                    </x-field>
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <p class="mb-2 text-sm font-medium text-slate-700">Descuento (opcional, uno por cotización)</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-field label="Tipo" for="qb-dtype" error="discount_type">
                            <select id="qb-dtype" wire:model.live="discount_type" class="input">
                                <option value="">Sin descuento</option>
                                @foreach ($discountTypes as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                        </x-field>
                        @if ($discount_type)
                            <x-field :label="$discount_type === 'percent' ? 'Porcentaje (%)' : 'Importe (MXN)'" for="qb-dval" error="discount_value" required>
                                <input id="qb-dval" type="number" step="0.01" min="0" wire:model.live.debounce.400ms="discount_value" class="input">
                            </x-field>
                            <x-field label="Se aplica a" for="qb-dscope" error="discount_scope" required>
                                <select id="qb-dscope" wire:model.live="discount_scope" class="input">
                                    @foreach ($discountScopes as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                                </select>
                            </x-field>
                            <x-field label="Motivo (nota interna, no sale en el PDF)" for="qb-dreason" error="discount_reason" required class="sm:col-span-3">
                                <input id="qb-dreason" type="text" wire:model.live.debounce.500ms="discount_reason" class="input" placeholder="Ruta compartida, cliente recurrente…">
                            </x-field>
                        @endif
                    </div>
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model.live="service_adjusted" class="rounded border-slate-300 text-brand-600"> Ajustar excepcionalmente el precio del censo
                    </label>
                    @if ($service_adjusted)
                        <div class="mt-3 grid grid-cols-1 gap-4 rounded-xl bg-amber-50/60 p-3 ring-1 ring-amber-200 sm:grid-cols-3">
                            <div class="text-sm"><p class="text-xs text-slate-500">Precio calculado por tarifa</p><p class="font-semibold tabular-nums">{{ $r ? money($r['service_amount_calculated']) : '—' }}</p></div>
                            <x-field label="Precio ajustado" for="qb-samount" error="service_amount" required>
                                <input id="qb-samount" type="number" step="0.01" min="0" wire:model.live.debounce.400ms="service_amount" class="input">
                            </x-field>
                            <x-field label="Motivo (nota interna)" for="qb-sreason" error="service_adjust_reason" required>
                                <input id="qb-sreason" type="text" wire:model.live.debounce.500ms="service_adjust_reason" class="input">
                            </x-field>
                        </div>
                    @endif
                </div>
            </section>

            {{-- 4. Documento --}}
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-slate-900">4. Datos de la cotización</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-field label="Fecha de emisión" for="qb-issued" error="issued_at" required><input id="qb-issued" type="date" wire:model="issued_at" class="input"></x-field>
                    <x-field label="Vigente hasta" for="qb-valid" error="valid_until" required><input id="qb-valid" type="date" wire:model="valid_until" class="input"></x-field>
                    <x-field label="Fecha tentativa del servicio" for="qb-tent" error="tentative_service_date"><input id="qb-tent" type="date" wire:model="tentative_service_date" class="input"></x-field>
                    <x-field label="Condiciones de pago" for="qb-pay" error="payment_terms" class="sm:col-span-3"><textarea id="qb-pay" wire:model="payment_terms" rows="2" class="input"></textarea></x-field>
                    <x-field label="Observaciones (salen en el PDF)" for="qb-obs" error="observations" class="sm:col-span-3"><textarea id="qb-obs" wire:model="observations" rows="2" class="input"></textarea></x-field>
                    <x-field label="Notas internas (no salen en el PDF)" for="qb-notes" error="notes" class="sm:col-span-3"><textarea id="qb-notes" wire:model="notes" rows="2" class="input"></textarea></x-field>
                </div>
                @if ($selecting)
                    <details class="mt-4 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200" @if ($folio || $errors->has('folio')) open @endif>
                        <summary class="cursor-pointer text-sm font-medium text-slate-700">Número: <span class="text-slate-500">{{ $folio ? \App\Models\Quote::formatNumber((int) $folio, max(1, (int) $version)) : 'automático ('.$nextNumber.')' }}</span></summary>
                        <div class="mt-3 flex items-start gap-2">
                            <x-field label="Folio (solo para migrar cotizaciones antiguas)" for="qb-folio" error="folio" class="flex-1"><input id="qb-folio" type="number" min="1" wire:model.live.debounce.500ms="folio" class="input"></x-field>
                            <x-field label="Versión" for="qb-version" error="version" class="w-24"><input id="qb-version" type="number" min="1" wire:model="version" class="input"></x-field>
                        </div>
                    </details>
                @endif
            </section>
        </div>

        {{-- Resumen --}}
        <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Desglose</h2>
                    <x-badge :tone="$this->isOutdatedPricing ? 'amber' : 'brand'">{{ $tariffVersion ? 'Tarifa v'.$tariffVersion : 'Previa' }}</x-badge>
                </div>

                <x-field label="IVA" error="apply_vat" required class="mt-4">
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 ring-inset hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800 has-[:checked]:ring-brand-400"><input type="radio" wire:model.live="apply_vat" value="si" class="sr-only">Con IVA</label>
                        <label class="flex cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 ring-inset hover:bg-slate-50 has-[:checked]:bg-amber-50 has-[:checked]:text-amber-900 has-[:checked]:ring-amber-400"><input type="radio" wire:model.live="apply_vat" value="no" class="sr-only">Sin IVA</label>
                    </div>
                </x-field>

                @if ($b['error'])
                    <p class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 ring-1 ring-rose-200">{{ $b['error']['message'] }}</p>
                @endif
                @error('pricing')<p class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 ring-1 ring-rose-200">{{ $message }}</p>@enderror
                @error('quote')<p class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 ring-1 ring-rose-200">{{ $message }}</p>@enderror

                @if ($r)
                    <dl class="mt-4 space-y-2 text-sm" data-testid="breakdown">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-600">Servicio de censo térmico<span class="block text-xs text-slate-400">{{ hectareas($r['hectares']) }}{{ $r['service_adjusted'] ? ' · ajustado (tarifa: '.money($r['service_amount_calculated']).')' : '' }}</span></dt>
                            <dd class="font-medium tabular-nums">{{ money($r['service_amount']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-600">Traslado y movilización<span class="block text-xs text-slate-400">{{ number_format((float) $r['route_km'], 2) }} km × {{ money($r['km_rate']) }}{{ $r['route_adjusted'] ? ' · ajustado' : '' }}</span></dt>
                            <dd class="font-medium tabular-nums">{{ money($r['logistics_amount']) }}</dd>
                        </div>
                        @if ((float) $r['travel_expenses'] > 0)
                            <div class="flex justify-between gap-3"><dt class="text-slate-600">Viáticos</dt><dd class="font-medium tabular-nums">{{ money($r['travel_expenses']) }}</dd></div>
                        @endif
                        @if ($r['discount_type'])
                            <div class="flex justify-between gap-3 text-amber-800">
                                <dt>Descuento {{ $r['discount_type'] === 'percent' ? (float) $r['discount_value'].'%' : '' }}<span class="block text-xs">sobre {{ mb_strtolower($discountScopes[$r['discount_scope']]) }} (base {{ money($r['discount_base']) }})</span></dt>
                                <dd class="font-medium tabular-nums">−{{ money($r['discount_amount']) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3 border-t border-slate-200 pt-2"><dt class="text-slate-600">Subtotal{{ $r['discount_type'] ? ' después del descuento' : '' }}</dt><dd class="font-medium tabular-nums">{{ money($r['subtotal']) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-600">{{ $r['apply_vat'] ? 'IVA '.(float) $r['vat_rate'].'%' : 'IVA' }}</dt><dd class="font-medium tabular-nums">{{ $apply_vat === '' ? 'Elige Con/Sin IVA' : ($r['apply_vat'] ? money($r['vat_amount']) : 'No aplica') }}</dd></div>
                        <div class="flex justify-between gap-3 border-t border-slate-200 pt-2 text-base"><dt class="font-semibold text-slate-900">Total</dt><dd class="font-semibold tabular-nums text-slate-900" data-testid="total">{{ money($r['total']) }} MXN</dd></div>
                    </dl>
                @elseif (! $b['error'])
                    <p class="mt-4 text-sm text-slate-500">Captura la superficie para ver el desglose.</p>
                @endif

                <div class="mt-5 grid grid-cols-1 gap-2">
                    <button type="button" wire:click="save(true)" class="btn-primary w-full" wire:loading.attr="disabled"><x-icon name="download" class="size-4" /> Guardar y descargar PDF</button>
                    <button type="submit" class="btn-secondary w-full" wire:loading.attr="disabled">Guardar borrador</button>
                </div>
                <p class="mt-3 text-xs text-slate-500">Guardar o descargar no marca la cotización como enviada.</p>
            </section>
        </aside>
    </form>
</div>
