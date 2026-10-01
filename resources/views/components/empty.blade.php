@props(['icon' => 'sparkles', 'title' => 'Sin registros', 'description' => null])
<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-10 text-center']) }}>
    <span class="mb-3 rounded-full bg-slate-100 p-3 text-slate-400"><x-icon :name="$icon" class="size-6" /></span>
    <p class="text-sm font-medium text-slate-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
