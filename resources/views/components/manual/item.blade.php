@props([
    'item' => null,
    'key' => null,
    'quantity' => null,
    'size' => 'sm',
])

@php
    $rarity = $item?->rarity?->value ?? 'common';
    $tone = match ($rarity) {
        'uncommon' => 'border-emerald-500/40 text-emerald-300',
        'rare' => 'border-sky-400/40 text-sky-300',
        'epic' => 'border-fuchsia-400/40 text-fuchsia-300',
        'scorched' => 'border-orange-500/60 text-orange-300 shadow-[0_0_12px_rgb(255_107_74/0.25)]',
        default => 'border-zinc-600 text-ez-ink',
    };
    $name = $item?->name ?? \Illuminate\Support\Str::headline((string) $key);
@endphp

@if ($item)
    <a
        href="{{ route('manual.item', $item->key) }}"
        {{ $attributes->class(['inline-flex max-w-full items-center gap-1.5 rounded-sm border bg-ez-ground/70 transition hover:bg-white/5', $tone, 'px-2 py-0.5 text-xs' => $size === 'sm', 'px-3 py-1.5 text-sm' => $size === 'md']) }}
        title="{{ $item->rarity->label() }} · {{ $item->name }}"
        wire:navigate
    >
@else
    <span {{ $attributes->class(['inline-flex max-w-full items-center gap-1.5 rounded-sm border border-dashed border-zinc-600 px-2 py-0.5 text-xs text-ez-muted']) }}>
@endif
        @if ($quantity)
            <span class="font-mono tabular-nums opacity-80">{{ $quantity }}×</span>
        @endif
        <span class="truncate">{{ $name }}</span>
@if ($item)
    </a>
@else
    </span>
@endif
