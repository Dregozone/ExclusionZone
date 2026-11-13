@php
    use App\Actions\Game\BuildManualCatalogue;
    use App\ItemRarity;

    $returnPercent = (int) config('trouble.return_visit_chance_percent');
    $fight = config('trouble.fight');
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 05 · Moving around"
    lede="Cities are linked by routes. Each leg of a journey takes time and wears you down, and some cities have trouble waiting at the edge."
    :image="$guide['image']"
    :breadcrumbs="['Guides' => route('manual.guide', 'getting-started'), $guide['title'] => null]"
    :toc="['routes' => 'Setting off', 'trouble' => 'Trouble on arrival', 'choices' => 'Fight, pay or run', 'types' => 'Who causes trouble', 'cities' => 'Where it is worst']"
>
    <x-manual.section id="routes" title="Setting off">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="ez-card p-5">
                <flux:icon.map class="text-ez-signal" />
                <p class="mt-3 font-semibold text-ez-ink">Pick a route</p>
                <p class="mt-1 text-sm leading-6 text-ez-muted">Open <strong class="text-ez-ink">Travel routes</strong> or the <strong class="text-ez-ink">Map</strong> in your PDA. You can only travel along a route from your current city, but you can plan several stops in one go.</p>
            </div>
            <div class="ez-card p-5">
                <flux:icon.clock class="text-ez-signal" />
                <p class="mt-3 font-semibold text-ez-ink">Each leg takes time</p>
                <p class="mt-1 text-sm leading-6 text-ez-muted">Every location page lists the time to each neighbour. Weather and your condition stretch it, just like work.</p>
            </div>
            <div class="ez-card p-5">
                <flux:icon.bolt class="text-amber-400" />
                <p class="mt-3 font-semibold text-ez-ink">Each leg costs you</p>
                <p class="mt-1 text-sm leading-6 text-ez-muted">+{{ (float) config('survival.travel.hunger_per_leg') }} hunger and +{{ (float) config('survival.travel.fatigue_per_leg') }} fatigue per leg. Cancelling keeps the legs you finished.</p>
            </div>
        </div>
        <x-manual.callout class="mt-4">Not sure which way to go? The <a href="{{ route('world-map') }}" class="underline" wire:navigate>world map</a> shows every route, and the <a href="{{ route('manual.locations') }}" class="underline" wire:navigate>locations index</a> shows what each city offers.</x-manual.callout>
    </x-manual.section>

    <x-manual.section id="trouble" title="Trouble on arrival" intro="When you arrive in a city, its trouble chance is rolled.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="ez-card ez-heat p-5">
                <p class="ez-label !text-orange-300">First visit</p>
                <p class="ez-numeral mt-2 text-6xl text-ez-ink">100%</p>
                <p class="mt-1 text-sm text-ez-muted">of the city’s trouble chance applies.</p>
            </div>
            <div class="ez-card p-5">
                <p class="ez-label">Every visit after</p>
                <p class="ez-numeral mt-2 text-6xl text-ez-ink">{{ $returnPercent }}%</p>
                <p class="mt-1 text-sm text-ez-muted">of it applies. A 72% city becomes {{ (int) round(72 * $returnPercent / 100) }}%. Cities get friendlier once they know you.</p>
            </div>
        </div>
        <div class="mt-4 h-2 ez-hazard rounded-sm" aria-hidden="true"></div>
        <p class="mt-3 text-sm text-ez-muted">Trouble holds you at the city’s edge. You can still eat and use medical items, but you cannot work, travel on or talk to quest contacts until it is dealt with.</p>
    </x-manual.section>

    <x-manual.section id="choices" title="Fight, pay or run">
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="ez-card p-5">
                <p class="flex items-center gap-2 text-lg font-semibold text-ez-ink"><flux:icon.fire variant="mini" class="text-orange-400" /> Attack</p>
                <p class="mt-2 text-sm leading-6 text-ez-muted">One roll decides it. Your win chance starts at <strong class="text-ez-ink">{{ $fight['base_win_chance_percent'] }}%</strong>, then:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ez-muted">
                    <li>±{{ $fight['win_chance_per_level_percent'] }}% per combat level above or below theirs</li>
                    <li>±1% per {{ $fight['health_points_per_win_chance_percent'] }} health above or below half</li>
                    <li>Always between {{ $fight['minimum_win_chance_percent'] }}% and {{ $fight['maximum_win_chance_percent'] }}%</li>
                </ul>
                <p class="mt-3 text-sm text-ez-muted">Win: you take {{ $fight['damage_taken_on_win_percent'] }}% of their damage roll, earn XP and loot, and walk in. Lose: you take all of it and they stay put.</p>
            </div>
            <div class="ez-card p-5">
                <p class="flex items-center gap-2 text-lg font-semibold text-ez-ink"><flux:icon.gift variant="mini" class="text-ez-signal" /> Buy your way in</p>
                <p class="mt-2 text-sm leading-6 text-ez-muted">Hand over items worth at least what they demand. Each item is worth its rarity:</p>
                <ul class="mt-3 flex flex-wrap gap-1.5">
                    @foreach (ItemRarity::cases() as $rarity)
                        <li class="rounded-sm border border-zinc-700 px-2 py-0.5 text-xs text-ez-ink">{{ $rarity->label() }} <span class="font-mono text-ez-signal">{{ $rarity->rank() }}</span></li>
                    @endforeach
                </ul>
                <p class="mt-3 text-sm text-ez-muted">So 3 Common items pay a demand of 3. Quest items are never handed over.</p>
            </div>
            <div class="ez-card p-5">
                <p class="flex items-center gap-2 text-lg font-semibold text-ez-ink"><flux:icon.arrow-uturn-left variant="mini" class="text-amber-400" /> Run</p>
                <p class="mt-2 text-sm leading-6 text-ez-muted">Turn back to the city you came from. It takes the normal travel time and <strong class="text-ez-ink">cannot be cancelled</strong>, but you never meet trouble on the way back.</p>
            </div>
        </div>
    </x-manual.section>

    <x-manual.section id="types" title="Who causes trouble" intro="Their fighter level is matched against your skill in the combat type listed.">
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($troubleTypes as $type)
                <article class="ez-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-lg font-semibold text-ez-ink">{{ $type->name }}</p>
                        <div class="text-right">
                            <p class="ez-label !text-[9px]">Fighter level</p>
                            <p class="ez-numeral text-4xl text-orange-300">{{ $type->fighter_level }}</p>
                        </div>
                    </div>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $type->description }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-zinc-800 pt-3 text-xs sm:grid-cols-4">
                        <div><dt class="ez-label !text-[9px]">Tests</dt><dd class="mt-1"><a href="{{ route('manual.skill', $type->skill_key) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $type->skill?->display_name }}</a></dd></div>
                        <div><dt class="ez-label !text-[9px]">Hits for</dt><dd class="mt-1 font-mono text-red-300">{{ $type->damage_min }}–{{ $type->damage_max }}</dd></div>
                        <div><dt class="ez-label !text-[9px]">Demands</dt><dd class="mt-1 font-mono text-ez-ink">{{ $type->demand_value }}</dd></div>
                        <div><dt class="ez-label !text-[9px]">Win pays</dt><dd class="mt-1 font-mono text-ez-ink">{{ $type->xp_reward }} XP</dd></div>
                    </dl>
                    @if ($type->lootItem)
                        <p class="mt-3 text-xs text-ez-muted">Loot: <x-manual.item :item="$type->lootItem" :quantity="$type->loot_quantity" /></p>
                    @endif
                </article>
            @endforeach
        </div>
    </x-manual.section>

    <x-manual.section id="cities" title="Where it is worst">
        <div class="grid gap-4 md:grid-cols-2">
            @foreach (['Most trouble' => [$riskiestCities, 'text-red-300'], 'Least trouble' => [$safestCities, 'text-ez-safe']] as $heading => [$cities, $tone])
                <div class="ez-panel p-5">
                    <p class="ez-label">{{ $heading }}</p>
                    <ol class="mt-3 space-y-2">
                        @foreach ($cities as $city)
                            <li class="flex items-center justify-between gap-3">
                                <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($city)) }}" class="text-ez-ink hover:text-ez-signal" wire:navigate>{{ $city->city }} <span class="text-xs text-zinc-500">{{ $city->country?->country }}</span></a>
                                <span class="font-mono {{ $tone }}">{{ $city->trouble_chance_pct }}%</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </x-manual.section>

    <x-manual.pager :previous="$previous" :next="$next" />
</x-manual.layout>
