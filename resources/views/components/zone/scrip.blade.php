@props([
    'amount',
])

<span {{ $attributes->class(['inline-flex items-center gap-1 font-mono tabular-nums']) }} title="{{ $amount }} scrip">
    <svg class="size-[1.1em] shrink-0 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
        <ellipse cx="12" cy="6" rx="7" ry="2.6" />
        <path d="M5 6v4c0 1.4 3.1 2.6 7 2.6s7-1.2 7-2.6V6" />
        <path d="M5 10v4c0 1.4 3.1 2.6 7 2.6s7-1.2 7-2.6v-4" />
        <path d="M5 14v4c0 1.4 3.1 2.6 7 2.6s7-1.2 7-2.6v-4" />
    </svg>
    <span>{{ number_format($amount) }}</span>
    <span class="sr-only">scrip</span>
</span>
