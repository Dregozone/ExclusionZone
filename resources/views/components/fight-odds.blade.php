@props([
    'enemy',
    'showName' => true,
])

@php
    /** @var \App\FightOdds $odds */
    $odds = $enemy['odds'];
@endphp

{{-- How a fight against one enemy is likely to go for the survivor as they are now. --}}
<div {{ $attributes->class(['rounded-sm border border-zinc-800 bg-zinc-950/50 px-2.5 py-2 text-xs']) }}>
    <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
        @if ($showName)
            <span class="font-medium text-ez-ink">{{ $enemy['name'] }}</span>
        @endif
        <span @class([
            'rounded-sm border px-1.5 py-px font-mono text-[10px] uppercase tracking-wider',
            'border-emerald-400/50 text-emerald-300' => $odds === \App\FightOdds::Easy,
            'border-amber-400/50 text-amber-300' => $odds === \App\FightOdds::Fair,
            'border-orange-400/60 text-orange-300' => $odds === \App\FightOdds::Risky,
            'border-red-400/60 text-red-300' => $odds === \App\FightOdds::Deadly,
        ]) data-odds="{{ $odds->value }}">{{ $odds->label() }}</span>
        <span class="text-ez-muted">{{ $odds->advice() }}</span>
    </p>
    <p class="mt-1 font-mono text-[11px] text-zinc-400">
        You land {{ $enemy['survivor_hit_chance_percent'] }}% · it lands {{ $enemy['enemy_hit_chance_percent'] }}%, up to {{ $enemy['enemy_max_hit'] }} a hit · about {{ $enemy['expected_rounds'] }} {{ Str::plural('round', $enemy['expected_rounds']) }} and {{ $enemy['expected_health_lost'] }} health
    </p>
</div>
