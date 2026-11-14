@props([
    'item',
    'quantity' => null,
])

@php
    $ring = match ($item->rarity?->value) {
        'uncommon' => 'border-emerald-400/60',
        'rare' => 'border-sky-400/70',
        'epic' => 'border-fuchsia-400/70',
        'scorched' => 'border-orange-400/80 shadow-[0_0_10px_rgba(251,146,60,0.35)]',
        default => 'border-zinc-600',
    };
@endphp

<span {{ $attributes->class(['relative block shrink-0 overflow-hidden rounded-sm border bg-zinc-950', $ring]) }}>
    <img src="{{ $item->imageUrl() }}" alt="" loading="lazy" decoding="async" width="128" height="128" class="size-full object-cover">
    @if ($quantity !== null)
        <span class="absolute right-0.5 bottom-0.5 rounded-[2px] bg-black/75 px-1 font-mono text-[10px] leading-4 font-semibold text-ez-ink tabular-nums">×{{ $quantity }}</span>
    @endif
</span>
