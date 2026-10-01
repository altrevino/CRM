<div>
    @php $quote = $showModal ? $this->quote : null; @endphp
    <x-modal title="Ajustar número y fechas" :subtitle="$quote ? $quote->number.' · '.$quote->opportunity->ranch->name : null" size="md">
        <form id="quote-adjust-form" wire:submit="save" class="space-y-4">
            <p class="text-sm text-slate-500">Para cotizaciones que vienen de tu sistema anterior. No cambia montos ni estado. Si cambias el folio, sus otras versiones también lo adoptan. Queda registrado en el historial.</p>

            <div class="flex items-start gap-2">
                <x-field label="Folio" for="qa-folio" error="folio" required class="flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm text-slate-500">COT-</span>
                        <input id="qa-folio" type="number" min="1" step="1" wire:model.live.debounce.400ms="folio" class="input" required>
                    </div>
                </x-field>
                <x-field label="Versión" for="qa-version" error="version" required class="w-24">
                    <input id="qa-version" type="number" min="1" max="99" step="1" wire:model.live.debounce.400ms="version" class="input" required>
                </x-field>
            </div>
            @if (is_numeric($folio) && is_numeric($version))
                <p class="-mt-2 text-sm">Quedará como <span class="font-semibold text-slate-900">{{ \App\Models\Quote::formatNumber((int) $folio, max(1, (int) $version)) }}</span></p>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-field label="Fecha de envío" for="qa-sent" error="sent_at" hint="Cuenta para la alerta de cotizaciones sin seguimiento.">
                    <input id="qa-sent" type="date" wire:model="sent_at" class="input">
                </x-field>
                @if ($quote?->status === \App\Enums\QuoteStatus::Accepted)
                    <x-field label="Fecha de aceptación" for="qa-accepted" error="accepted_at" hint="Define el mes en «Ventas por mes».">
                        <input id="qa-accepted" type="date" wire:model="accepted_at" class="input">
                    </x-field>
                @endif
            </div>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="quote-adjust-form" class="btn-primary" wire:loading.attr="disabled">Guardar</button>
        </x-slot:footer>
    </x-modal>
</div>
