@props(['options' => [], 'placeholder' => null])
<select {{ $attributes->class(['input pr-8']) }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
