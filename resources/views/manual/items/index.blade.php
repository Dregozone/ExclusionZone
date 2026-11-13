@php
    $kinds = [
        'food' => 'Food',
        'medical' => 'Medical',
        'weapon' => 'Weapons',
        'quest' => 'Quest items',
        'material' => 'Materials',
    ];
    $kindOf = fn ($item): string => match (true) {
        $item->is_quest_item => 'quest',
        $item->isMedical() => 'medical',
        $item->isFood() => 'food',
        $item->isWeapon() => 'weapon',
        default => 'material',
    };
    $rarityTone = [
        'common' => 'text-ez-ink',
        'uncommon' => 'text-emerald-300',
        'rare' => 'text-sky-300',
        'epic' => 'text-fuchsia-300',
        'scorched' => 'text-orange-300',
    ];
@endphp

<x-manual.layout
    title="Items"
    lede="Everything you can carry. Open an item to see exactly where it comes from and what it is used for."
    :breadcrumbs="['Items' => null]"
>
    <div x-data="{ kind: 'all', rarity: 'all', text: '' }" class="space-y-6">
        {{-- Filters --}}
        <div class="ez-panel flex flex-col gap-4 p-4 lg:flex-row lg:items-center">
            <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filter by type">
                @foreach (['all' => 'All'] + $kinds as $value => $label)
                    <button type="button" @click="kind = '{{ $value }}'" :aria-pressed="kind === '{{ $value }}'" :class="kind === '{{ $value }}' ? 'border-ez-signal bg-ez-signal/10 text-ez-signal' : 'border-zinc-700 text-ez-muted hover:text-ez-ink'" class="rounded-sm border px-2.5 py-1 text-xs transition">{{ $label }}</button>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filter by rarity">
                <button type="button" @click="rarity = 'all'" :aria-pressed="rarity === 'all'" :class="rarity === 'all' ? 'border-ez-signal bg-ez-signal/10 text-ez-signal' : 'border-zinc-700 text-ez-muted hover:text-ez-ink'" class="rounded-sm border px-2.5 py-1 text-xs transition">Any rarity</button>
                @foreach ($rarities as $rarity)
                    <button type="button" @click="rarity = '{{ $rarity->value }}'" :aria-pressed="rarity === '{{ $rarity->value }}'" :class="rarity === '{{ $rarity->value }}' ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700 hover:bg-white/5'" class="rounded-sm border px-2.5 py-1 text-xs transition {{ $rarityTone[$rarity->value] }}">{{ $rarity->label() }}</button>
                @endforeach
            </div>
            <label class="relative lg:ml-auto lg:w-56">
                <span class="sr-only">Filter items by name</span>
                <input x-model="text" type="search" placeholder="Filter by name…" class="w-full rounded-sm border border-zinc-700 bg-ez-ground px-3 py-1.5 text-sm text-ez-ink placeholder:text-zinc-500 focus:border-ez-signal focus:outline-none" />
            </label>
        </div>

        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                @php($kind = $kindOf($item))
                <li
                    x-show="(kind === 'all' || kind === '{{ $kind }}') && (rarity === 'all' || rarity === '{{ $item->rarity->value }}') && @js(Str::lower($item->name)).includes(text.toLowerCase())"
                    x-transition.opacity
                >
                    <a href="{{ route('manual.item', $item->key) }}" @class(['ez-card group flex h-full flex-col p-4 transition hover:border-ez-signal/60', 'ez-heat' => $item->rarity->isScorcherExclusive()]) wire:navigate>
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold text-ez-ink group-hover:text-ez-signal">{{ $item->name }}</p>
                            <span class="shrink-0 font-mono text-[10px] tracking-wider uppercase {{ $rarityTone[$item->rarity->value] }}">{{ $item->rarity->label() }}</span>
                        </div>
                        <p class="mt-1 line-clamp-2 text-sm text-ez-muted">{{ $item->description }}</p>
                        <div class="mt-auto flex flex-wrap gap-1.5 pt-3 text-[11px]">
                            <span class="rounded-sm border border-zinc-700 px-1.5 py-0.5 text-ez-muted">{{ $kinds[$kind] }}</span>
                            @if ($item->hunger_restored)<span class="rounded-sm border border-zinc-700 px-1.5 py-0.5 text-ez-signal">Hunger −{{ $item->hunger_restored }}</span>@endif
                            @if ($item->fatigue_restored)<span class="rounded-sm border border-zinc-700 px-1.5 py-0.5 text-ez-signal">Fatigue −{{ $item->fatigue_restored }}</span>@endif
                            @if ($item->health_restored)<span class="rounded-sm border border-zinc-700 px-1.5 py-0.5 text-emerald-300">Health +{{ $item->health_restored }}</span>@endif
                            @if ($item->attack_bonus)<span class="rounded-sm border border-zinc-700 px-1.5 py-0.5 text-fuchsia-300">Attack +{{ $item->attack_bonus }}</span>@endif
                            @if ($producedCounts[$item->key] ?? false)<span class="rounded-sm border border-zinc-700 px-1.5 py-0.5 text-ez-muted">Made by {{ $producedCounts[$item->key] }} {{ Str::plural('action', $producedCounts[$item->key]) }}</span>@endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-manual.layout>
