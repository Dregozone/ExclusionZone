@props(['seconds'])

@php
    $seconds = (int) $seconds;
    $text = match (true) {
        $seconds < 60 => $seconds.'s',
        $seconds % 60 === 0 && $seconds < 3600 => intdiv($seconds, 60).' min',
        $seconds < 3600 => intdiv($seconds, 60).'m '.($seconds % 60).'s',
        default => intdiv($seconds, 3600).'h '.intdiv($seconds % 3600, 60).'m',
    };
@endphp

<span {{ $attributes->class(['font-mono tabular-nums']) }}>{{ $text }}</span>
