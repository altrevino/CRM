<div>
    @php $opp = $showModal ? $this->opportunity : null; @endphp
    <x-modal :title="$paymentId ? 'Editar pago' : 'Registrar pago'" :subtitle="$opp ? $opp->ranch->name.' · '.$opp->ranch->client->name : null" size="md">
        @if ($opp)
            <div class="mb-5 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                @if ($opp->acceptedQuote)
                    <p class="mb-2 text-xs font-medium text-slate-500">Cotización aceptada {{ $opp->acceptedQuote->number }} · {{ $opp->acceptedQuote->vatLabel() }}</p>
                @endif
                <x-progress :paid="$opp->paidTotal()" :total="$opp->acceptedTotal()" />
                @if ($opp->acceptedTotal() === null)
                    <p class="mt-1 text-xs text-amber-700">El pago se registrará en el servicio. Cuando aceptes una cotización, el saldo se calculará contra ella.</p>
                @endif
            </div>
        @endif
        <form id="payment-form" wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-field label="Fecha" for="pf-date" error="paid_at" required>
                <input id="pf-date" type="date" wire:model="paid_at" class="input @error('paid_at') input-error @enderror" required>
            </x-field>
            <x-field label="Monto" for="pf-amount" error="amount" required>
                <div class="relative">
                    <span class="pointer-events-none absolute top-2 left-3 text-sm text-slate-400">$</span>
                    <input id="pf-amount" type="number" step="0.01" min="0.01" wire:model.live.debounce.400ms="amount" class="input pl-7 @error('amount') input-error @enderror" required>
                </div>
                @if ($opp && $opp->balance() !== null && is_numeric($amount) && ! $paymentId && (float) $amount > $opp->balance())
                    <p class="mt-1 text-xs text-amber-700">El monto supera el saldo pendiente ({{ money($opp->balance()) }}).</p>
                @endif
            </x-field>
            <x-field label="Forma de pago" for="pf-method" error="payment_method_id" required>
                <x-select id="pf-method" wire:model="payment_method_id" :options="$methods" required />
            </x-field>
            <x-field label="Referencia" for="pf-ref" error="reference">
                <input id="pf-ref" type="text" wire:model="reference" class="input" placeholder="Folio, núm. de cheque…" maxlength="100">
            </x-field>
            <x-field label="Notas" for="pf-notes" error="notes" class="sm:col-span-2">
                <textarea id="pf-notes" wire:model="notes" rows="2" class="input" placeholder="Anticipo 50%, liquidación…"></textarea>
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="payment-form" class="btn-primary" wire:loading.attr="disabled">Guardar pago</button>
        </x-slot:footer>
    </x-modal>
</div>
