<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.empty')] class extends Component
{
    //
};
?>

<div class="text-ez-ink">
    @php
        $cities = [
            ['Pripyat', 'Ukraine', '51.39° N'],
            ['Kyiv', 'Ukraine', '50.45° N'],
            ['Warsaw', 'Poland', '52.23° N'],
            ['Gdansk', 'Poland', '54.35° N'],
            ['Detroit', 'United States', '42.33° N'],
            ['Seattle', 'United States', '47.61° N'],
            ['Tokyo', 'Japan', '35.68° N'],
            ['Sapporo', 'Japan', '43.06° N'],
            ['Rio de Janeiro', 'Brazil', '22.91° S'],
            ['Manaus', 'Brazil', '3.12° S'],
            ['Johannesburg', 'South Africa', '26.20° S'],
            ['Cape Town', 'South Africa', '33.92° S'],
        ];
    @endphp

    {{-- Top bar --}}
    <header class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-ez-signal" wire:navigate>
            <x-app-logo-icon class="size-8" />
            <span class="ez-display text-xl text-ez-ink">Exclusion Zone</span>
        </a>
        <nav class="flex items-center gap-1 text-sm sm:gap-2" aria-label="Site">
            <a href="{{ route('world-map') }}" class="hidden rounded-sm px-3 py-2 text-ez-muted transition hover:text-ez-ink sm:inline-flex" wire:navigate>World Map</a>
            <a href="{{ route('high-scores') }}" class="hidden rounded-sm px-3 py-2 text-ez-muted transition hover:text-ez-ink sm:inline-flex" wire:navigate>High Scores</a>
            <a href="{{ route('manual.index') }}" class="rounded-sm px-3 py-2 text-ez-muted transition hover:text-ez-ink" wire:navigate>Manual</a>
            @auth
                <a href="{{ route('dashboard') }}" class="ez-button !min-h-10 !px-4 !py-2 text-sm">Enter your city menu</a>
            @else
                <a href="{{ route('login') }}" class="rounded-sm px-3 py-2 text-ez-muted transition hover:text-ez-ink">Login</a>
                <a href="{{ route('register') }}" class="ez-button !min-h-10 !px-4 !py-2 text-sm">Register</a>
            @endauth
        </nav>
    </header>

    {{-- Hero: the place, not the product --}}
    <section class="relative mx-auto max-w-7xl px-4 pt-10 pb-16 sm:px-8 lg:pt-20">
        <div class="grid items-end gap-10 lg:grid-cols-[1.35fr_0.65fr]">
            <div>
                <p class="ez-label !text-ez-signal">51.3890° N · 30.0990° E · Text-based survival MMO</p>
                <h1 class="ez-display mt-4 text-[clamp(4rem,13vw,11rem)] text-ez-ink">Exclusion Zone</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-ez-muted sm:text-xl">
                    Build a survivor in a fractured world of irradiated ruins, fortified hubs, black markets and long scavenging runs.
                    One click is one move or one action, so it's quick to learn and hard to put down.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="ez-button">Enter your city menu</a>
                    @else
                        <a href="{{ route('register') }}" class="ez-button">Enter the Zone</a>
                        <a href="{{ route('login') }}" class="ez-button ez-button-ghost">I already have a survivor</a>
                    @endauth
                    <a href="{{ route('world-map') }}" class="ez-button ez-button-ghost !border-zinc-600 !text-ez-ink" wire:navigate>See the map</a>
                    <a href="{{ route('manual.guide', 'getting-started') }}" class="ez-button ez-button-ghost !border-zinc-600 !text-ez-ink" wire:navigate>How to play</a>
                </div>
            </div>

            <aside class="ez-panel ez-scanlines p-5" aria-label="Field report">
                <div class="flex items-center justify-between gap-3">
                    <p class="ez-label !text-amber-400">Field report · Channel 7</p>
                    <span class="relative flex size-2 text-ez-phosphor" aria-hidden="true"><span class="ez-ping absolute inset-0 rounded-full"></span><span class="relative size-2 rounded-full bg-current"></span></span>
                </div>
                <div class="mt-4 space-y-3 text-sm leading-6">
                    <p class="ez-phosphor">&gt; Kyiv sectors are holding. Pripyat is still glowing. Detroit factories are full of broken steel and armed scavengers.</p>
                    <p class="ez-phosphor">&gt; Tokyo tech ruins keep producing parts. Manaus pays well if you can survive the jungle edge.</p>
                    <p class="ez-phosphor ez-cursor">&gt; Premium backers get style, not strength. Nobody buys victory in the Zone.</p>
                </div>
            </aside>
        </div>
    </section>

    {{-- Numerals: the Zone in four figures --}}
    <section class="border-y border-zinc-700 bg-ez-ground/60 backdrop-blur-sm">
        <dl class="mx-auto grid max-w-7xl grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['12', 'Cities', 'Each with its own actions, weather and trouble.'],
                ['6', 'Countries', 'From the Ukrainian ruins to the Brazilian jungle.'],
                ['1', 'Click per move', 'Every action and every journey is a single decision.'],
                ['0', 'Pay-to-win', 'Premium is cosmetic. Nobody buys strength.'],
            ] as [$figure, $label, $detail])
                <div @class(['border-zinc-700 px-4 py-8 sm:px-8', 'border-r' => ! $loop->last, 'max-lg:border-b' => $loop->index < 2, 'max-lg:border-r-0' => $loop->index === 1])>
                    <dd class="ez-numeral text-[clamp(5rem,12vw,9rem)] text-ez-ink">{{ $figure }}</dd>
                    <dt class="ez-label mt-3 !text-ez-signal">{{ $label }}</dt>
                    <p class="mt-2 max-w-[26ch] text-sm text-ez-muted">{{ $detail }}</p>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Gazetteer of cities --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="ez-label">Gazetteer</p>
                <h2 class="ez-display mt-2 text-5xl text-ez-ink sm:text-6xl">Twelve places left standing</h2>
            </div>
            <a href="{{ route('world-map') }}" class="ez-label !text-ez-signal hover:underline" wire:navigate>Open the world map →</a>
        </div>
        <ol class="mt-10 grid gap-px overflow-hidden rounded-sm border border-zinc-700 bg-zinc-700 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cities as [$name, $country, $latitude])
                <li class="group bg-ez-ground/90 p-5 transition hover:bg-zinc-800">
                    <p class="font-mono text-[11px] text-zinc-500">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ $latitude }}</p>
                    <p class="ez-display mt-2 text-3xl text-ez-ink group-hover:text-ez-signal">{{ $name }}</p>
                    <p class="mt-1 text-sm text-ez-muted">{{ $country }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- How it plays --}}
    <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-8">
        <div class="grid gap-5 lg:grid-cols-3">
            @foreach ([
                ['Survive the place you are in', 'Every city has its own work, enemies, weather and food. Hunger and fatigue slow your timers, and radiation and fights wear down your health.'],
                ['Cross the map one decision at a time', 'Travel between linked cities. Trouble waits at the edge of some: fight, pay your way in, or run.'],
                ['Talk, trade and team up', 'Radio chat, a trade board at every post, quests from field contacts, and a leaderboard for every skill.'],
            ] as [$heading, $copy])
                <div class="ez-card ez-pin p-6 pt-7">
                    <h3 class="text-xl font-semibold text-ez-ink">{{ $heading }}</h3>
                    <p class="mt-3 text-sm leading-7 text-ez-muted">{{ $copy }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <footer class="border-t border-zinc-700">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-ez-muted sm:flex-row sm:justify-between sm:px-8">
            <p>&copy; {{ date('Y') }} Exclusion Zone.</p>
            <p>A fictional post-nuclear game about survival, atmosphere and fair progression.</p>
        </div>
    </footer>
</div>
