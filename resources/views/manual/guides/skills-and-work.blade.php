@php
    use App\Actions\Game\BuildManualCatalogue;
    use App\Weather;

    $levels = [1, 2, 3, 5, 10, 15, 20, 25, 30, 40, 50, 75, 100];
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 03 · Progression"
    lede="Every job in the Zone works the same way: a timer, a reward, and XP for one skill. Here is how the numbers fit together."
    :image="$guide['image']"
    :breadcrumbs="['Guides' => route('manual.guide', 'getting-started'), $guide['title'] => null]"
    :toc="['loop' => 'The work loop', 'xp' => 'XP and levels', 'calculator' => 'XP calculator', 'tiers' => 'Skill tiers', 'speed' => 'What changes a timer']"
>
    <x-manual.section id="loop" title="The work loop">
        <ol class="grid gap-3 md:grid-cols-4">
            @foreach ([
                ['Begin work', 'Choose an action card in your city. You need the level it asks for and any items it uses.'],
                ['Timer runs', 'One cycle takes the action’s time, adjusted for your level, the weather and your condition.'],
                ['Cycle pays', 'You get the item reward and XP, pay the hunger and fatigue cost, and items it uses are taken.'],
                ['Repeat', 'The next cycle starts on its own, until you stop, run out of inputs, or 8 hours pass.'],
            ] as [$name, $copy])
                <li class="ez-card relative p-4">
                    <span class="ez-numeral text-5xl text-ez-signal">{{ $loop->iteration }}</span>
                    <p class="mt-2 font-semibold text-ez-ink">{{ $name }}</p>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $copy }}</p>
                    @unless ($loop->last)
                        <flux:icon.arrow-right class="absolute top-1/2 -right-3 z-10 hidden -translate-y-1/2 text-zinc-600 md:block" />
                    @endunless
                </li>
            @endforeach
        </ol>
        <x-manual.callout class="mt-4">Production actions (cooking, crafting, medicine…) turn input items into better ones. If an action shows items under “Needs”, gather those first. Each item page shows where to get it.</x-manual.callout>
    </x-manual.section>

    <x-manual.section id="xp" title="XP and levels" intro="Climbing from level L to L+1 costs L × 100 XP. There is no level cap.">
        <div class="ez-panel overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-700 text-left">
                        <th scope="col" class="ez-label px-4 py-3 font-normal">Level</th>
                        <th scope="col" class="ez-label px-4 py-3 text-right font-normal">XP to next level</th>
                        <th scope="col" class="ez-label px-4 py-3 text-right font-normal">Total XP to reach</th>
                        <th scope="col" class="ez-label px-4 py-3 text-right font-normal">Timer speed-up</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($levels as $level)
                        <tr class="border-b border-zinc-800 last:border-0">
                            <td class="px-4 py-2.5"><span class="ez-numeral text-2xl text-ez-ink">{{ $level }}</span></td>
                            <td class="px-4 py-2.5 text-right font-mono tabular-nums text-ez-ink">{{ number_format(BuildManualCatalogue::experienceToNextLevel($level)) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono tabular-nums text-ez-muted">{{ number_format(BuildManualCatalogue::totalExperienceForLevel($level)) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono tabular-nums text-ez-signal">−{{ min(100, $level - 1) * BuildManualCatalogue::TIMER_REDUCTION_PERCENT_PER_LEVEL }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-manual.section>

    <x-manual.section id="calculator" title="XP calculator" intro="How much XP between two levels? Each skill page has its own calculator that also counts cycles for every action.">
        <x-manual.xp-calculator />
    </x-manual.section>

    <x-manual.section id="tiers" title="Skill tiers" intro="Levels fall into four tiers. The tier names are a rough guide to what opens up.">
        <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tiers as $tier)
                <li class="ez-card p-4">
                    <p class="ez-label !text-ez-signal">{{ $tier->level_min }}{{ $tier->level_max ? '–'.$tier->level_max : '+' }}</p>
                    <p class="ez-display mt-2 text-3xl text-ez-ink">{{ Str::title($tier->tier) }}</p>
                    <p class="mt-1 text-sm text-ez-muted">{{ ucfirst($tier->unlock_examples) }}</p>
                </li>
            @endforeach
        </ol>
    </x-manual.section>

    <x-manual.section id="speed" title="What changes a timer">
        <div class="ez-panel overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-700 text-left">
                        <th scope="col" class="ez-label px-4 py-3 font-normal">Factor</th>
                        <th scope="col" class="ez-label px-4 py-3 font-normal">Effect</th>
                    </tr>
                </thead>
                <tbody class="text-ez-muted">
                    <tr class="border-b border-zinc-800"><td class="px-4 py-3 text-ez-ink">Skill level</td><td class="px-4 py-3">−1% per level above 1. No action drops below {{ BuildManualCatalogue::MINIMUM_ACTION_SECONDS }} seconds.</td></tr>
                    @foreach (Weather::cases() as $weather)
                        @continue(! $weather->isBad())
                        <tr class="border-b border-zinc-800"><td class="px-4 py-3 text-ez-ink">{{ $weather->label() }}</td><td class="px-4 py-3">{{ $weather->effectsSummary() }}</td></tr>
                    @endforeach
                    <tr class="border-b border-zinc-800"><td class="px-4 py-3 text-ez-ink">Hunger &amp; fatigue</td><td class="px-4 py-3">{{ collect(config('survival.thresholds.hunger'))->map(fn (array $threshold): string => '+'.$threshold['timer_increase_percent'].'% at '.$threshold['at'])->implode(', ') }}. Hunger and fatigue add together.</td></tr>
                    <tr><td class="px-4 py-3 text-ez-ink">Wounds</td><td class="px-4 py-3">{{ collect(config('survival.thresholds.health'))->reverse()->map(fn (array $threshold): string => '+'.$threshold['timer_increase_percent'].'% at '.$threshold['at'].' health or less')->implode(', ') }}.</td></tr>
                </tbody>
            </table>
        </div>
        <x-manual.callout type="lore" class="mt-4">Bad weather is a trade-off: slower work and a chance to lose the loot, but more XP per cycle. Storm-chasers level faster.</x-manual.callout>
    </x-manual.section>

    <x-manual.pager :previous="$previous" :next="$next" />
</x-manual.layout>
