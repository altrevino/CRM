<div>
    <x-page-header title="Configuración" subtitle="Parámetros del sistema y catálogos" />
    @include('livewire.settings.nav')

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form wire:submit="save" class="card space-y-5 p-5 lg:col-span-2">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-field label="IVA predeterminado (%)" for="s-vat" error="vat_rate" hint="Se usa al crear cotizaciones Con IVA. Las cotizaciones existentes conservan su porcentaje.">
                    <input id="s-vat" type="number" step="0.01" min="0" max="100" wire:model="vat_rate" class="input">
                </x-field>
                <x-field label="Folio inicial de cotizaciones" for="s-folio" error="quote_folio_start" hint="El siguiente número será el mayor entre este valor y el último folio + 1.">
                    <input id="s-folio" type="number" min="1" wire:model="quote_folio_start" class="input">
                </x-field>
                <x-field label="Días para alertar cotización sin seguimiento" for="s-days" error="quote_followup_days" hint="Cotización enviada sin actividad posterior durante este número de días.">
                    <input id="s-days" type="number" min="1" max="90" wire:model="quote_followup_days" class="input">
                </x-field>
                <x-field label="Días para considerar un censo «próximo»" for="s-census" error="upcoming_census_days">
                    <input id="s-census" type="number" min="1" max="90" wire:model="upcoming_census_days" class="input">
                </x-field>
            </div>
            <div class="flex justify-end"><button type="submit" class="btn-primary">Guardar configuración</button></div>
        </form>

        <section class="card">
            <div class="border-b border-slate-100 px-5 py-3.5">
                <h2 class="font-semibold text-slate-900">Estados de México</h2>
                <p class="text-xs text-slate-500">Catálogo fijo ({{ $states->count() }})</p>
            </div>
            <ul class="max-h-80 divide-y divide-slate-100 overflow-y-auto text-sm">
                @foreach ($states as $state)
                    <li class="flex justify-between px-5 py-2"><span>{{ $state->name }}</span><span class="text-slate-400">{{ $state->abbreviation }}</span></li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
