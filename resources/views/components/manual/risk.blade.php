@props(['level'])

@php
    $tone = match ($level) {
        'extreme' => 'border-red-500/60 bg-red-500/10 text-red-300',
        'high' => 'border-orange-500/50 bg-orange-500/10 text-orange-300',
        'medium' => 'border-amber-400/40 text-amber-300',
        default => 'border-zinc-600 text-ez-muted',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-sm border px-1.5 py-0.5 font-mono text-[10px] tracking-wider uppercase', $tone]) }}>{{ $level ?? 'low' }}</span>
