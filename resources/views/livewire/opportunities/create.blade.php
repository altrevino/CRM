<div>
    <x-page-header title="Nuevo servicio" subtitle="Cliente, rancho y servicio en una sola captura" />

    <form wire:submit="save" class="mx-auto max-w-3xl space-y-6">
        {{-- 1. Cliente --}}
        <section class="card p-5 sm:p-6">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-7 items-center justify-center rounded-full {{ $client_id || $clientMode === 'new' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600' }} text-sm font-semibold">1</span>
                <h2 class="font-semibold text-slate-900">Cliente</h2>
            </div>

            @if ($clientMode === 'existing')
                @if ($this->client)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-brand-50/60 px-4 py-3 ring-1 ring-brand-200">
                        <div>
                            <p class="font-medium text-slate-900">{{ $this->client->name }}</p>
                            <p class="text-sm text-slate-500">{{ $this->client->formattedPhone() }}</p>
                        </div>
                        <button type="button" wire:click="clearClient" class="btn-ghost btn-sm">Cambiar</button>
                    </div>
                @else
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400" />
                        <input type="text" wire:model.live.debounce.300ms="clientSearch" class="input pl-9 @error('client_id') input-error @enderror" placeholder="Buscar cliente por nombre o teléfono…" autocomplete="off" autofocus>
                    </div>
                    @error('client_id')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    @if ($this->clientResults->isNotEmpty())
                        <ul class="mt-2 divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                            @foreach ($this->clientResults as $result)
                                <li>
                                    <button type="button" wire:click="selectClient('{{ $result->id }}')" class="flex w-full items-center justify-between px-4 py-2.5 text-left hover:bg-slate-50">
                                        <span class="font-medium text-slate-800">{{ $result->name }}</span>
                                        <span class="text-xs text-slate-500">{{ $result->formattedPhone() }} · {{ $result->ranches_count }} ranchos</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <button type="button" wire:click="newClient" class="btn-secondary btn-sm mt-3"><x-icon name="plus" class="size-4" /> Crear cliente nuevo</button>
                @endif
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="Nombre completo" for="w-cname" error="client_name" required>
                        <input id="w-cname" type="text" wire:model.live.debounce.400ms="client_name" class="input @error('client_name') input-error @enderror" placeholder="Juan Pérez Garza">
                    </x-field>
                    <x-field label="Teléfono" for="w-cphone" error="client_phone" required>
                        <input id="w-cphone" type="tel" inputmode="tel" wire:model.live.debounce.400ms="client_phone" class="input @error('client_phone') input-error @enderror" placeholder="81 1234 5678">
                    </x-field>
                </div>
                @if ($this->clientDuplicates->isNotEmpty())
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm">
                        <p class="flex items-center gap-1.5 font-medium text-amber-900"><x-icon name="alert" class="size-4" /> ¿Es alguno de estos clientes?</p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($this->clientDuplicates as $match)
                                <li class="flex items-center justify-between gap-2">
                                    <span class="truncate text-slate-800">{{ $match['client']->name }} <span class="text-xs text-amber-800">· {{ $match['reason'] }}</span></span>
                                    <button type="button" wire:click="selectClient('{{ $match['client']->id }}')" class="btn-secondary btn-sm">Usar este</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <button type="button" wire:click="$set('clientMode', 'existing')" class="btn-ghost btn-sm mt-3">← Buscar cliente existente</button>
            @endif
        </section>

        {{-- 2. Rancho --}}
        <section class="card p-5 sm:p-6 {{ ! $client_id && $clientMode !== 'new' ? 'pointer-events-none opacity-50' : '' }}">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-7 items-center justify-center rounded-full {{ $ranch_id || $ranchMode === 'new' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600' }} text-sm font-semibold">2</span>
                <h2 class="font-semibold text-slate-900">Rancho</h2>
            </div>

            @if ($clientMode === 'existing' && $ranchMode === 'existing')
                @if ($this->clientRanches->isNotEmpty())
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($this->clientRanches as $ranch)
                            <button type="button" wire:click="selectRanch('{{ $ranch->id }}')" wire:key="wr-{{ $ranch->id }}"
                                    class="rounded-xl px-4 py-3 text-left ring-1 transition {{ $ranch_id === $ranch->id ? 'bg-brand-50 ring-2 ring-brand-500' : 'ring-slate-200 hover:bg-slate-50' }}">
                                <p class="font-medium text-slate-900">{{ $ranch->name }}</p>
                                <p class="text-xs text-slate-500">{{ $ranch->location() }} · {{ hectareas($ranch->total_hectares) }}</p>
                            </button>
                        @endforeach
                    </div>
                @endif
                @error('ranch_id')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                <button type="button" wire:click="newRanch" class="btn-secondary btn-sm mt-3"><x-icon name="plus" class="size-4" /> Rancho nuevo</button>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="Nombre del rancho" for="w-rname" error="ranch_name" required class="sm:col-span-2">
                        <input id="w-rname" type="text" wire:model.live.debounce.400ms="ranch_name" class="input @error('ranch_name') input-error @enderror" placeholder="Rancho El Venado">
                    </x-field>
                    @if ($this->ranchDuplicates->isNotEmpty())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:col-span-2">
                            <p class="font-medium">Este cliente ya tiene un rancho con nombre parecido:</p>
                            @foreach ($this->ranchDuplicates as $dup)
                                <button type="button" wire:click="selectRanch('{{ $dup->id }}')" class="mt-1 block underline">Usar {{ $dup->name }} ({{ $dup->municipality }})</button>
                            @endforeach
                        </div>
                    @endif
                    <x-field label="Municipio" for="w-mun" error="municipality" required>
                        <input id="w-mun" type="text" wire:model="municipality" list="w-municipalities" class="input @error('municipality') input-error @enderror" placeholder="Anáhuac">
                        <datalist id="w-municipalities">
                            @foreach ($municipalities as $m)<option value="{{ $m }}">@endforeach
                        </datalist>
                    </x-field>
                    <x-field label="Estado" for="w-state" error="state_id" required>
                        <x-select id="w-state" wire:model="state_id" :options="$states" placeholder="Selecciona…" />
                    </x-field>
                    <x-field label="Superficie total (ha)" for="w-ha" error="total_hectares">
                        <input id="w-ha" type="number" step="0.01" min="0.01" wire:model.blur="total_hectares" class="input @error('total_hectares') input-error @enderror">
                    </x-field>
                    <x-field label="Tipo de cerca" for="w-fence" error="fence_type">
                        <x-select id="w-fence" wire:model="fence_type" :options="$fenceTypes" placeholder="Sin definir" />
                    </x-field>
                    <x-field label="Liga de Google Maps" for="w-maps" error="maps_url">
                        <input id="w-maps" type="url" wire:model="maps_url" class="input @error('maps_url') input-error @enderror" placeholder="https://maps.app.goo.gl/…">
                    </x-field>
                    <x-field label="Km desde Monterrey (redondo)" for="w-km" error="km_round_trip">
                        <input id="w-km" type="number" step="0.1" min="0" wire:model="km_round_trip" class="input @error('km_round_trip') input-error @enderror">
                    </x-field>
                </div>
                @if ($clientMode === 'existing' && $this->clientRanches->isNotEmpty())
                    <button type="button" wire:click="$set('ranchMode', 'existing')" class="btn-ghost btn-sm mt-3">← Elegir un rancho existente</button>
                @endif
            @endif
        </section>

        {{-- 3. Servicio --}}
        <section class="card p-5 sm:p-6">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-7 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">3</span>
                <h2 class="font-semibold text-slate-900">Servicio</h2>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @include('livewire.partials.opportunity-fields')
                <x-field label="Etapa inicial" for="w-stage" error="stage">
                    <x-select id="w-stage" wire:model="stage" :options="$stages" />
                </x-field>
            </div>
        </section>

        {{-- Seguimiento --}}
        <section class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">Primer seguimiento <span class="font-normal text-slate-400">(opcional)</span></h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-field label="Fecha" for="w-fdate" error="followup_date">
                    <input id="w-fdate" type="date" wire:model="followup_date" class="input">
                </x-field>
                <x-field label="¿Qué hay que hacer?" for="w-ftitle" error="followup_title" class="sm:col-span-2">
                    <input id="w-ftitle" type="text" wire:model="followup_title" class="input" placeholder="Enviar cotización / Llamar para confirmar fechas">
                </x-field>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ url()->previous() }}" wire:navigate class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary px-6" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Guardar servicio</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </button>
        </div>
    </form>
</div>
