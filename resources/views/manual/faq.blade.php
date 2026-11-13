@php
    $groups = [
        'Getting started' => [
            ['Do I need an account to read the manual?', 'No. The whole manual is public. You only need an account to play.'],
            ['Where do I start?', 'Every survivor starts in <a href="'.route('manual.location', 'kyiv').'">Kyiv</a>, Ukraine, with every skill at level 1. Follow <a href="'.route('manual.guide', 'getting-started').'">Just arrived</a> for your first hour.'],
            ['What should I do first?', 'Start a level 1 job in Kyiv such as District patrols or Shelter repair, eat when hunger passes 50, and start the first story chapter, Static, from Contracts in your PDA.'],
            ['Is it free?', 'Yes. Premium only adds cosmetics: outfits, themes and flair. It never adds strength, skills or items.'],
        ],
        'Skills & work' => [
            ['How do I level up a skill?', 'Do actions that train it. Going from level L to L+1 costs L × 100 XP. Each <a href="'.route('manual.skills').'">skill page</a> lists every action with its level requirement and XP.'],
            ['Why is an action locked?', 'Your level in its skill is lower than the action needs. The level is shown on the card and in every table here.'],
            ['Why does it say “Missing items”?', 'The action uses up items each cycle and you are short. Open the item in the manual to see where to get it.'],
            ['Does work continue when I log out?', 'Yes. A work loop keeps paying out for up to 8 hours, until you stop it, or until you run out of the items it uses.'],
            ['Do I lose anything if I stop working?', 'Only the cycle in progress. Every finished cycle is paid out.'],
            ['Why are my timers slower than the table says?', 'Weather (rain +'.\App\Weather::Rain->timerIncreasePercent().'%, storm +'.\App\Weather::Storm->timerIncreasePercent().'%) and your condition (hunger, fatigue and wounds) stretch every timer. Each skill level makes that skill’s actions 1% faster.'],
        ],
        'Survival' => [
            ['How do I lower hunger and fatigue?', 'Eat food for hunger. Fatigue falls on its own while you are not working, and some food helps too. See <a href="'.route('manual.guide', 'survival').'#food">the food table</a>.'],
            ['How do I heal?', 'Health comes back slowly by itself. Food with a health value, medical items such as Bandages and Antiseptic, and clinic actions heal faster.'],
            ['What happens when I die?', 'You wake at the nearest trade post on your continent with '.config('survival.health.revive_health').' health, lose about '.config('survival.death.pack_lost_percent').'% of each stack in your pack (quest items are safe), and must rest '.config('survival.death.recovery_minutes').' minutes before working or travelling.'],
            ['Is bad weather always bad?', 'It slows work and may cost you the loot, but gives bonus XP: +'.\App\Weather::Rain->experienceBonusPercent().'% in rain, +'.\App\Weather::Storm->experienceBonusPercent().'% in a storm.'],
        ],
        'Travel & combat' => [
            ['How do I travel?', 'Open Travel routes or the Map in your PDA and choose a connected city. You can plan several stops at once.'],
            ['What is trouble?', 'A group blocking the way into a city when you arrive. Fight them, pay them in items, or run back. Return visits are much less likely to meet trouble. <a href="'.route('manual.guide', 'travel').'#trouble">More</a>'],
            ['How do I equip a weapon?', 'You don’t. The best weapon in your pack is used automatically in every fight.'],
            ['Can I run from a fight?', 'You break off automatically if a round leaves you at '.config('combat.flee_at_health').' health or less. You can also press Stop working between rounds.'],
        ],
        'Trading & social' => [
            ['Where can I trade?', 'Only at trade posts, and only with someone standing in the same city: '.$tradePosts->pluck('city')->implode(', ').'.'],
            ['Can I trade quest items?', 'No. Quest items stay with you.'],
            ['How do I talk to other players?', 'Open Radio in your PDA. It is one channel shared by everyone in the Zone.'],
        ],
    ];
@endphp

<x-manual.layout
    title="FAQ"
    lede="Short answers to the questions new survivors ask most. Can’t find yours? Search the manual or ask on the radio."
    :breadcrumbs="['FAQ' => null]"
    :toc="collect(array_keys($groups))->mapWithKeys(fn ($group) => [Str::slug($group) => $group])->all()"
>
    @foreach ($groups as $group => $questions)
        <x-manual.section :id="Str::slug($group)" :title="$group">
            <div class="divide-y divide-zinc-800 overflow-hidden rounded-sm border border-zinc-700">
                @foreach ($questions as [$question, $answer])
                    <details class="group bg-ez-ground/70 open:bg-ez-surface">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3.5 text-ez-ink transition hover:bg-white/5 [&::-webkit-details-marker]:hidden">
                            <span class="font-medium">{{ $question }}</span>
                            <flux:icon.plus variant="mini" class="shrink-0 text-ez-signal transition group-open:rotate-45" />
                        </summary>
                        <div class="px-4 pb-4 text-sm leading-7 text-ez-muted [&_a]:text-ez-signal [&_a]:underline">{!! $answer !!}</div>
                    </details>
                @endforeach
            </div>
        </x-manual.section>
    @endforeach
</x-manual.layout>
