{{-- Rango de fechas compacto. Requiere propiedades $dateFrom y $dateTo en el componente. --}}
<div class="flex w-full items-center gap-1.5 sm:w-auto">
    <label for="{{ $id ?? 'range' }}-from" class="shrink-0 text-xs font-medium text-slate-500">Del</label>
    <input id="{{ $id ?? 'range' }}-from" type="date" wire:model.live="dateFrom" class="input min-w-0 flex-1 sm:w-[9.5rem] sm:flex-none">
    <label for="{{ $id ?? 'range' }}-to" class="shrink-0 text-xs font-medium text-slate-500">al</label>
    <input id="{{ $id ?? 'range' }}-to" type="date" wire:model.live="dateTo" class="input min-w-0 flex-1 sm:w-[9.5rem] sm:flex-none">
</div>
