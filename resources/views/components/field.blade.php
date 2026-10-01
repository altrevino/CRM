@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null, 'required' => false])
<div {{ $attributes }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="label">
            {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($error)
        @error($error)
            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    @endif
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
