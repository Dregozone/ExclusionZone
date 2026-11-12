@props([
    'label',
    'value',
    'maximum' => 100,
    'tone' => 'ok',
    'reading' => null,
    'caption' => null,
    'compact' => false,
])

@php
    $percent = (int) round($value / max(1, $maximum) * 100);
    $toneClass = match ($tone) {
        'danger' => 'text-red-400',
        'warn' => 'text-amber-400',
        default => 'text-emerald-400',
    };
    $toneIcon = match ($tone) {
        'danger' => '▲▲',
        'warn' => '▲',
        default => null,
    };
@endphp

<div {{ $attributes->class(['grid gap-1.5']) }}>
    <div class="flex items-baseline justify-between gap-3">
        <p @class(['ez-label', '!text-[10px]' => $compact])>{{ $label }}</p>
        <p @class(['font-mono tabular-nums text-ez-ink', 'text-xs' => $compact, 'text-sm' => ! $compact])>
            @if ($toneIcon)
                <span class="{{ $toneClass }}" aria-hidden="true">{{ $toneIcon }}</span>
            @endif
            {{ $reading ?? "{$value} / {$maximum}" }}
        </p>
    </div>
    <div
        @class(['ez-meter', '!h-2' => $compact])
        role="meter"
        aria-label="{{ $label }}"
        aria-valuemin="0"
        aria-valuemax="{{ $maximum }}"
        aria-valuenow="{{ $value }}"
    >
        <div class="ez-meter-fill {{ $toneClass }}" style="width: {{ $percent }}%"></div>
    </div>
    @if ($caption)
        <p @class(['text-xs', 'text-ez-muted' => $tone === 'ok', $toneClass => $tone !== 'ok'])>{{ $caption }}</p>
    @endif
</div>
