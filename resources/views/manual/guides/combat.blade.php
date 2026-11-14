@php
    use App\Actions\Game\BuildManualCatalogue;

    $survivor = config('combat.survivor');
    $hitChance = config('combat.hit_chance');
    $fleeAt = (int) config('combat.flee_at_health');
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 06 · Combat"
    lede="Fights are city actions with an enemy attached. Each cycle of the timer is one round: you strike, then they strike back."
    :image="$guide['image']"
    :breadcrumbs="['Guides' => route('manual.guide', 'getting-started'), $guide['title'] => null]"
    :toc="['how' => 'How a fight works', 'odds' => 'Reading the odds', 'hitting' => 'Landing a blow', 'damage' => 'Your damage', 'weapons' => 'Weapons and armour', 'enemies' => 'Enemies', 'trouble' => 'Trouble fights']"
>
    <x-manual.section id="how" title="How a fight works">
        <ol class="grid gap-3 md:grid-cols-3">
            @foreach ([
                ['Find a fight', 'Action cards with a red border and a “Fight:” line are fights. They train Melee or Ranged Combat.'],
                ['Trade blows', 'Every cycle is a round. You swing first; if the enemy is still standing, it swings back. Either blow can miss. Beat it to earn its XP and roll its loot; that is the whole reward for the fight.'],
                ['Know when to stop', 'If a round leaves you at '.$fleeAt.' health or less, you break off and run. You cannot start a fight that hurt either.'],
            ] as [$name, $copy])
                <li class="ez-card p-5">
                    <span class="ez-numeral text-5xl text-orange-300">{{ $loop->iteration }}</span>
                    <p class="mt-2 font-semibold text-ez-ink">{{ $name }}</p>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $copy }}</p>
                </li>
            @endforeach
        </ol>
        <x-manual.callout type="warn" class="mt-4">Bring food and medical items. You can eat and patch up between rounds without stopping the fight.</x-manual.callout>
    </x-manual.section>

    <x-manual.section id="odds" title="Reading the odds" intro="Every fight card on the dashboard sizes up each enemy against you as you are right now: your combat level, health, and the weapon and armour you wear.">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (App\FightOdds::cases() as $odds)
                <li class="ez-card p-4">
                    <p class="font-mono text-[11px] uppercase tracking-wider text-ez-ink">{{ $odds->label() }}</p>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $odds->advice() }}</p>
                </li>
            @endforeach
        </ul>
        <p class="mt-4 max-w-3xl text-sm leading-6 text-ez-muted">The odds compare the health an average fight should cost you with the health you can lose before you flee at {{ $fleeAt }}. Level up, wear a better weapon or armour, or heal before you start, and the same enemy gets easier.</p>
    </x-manual.section>

    <x-manual.section id="hitting" title="Landing a blow">
        <div class="ez-panel p-5">
            <p class="font-mono text-sm leading-7 text-ez-ink sm:text-base">
                your chance = <span class="text-ez-signal">{{ $survivor['base_hit_chance_percent'] }}%</span>
                + <span class="text-amber-300">{{ $survivor['hit_chance_percent_per_skill_level'] }}% × combat level</span>
                − <span class="text-red-300">enemy defence</span>
            </p>
            <p class="mt-2 font-mono text-sm leading-7 text-ez-ink sm:text-base">
                their chance = <span class="text-red-300">enemy accuracy</span>
                − <span class="text-amber-300">{{ $survivor['evasion_percent_per_skill_level'] }}% × combat level</span>
                − <span class="text-sky-300">best armour bonus</span>
            </p>
            <p class="mt-4 text-sm text-ez-muted">Neither side ever lands less than {{ $hitChance['minimum_percent'] }}% or more than {{ $hitChance['maximum_percent'] }}% of the time.</p>
        </div>
    </x-manual.section>

    <x-manual.section id="damage" title="Your damage">
        <div class="ez-panel p-5">
            <p class="font-mono text-sm leading-7 text-ez-ink sm:text-base">
                hit = <span class="text-ez-signal">roll {{ $survivor['base_damage_min'] }}–{{ $survivor['base_damage_max'] }}</span>
                + <span class="text-amber-300">{{ $survivor['damage_per_skill_level'] }} × combat level</span>
                + <span class="text-fuchsia-300">held weapon bonus</span>
            </p>
            <ul class="mt-4 grid gap-3 text-sm text-ez-muted sm:grid-cols-2">
                <li class="flex gap-2"><flux:icon.heart variant="mini" class="shrink-0 text-red-400" /> Hurt survivors hit softer: at 0 health a blow lands at half strength, rising to full strength at full health.</li>
                <li class="flex gap-2"><flux:icon.shield-check variant="mini" class="shrink-0 text-ez-signal" /> Every combat level turns aside {{ $survivor['blocked_percent_per_skill_level'] }}% of each enemy blow that lands, up to {{ $survivor['maximum_blocked_percent'] }}% (level {{ (int) ceil($survivor['maximum_blocked_percent'] / max(1, $survivor['blocked_percent_per_skill_level'])) }}).</li>
            </ul>
        </div>
    </x-manual.section>

    <x-manual.section id="weapons" title="Weapons and armour" intro="Wear them from the Gear tab of your PDA: hold a weapon in an arm and put armour on. The best weapon you hold and the best armour you wear are used for both melee and ranged fights; anything left in your pack doesn’t count.">
        <ul class="grid gap-3 sm:grid-cols-3">
            @foreach ($weapons as $weapon)
                <li class="ez-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <x-manual.item :item="$weapon" size="md" />
                        <span class="ez-numeral text-4xl text-fuchsia-300">+{{ $weapon->attack_bonus }}</span>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-ez-muted">{{ $weapon->description }}</p>
                </li>
            @endforeach
            @foreach ($armour as $piece)
                <li class="ez-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <x-manual.item :item="$piece" size="md" />
                        <span class="ez-numeral text-4xl text-sky-300">−{{ $piece->defence_bonus }}%</span>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-ez-muted">{{ $piece->description }}</p>
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-xs text-ez-muted">Weapons add their bonus to every blow you land. Armour takes its bonus off every enemy’s chance to land a blow.</p>
    </x-manual.section>

    <x-manual.section id="enemies" title="Enemies" intro="Sorted from weakest to toughest. Each one waits in a single city.">
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($enemies as $enemy)
                <article class="ez-card ez-pin ez-pin-heat p-5 pt-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-ez-ink">{{ $enemy->name }}</p>
                            <p class="text-xs text-ez-muted">
                                <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($enemy->city)) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $enemy->city->city }}</a>
                                · <a href="{{ route('manual.skill', $enemy->skill_key) }}" class="hover:underline" wire:navigate>{{ $enemy->skill?->display_name }}</a>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="ez-label !text-[9px]">Health</p>
                            <p class="ez-numeral text-4xl text-ez-ink">{{ $enemy->health }}</p>
                        </div>
                    </div>
                    @if ($enemy->description)
                        <p class="mt-2 text-sm leading-6 text-ez-muted">{{ $enemy->description }}</p>
                    @endif
                    <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-zinc-800 pt-3 text-xs sm:grid-cols-4">
                        <div><dt class="ez-label !text-[9px]">Hits for</dt><dd class="mt-1 font-mono text-red-300">{{ $enemy->min_damage }}–{{ $enemy->max_damage }}</dd></div>
                        <div><dt class="ez-label !text-[9px]">Accuracy</dt><dd class="mt-1 font-mono text-red-300">{{ $enemy->accuracy }}%</dd></div>
                        <div><dt class="ez-label !text-[9px]">Defence</dt><dd class="mt-1 font-mono text-ez-ink">{{ $enemy->defence }}%</dd></div>
                        <div><dt class="ez-label !text-[9px]">Defeat pays</dt><dd class="mt-1 font-mono text-ez-ink">{{ $enemy->xp_reward }} XP</dd></div>
                    </dl>
                    @if ($enemy->lootTable() !== [])
                        <ul class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($enemy->lootTable() as $loot)
                                <li class="flex items-center gap-1">
                                    <x-manual.item :item="$catalogueItems[$loot['item_key']] ?? null" :key="$loot['item_key']" :quantity="$loot['quantity']" />
                                    <span class="font-mono text-[11px] text-zinc-500">{{ $loot['chance_percent'] }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        </div>
    </x-manual.section>

    <x-manual.section id="trouble" title="Trouble fights">
        <p class="max-w-3xl leading-7 text-ez-muted">
            Trouble waiting at a city’s edge is settled in a single roll rather than rounds, and uses your combat level against theirs.
            <a href="{{ route('manual.guide', 'travel') }}#choices" class="text-ez-signal hover:underline" wire:navigate>See how the win chance works →</a>
        </p>
    </x-manual.section>

    <x-manual.pager :previous="$previous" :next="$next" />
</x-manual.layout>
