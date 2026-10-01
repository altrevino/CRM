@props(['field', 'label', 'sortField' => null, 'sortDirection' => 'asc', 'align' => 'left'])
<th {{ $attributes->class(['table-th', 'text-right' => $align === 'right']) }}>
    <button type="button" wire:click="sortBy('{{ $field }}')" class="group inline-flex items-center gap-1 uppercase hover:text-slate-800 {{ $align === 'right' ? 'flex-row-reverse' : '' }}">
        {{ $label }}
        <span class="{{ $sortField === $field ? 'text-slate-700' : 'text-slate-300 group-hover:text-slate-400' }}">
            @if ($sortField === $field)
                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
            @else
                ↕
            @endif
        </span>
    </button>
</th>
