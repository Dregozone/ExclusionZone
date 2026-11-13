@php
    use App\Actions\Game\BuildManualCatalogue;
@endphp

<x-manual.layout
    title="Quests & jobs"
    lede="Story chapters lead you across the Zone one contact at a time. Jobs are side work you can pick up again and again. Start either from Contracts in your PDA."
    :breadcrumbs="['Quests & jobs' => null]"
    :toc="['how' => 'How quests work', 'story' => 'The story', 'jobs' => 'Jobs & contracts']"
>
    <x-manual.section id="how" title="How quests work">
        <ol class="grid gap-3 md:grid-cols-4">
            @foreach ([
                ['Start it', 'Open Contracts in your PDA and start a story chapter or accept a job.'],
                ['Go to the contact', 'Each step names a person and a city. Travel there.'],
                ['Do the step', 'The step appears on that city menu. Speak, hand over items, or finish an action a number of times first.'],
                ['Collect', 'Finishing the last step pays the reward: XP for a skill, items, or both.'],
            ] as [$name, $copy])
                <li class="ez-card p-4">
                    <span class="ez-numeral text-5xl text-ez-signal">{{ $loop->iteration }}</span>
                    <p class="mt-2 font-semibold text-ez-ink">{{ $name }}</p>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $copy }}</p>
                </li>
            @endforeach
        </ol>
        <x-manual.callout type="lore" class="mt-4">Some steps ask for different items each time you start them, so two survivors on the same quest may need different things.</x-manual.callout>
    </x-manual.section>

    <x-manual.section id="story" title="The story" :intro="$story->count().' chapters, played in order. Each one unlocks the next. No spoilers beyond who to see and where.'">
        <ol class="relative space-y-4 border-l border-zinc-700 pl-6">
            @foreach ($story as $quest)
                @php($cities = $quest->steps->pluck('city')->filter()->unique('id'))
                <li id="quest-{{ $quest->id }}" class="relative scroll-mt-24">
                    <span class="absolute top-4 -left-[1.95rem] size-3 rounded-full border-2 border-ez-ground bg-ez-signal shadow-[0_0_10px_var(--color-ez-signal)]" aria-hidden="true"></span>
                    <article class="ez-paper p-5 pt-6">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-xl font-semibold"><span class="font-mono text-sm text-ez-paper-muted">Ch. {{ $quest->sequence_order }}</span> {{ $quest->name }}</p>
                            <p class="font-mono text-xs text-ez-paper-muted">{{ $quest->steps->count() }} {{ Str::plural('step', $quest->steps->count()) }}</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-ez-paper-muted">
                            Meet
                            {{ $quest->steps->pluck('person_of_interest')->filter()->unique()->implode(', ') }}
                            in
                            @foreach ($cities as $city)
                                <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($city)) }}" class="font-medium text-ez-stamp underline decoration-dotted" wire:navigate>{{ $city->city }}</a>@if (! $loop->last), @endif
                            @endforeach
                        </p>
                        <p class="mt-3 flex flex-wrap gap-2 text-xs">
                            @if ($quest->reward_xp_amount)
                                <span class="rounded-sm bg-ez-paper-ink/10 px-2 py-0.5">+{{ number_format($quest->reward_xp_amount) }} {{ $quest->rewardSkill?->display_name ?? '' }} XP</span>
                            @endif
                            @if ($quest->rewardItem)
                                <a href="{{ route('manual.item', $quest->rewardItem->key) }}" class="rounded-sm bg-ez-paper-ink/10 px-2 py-0.5 hover:bg-ez-paper-ink/20" wire:navigate>+{{ $quest->reward_item_quantity }}× {{ $quest->rewardItem->name }}</a>
                            @endif
                        </p>
                    </article>
                </li>
            @endforeach
        </ol>
    </x-manual.section>

    <x-manual.section id="jobs" title="Jobs & contracts" intro="Pick these up whenever you like. Repeatable ones can be run again once finished.">
        <ul class="grid gap-3 md:grid-cols-2">
            @foreach ($jobs as $quest)
                @php($cities = $quest->steps->pluck('city')->filter()->unique('id'))
                <li id="quest-{{ $quest->id }}" class="ez-card ez-pin scroll-mt-24 p-5 pt-6">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-lg font-semibold text-ez-ink">{{ $quest->name }}</p>
                        <span class="shrink-0 rounded-sm border px-1.5 py-0.5 font-mono text-[10px] uppercase {{ $quest->is_repeatable ? 'border-ez-signal/50 text-ez-signal' : 'border-zinc-600 text-ez-muted' }}">{{ $quest->is_repeatable ? 'Repeatable' : 'One-off' }}</span>
                    </div>
                    @if ($quest->description)
                        <p class="mt-1 text-sm leading-6 text-ez-muted">{{ Str::limit($quest->description, 160) }}</p>
                    @endif
                    <p class="mt-3 text-xs text-ez-muted">
                        {{ $quest->steps->pluck('person_of_interest')->filter()->unique()->implode(', ') }} ·
                        @foreach ($cities as $city)
                            <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($city)) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $city->city }}</a>@if (! $loop->last), @endif
                        @endforeach
                    </p>
                    <p class="mt-3 flex flex-wrap gap-2 text-xs">
                        @if ($quest->reward_xp_amount)
                            <span class="rounded-sm border border-zinc-700 px-2 py-0.5 text-ez-ink">+{{ number_format($quest->reward_xp_amount) }} {{ $quest->rewardSkill?->display_name ?? '' }} XP</span>
                        @endif
                        @if ($quest->rewardItem)
                            <x-manual.item :item="$quest->rewardItem" :quantity="$quest->reward_item_quantity" />
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    </x-manual.section>
</x-manual.layout>
