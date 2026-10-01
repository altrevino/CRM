<div>
    @php
        $title = match ($mode) {
            'edit' => 'Editar '.($this->baseQuote?->number ?? 'cotización'),
            'version' => 'Nueva versión de '.($this->baseQuote?->number ?? 'cotización'),
            default => 'Nueva cotización',
        };
        $subtitle = $this->opportunity ? $this->opportunity->ranch->name.' · '.$this->opportunity->ranch->client->name : null;
    @endphp
    <x-modal :title="$title" :subtitle="$subtitle">
        <form id="quote-form" wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-field label="Fecha" for="qf-date" error="issued_at" required>
                <input id="qf-date" type="date" wire:model="issued_at" class="input @error('issued_at') input-error @enderror" required>
            </x-field>
            <x-field label="Hectáreas cotizadas" for="qf-ha" error="hectares" required>
                <input id="qf-ha" type="number" step="0.01" min="0.01" wire:model.live.debounce.500ms="hectares" class="input @error('hectares') input-error @enderror" required>
            </x-field>

            <x-field label="Precio por hectárea (opcional)" for="qf-pph" hint="Calcula el importe del servicio. No se guarda.">
                <input id="qf-pph" type="number" step="0.01" min="0" wire:model.live.debounce.500ms="price_per_hectare" class="input" placeholder="45.00">
            </x-field>
            <x-field label="Importe del servicio" for="qf-service" error="service_amount" required>
                <input id="qf-service" type="number" step="0.01" min="0" wire:model.live.debounce.500ms="service_amount" class="input @error('service_amount') input-error @enderror" required>
            </x-field>

            <x-field label="Logística y movilización" for="qf-log" error="logistics_amount">
                <input id="qf-log" type="number" step="0.01" min="0" wire:model.live.debounce.500ms="logistics_amount" class="input @error('logistics_amount') input-error @enderror">
            </x-field>

            <x-field label="IVA" error="apply_vat" required>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 ring-inset hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800 has-[:checked]:ring-brand-400">
                        <input type="radio" wire:model.live="apply_vat" value="si" class="sr-only">Con IVA
                    </label>
                    <label class="flex cursor-pointer items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 ring-inset hover:bg-slate-50 has-[:checked]:bg-amber-50 has-[:checked]:text-amber-900 has-[:checked]:ring-amber-400">
                        <input type="radio" wire:model.live="apply_vat" value="no" class="sr-only">Sin IVA
                    </label>
                </div>
            </x-field>

            @if ($apply_vat === 'si')
                <x-field label="Porcentaje de IVA" for="qf-rate" error="vat_rate" hint="Se guarda en la cotización aunque después cambie el IVA del sistema.">
                    <div class="relative">
                        <input id="qf-rate" type="number" step="0.01" min="0" max="100" wire:model.live.debounce.500ms="vat_rate" class="input pr-8">
                        <span class="pointer-events-none absolute top-2 right-3 text-sm text-slate-400">%</span>
                    </div>
                </x-field>
            @endif

            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 sm:col-span-2">
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal (servicio + logística)</dt><dd class="font-medium tabular-nums">{{ money($this->preview['subtotal']) }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">IVA {{ $apply_vat === 'si' ? '('.(float) $vat_rate.'%)' : ($apply_vat === 'no' ? '(sin IVA)' : '') }}</dt>
                        <dd class="font-medium tabular-nums">{{ money($this->preview['vat']) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt class="font-semibold text-slate-900">Total</dt><dd class="font-semibold text-slate-900 tabular-nums">{{ money($this->preview['total']) }}</dd></div>
                </dl>
            </div>

            <x-field label="Notas" for="qf-notes" error="notes" class="sm:col-span-2">
                <textarea id="qf-notes" wire:model="notes" rows="2" class="input" placeholder="Condiciones, vigencia, forma de pago acordada…"></textarea>
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="quote-form" class="btn-primary" wire:loading.attr="disabled">
                {{ $mode === 'edit' ? 'Guardar cambios' : ($mode === 'version' ? 'Crear versión' : 'Crear cotización') }}
            </button>
        </x-slot:footer>
    </x-modal>
</div>
