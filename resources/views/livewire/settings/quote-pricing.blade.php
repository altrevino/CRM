<div>
    <x-page-header title="Configuración" subtitle="Tarifas, IVA y textos de las cotizaciones" />
    @include('livewire.settings.nav')

    <div class="mb-5 flex flex-wrap items-center gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-900 ring-1 ring-brand-200">
        <x-icon name="tag" class="size-4" />
        Vigente: <span class="font-semibold">{{ $this->current->label() }}</span>
        <span class="text-brand-700">desde {{ fecha_hora($this->current->activated_at) }}</span>
        <span class="text-brand-700">· Al guardar se crea una versión nueva. Las cotizaciones ya creadas conservan su tarifa.</span>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Censo --}}
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Tarifa del censo (progresiva por bloques)</h2>
                <p class="mt-1 text-sm text-slate-500">Cada bloque cobra su precio solo por las hectáreas que caen dentro de él. Precios antes de IVA, traslado, viáticos y descuentos.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="Tarifa mínima (MXN)" for="qp-min-price" error="minimum_price" required>
                        <input id="qp-min-price" type="number" step="0.01" min="0.01" wire:model.live.debounce.500ms="minimum_price" class="input">
                    </x-field>
                    <x-field label="Superficie cubierta por la tarifa mínima (ha)" for="qp-min-ha" error="minimum_hectares" required>
                        <input id="qp-min-ha" type="number" step="0.01" min="0.01" wire:model.live.debounce.500ms="minimum_hectares" class="input">
                    </x-field>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase">
                                <th class="py-2 pr-3">Desde (ha)</th>
                                <th class="py-2 pr-3">Hasta (ha)</th>
                                <th class="py-2 pr-3">Precio por ha adicional</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $from = $minimum_hectares; @endphp
                            @foreach ($blocks as $i => $block)
                                <tr wire:key="block-{{ $i }}">
                                    <td class="py-2 pr-3 text-slate-600 tabular-nums">Más de {{ is_numeric($from) ? hectareas($from) : '—' }}</td>
                                    <td class="py-2 pr-3">
                                        @if ($loop->last)
                                            <span class="text-slate-500">En adelante</span>
                                        @else
                                            <input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="blocks.{{ $i }}.up_to" class="input w-36" aria-label="Hasta (ha)">
                                        @endif
                                        @error("blocks.$i.up_to")<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="py-2 pr-3">
                                        <div class="relative w-36">
                                            <span class="pointer-events-none absolute top-2 left-3 text-sm text-slate-400">$</span>
                                            <input type="number" step="0.01" min="0.01" wire:model.live.debounce.500ms="blocks.{{ $i }}.price" class="input pl-7" aria-label="Precio por hectárea">
                                        </div>
                                        @error("blocks.$i.price")<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="py-2 text-right">
                                        @if (! $loop->last && count($blocks) > 1)
                                            <button type="button" wire:click="removeBlock({{ $i }})" class="btn-ghost btn-sm text-rose-600" title="Quitar bloque"><x-icon name="trash" class="size-4" /></button>
                                        @endif
                                    </td>
                                </tr>
                                @php $from = $block['up_to'] ?? null; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" wire:click="addBlock" class="btn-secondary btn-sm mt-3"><x-icon name="plus" class="size-4" /> Agregar bloque</button>
            </section>

            {{-- Traslado, IVA, vigencia --}}
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Traslado, IVA y vigencia</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-field label="Tarifa por km recorrido (MXN)" for="qp-km" error="km_rate" required hint="Se cobra sobre ida y vuelta: distancia de ida × 2.">
                        <input id="qp-km" type="number" step="0.01" min="0" wire:model="km_rate" class="input">
                    </x-field>
                    <x-field label="IVA (%)" for="qp-vat" error="vat_rate" required>
                        <input id="qp-vat" type="number" step="0.01" min="0" max="100" wire:model="vat_rate" class="input">
                    </x-field>
                    <x-field label="Vigencia predeterminada (días naturales)" for="qp-valid" error="validity_days" required>
                        <input id="qp-valid" type="number" min="1" max="365" wire:model="validity_days" class="input">
                    </x-field>
                </div>
            </section>

            {{-- Textos del PDF --}}
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Textos de la cotización (PDF)</h2>
                <div class="mt-4 space-y-4">
                    <x-field label="Alcance del servicio" for="qp-scope" error="service_scope" hint="Qué incluye el servicio. Una idea por renglón; cada renglón se muestra como viñeta.">
                        <textarea id="qp-scope" wire:model="service_scope" rows="4" class="input"></textarea>
                    </x-field>
                    <x-field label="Gastos que se cobran por separado" for="qp-sep" error="separate_charges">
                        <textarea id="qp-sep" wire:model="separate_charges" rows="2" class="input"></textarea>
                    </x-field>
                    <x-field label="Condiciones de pago" for="qp-pay" error="payment_terms" hint="Se proponen al crear cada cotización y se pueden ajustar en ella.">
                        <textarea id="qp-pay" wire:model="payment_terms" rows="3" class="input" placeholder="Captura tus condiciones de pago (por ejemplo, anticipo y liquidación)."></textarea>
                    </x-field>
                </div>
            </section>

            {{-- Datos comerciales --}}
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Datos comerciales</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="Nombre comercial" for="qp-bname" error="business.name" required>
                        <input id="qp-bname" type="text" wire:model="business.name" class="input">
                    </x-field>
                    <x-field label="Correo" for="qp-bmail" error="business.email">
                        <input id="qp-bmail" type="email" wire:model="business.email" class="input">
                    </x-field>
                    <x-field label="WhatsApp" for="qp-bwa" error="business.whatsapp">
                        <input id="qp-bwa" type="text" wire:model="business.whatsapp" class="input">
                    </x-field>
                    <x-field label="Sitio web" for="qp-bweb" error="business.website">
                        <input id="qp-bweb" type="text" wire:model="business.website" class="input">
                    </x-field>
                    <x-field label="Dirección (opcional)" for="qp-baddr" error="business.address" class="sm:col-span-2">
                        <input id="qp-baddr" type="text" wire:model="business.address" class="input">
                    </x-field>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Vista previa del censo</h2>
                <p class="mt-1 text-xs text-slate-500">Con los valores capturados, antes de IVA, traslado, viáticos y descuentos.</p>
                @if ($this->preview)
                    <dl class="mt-3 divide-y divide-slate-100 text-sm">
                        @foreach ($this->preview as $ha => $price)
                            <div class="flex justify-between py-1.5"><dt class="text-slate-500">{{ hectareas($ha) }}</dt><dd class="font-medium tabular-nums">{{ money($price) }}</dd></div>
                        @endforeach
                    </dl>
                @else
                    <p class="mt-3 text-sm text-amber-700">Corrige los bloques para ver la vista previa.</p>
                @endif
            </section>

            <section class="card p-5">
                <x-field label="Nota del cambio (opcional)" for="qp-note" error="change_note" hint="Ej. «Ajuste de precios 2027».">
                    <input id="qp-note" type="text" wire:model="change_note" class="input" maxlength="255">
                </x-field>
                <button type="submit" class="btn-primary mt-4 w-full" wire:loading.attr="disabled">Guardar como nueva versión</button>
            </section>

            <section class="card">
                <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="font-semibold text-slate-900">Historial de tarifas</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($this->versions as $v)
                        <li class="px-5 py-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">{{ $v->label() }}</span>
                                @if ($v->is_current)<x-badge tone="emerald">Vigente</x-badge>@endif
                            </div>
                            <p class="text-xs text-slate-500">{{ fecha_hora($v->activated_at) }} · {{ $v->creator->name }}@if ($v->change_note) · {{ $v->change_note }}@endif</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </form>
</div>
