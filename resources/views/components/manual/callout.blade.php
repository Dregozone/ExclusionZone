@props([
    'type' => 'tip',
    'title' => null,
])

@php
    [$tone, $icon, $label] = match ($type) {
        'warn' => ['border-amber-400/50 bg-amber-400/5', 'exclamation-triangle', 'Watch out'],
        'danger' => ['border-red-500/50 bg-red-500/5', 'fire', 'Danger'],
        'lore' => ['border-ez-phosphor/30 bg-black/30', 'signal', 'Intercept'],
        default => ['border-ez-signal/40 bg-ez-signal/5', 'light-bulb', 'Tip'],
    };
    $iconTone = match ($type) {
        'warn' => 'text-amber-400',
        'danger' => 'text-red-400',
        'lore' => 'text-ez-phosphor',
        default => 'text-ez-signal',
    };
@endphp

<aside {{ $attributes->class(['flex gap-3 rounded-sm border-l-2 p-4', $tone]) }}>
    <flux:icon :name="$icon" variant="mini" class="mt-0.5 shrink-0 {{ $iconTone }}" />
    <div class="min-w-0 text-sm leading-6 text-ez-ink">
        <p class="ez-label !text-[10px] {{ $iconTone }}">{{ $title ?? $label }}</p>
        <div @class(['mt-1', 'ez-phosphor' => $type === 'lore'])>{{ $slot }}</div>
    </div>
</aside>
