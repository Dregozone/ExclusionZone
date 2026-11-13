@php
    use App\Actions\Game\BuildManualCatalogue;

    $cityName = $startingCity?->city ?? 'Kyiv';
    $firstJobs = $starterRows->filter(fn (array $row): bool => $row['action']->min_level <= 1 && $row['inputs'] === []);
    $steps = [
        'arrive' => 'You arrive',
        'look' => 'Look around',
        'work' => 'Your first job',
        'eat' => 'Eat and rest',
        'level' => 'Level up',
        'pda' => 'Open your PDA',
        'travel' => 'Your first trip',
        'quest' => 'Your first quest',
    ];
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 01 · Start here"
    lede="Your first hour in the Zone, in eight short steps. Tick them off as you go; this page remembers your progress on this device."
    :image="$guide['image']"
    :breadcrumbs="['Guides' => null, $guide['title'] => null]"
    :toc="$steps"
>
    <div
        x-data="{
            done: {},
            init() {
                try { this.done = JSON.parse(localStorage.getItem('ez.manual.first-steps') || '{}') } catch (error) { this.done = {} }
            },
            toggle(step) {
                this.done[step] = ! this.done[step];
                try { localStorage.setItem('ez.manual.first-steps', JSON.stringify(this.done)) } catch (error) {}
            },
            get count() { return Object.values(this.done).filter(Boolean).length },
        }"
        class="space-y-14"
    >
        {{-- Progress --}}
        <div class="ez-panel flex flex-wrap items-center gap-4 p-4" aria-live="polite">
            <p class="ez-label">First-day checklist</p>
            <div class="ez-meter min-w-40 flex-1 text-ez-signal"><div class="ez-meter-fill" :style="`width: ${count / {{ count($steps) }} * 100}%`"></div></div>
            <p class="font-mono text-sm text-ez-ink"><span x-text="count">0</span> / {{ count($steps) }}</p>
        </div>

        <ol class="relative space-y-12 border-l border-zinc-700 pl-8 sm:pl-10">
            @foreach ($steps as $anchor => $label)
                <li id="{{ $anchor }}" class="relative scroll-mt-24">
                    <button
                        type="button"
                        @click="toggle('{{ $anchor }}')"
                        :aria-pressed="done['{{ $anchor }}'] ? 'true' : 'false'"
                        class="absolute top-0 -left-[3.05rem] flex size-9 items-center justify-center rounded-full border bg-ez-ground font-mono text-sm transition sm:-left-[3.55rem]"
                        :class="done['{{ $anchor }}'] ? 'border-ez-signal bg-ez-signal text-ez-signal-ink' : 'border-zinc-600 text-ez-muted hover:border-ez-signal'"
                        aria-label="Mark “{{ $label }}” as done"
                    >
                        <span x-show="! done['{{ $anchor }}']">{{ $loop->iteration }}</span>
                        <flux:icon.check variant="mini" x-show="done['{{ $anchor }}']" x-cloak />
                    </button>
                    <h2 class="ez-display text-4xl text-ez-ink">{{ $label }}</h2>

                    <div class="mt-4 max-w-3xl space-y-4 leading-7 text-ez-muted">
                        @switch($anchor)
                            @case('arrive')
                                <p>
                                    Every new survivor wakes up in <a href="{{ route('manual.location', 'kyiv') }}" class="text-ez-signal hover:underline" wire:navigate>{{ $cityName }}</a>,
                                    {{ $startingCity?->country?->country ?? 'Ukraine' }}. You carry nothing, and every one of the game's skills starts at level 1.
                                </p>
                                <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-sm border border-zinc-700 bg-zinc-700 sm:grid-cols-4">
                                    @foreach ([['Health', '100', 'Full'], ['Hunger', '0', 'Fed'], ['Fatigue', '0', 'Rested'], ['Skills', 'Lv 1', 'All of them']] as [$stat, $value, $note])
                                        <div class="bg-ez-ground/90 p-4">
                                            <dt class="ez-label">{{ $stat }}</dt>
                                            <dd class="ez-numeral mt-2 text-4xl text-ez-ink">{{ $value }}</dd>
                                            <dd class="mt-1 text-xs text-ez-muted">{{ $note }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                                @guest
                                    <p><a href="{{ route('register') }}" class="ez-button !min-h-10 !py-2 text-sm">Create your survivor</a></p>
                                @endguest
                                @break

                            @case('look')
                                <p>
                                    The <strong class="text-ez-ink">city menu</strong> is your home screen. At the top you'll see your health, hunger and fatigue meters,
                                    then the city's weather and trouble chance, and below that a card for every action you can take here.
                                </p>
                                <p><a href="{{ route('manual.guide', 'city-menu') }}" class="text-ez-signal hover:underline" wire:navigate>See the labelled city menu →</a></p>
                                @break

                            @case('work')
                                <p>
                                    Pick an action card and press <strong class="text-ez-ink">Begin work</strong>. A timer starts. When it finishes you earn XP and loot,
                                    and the next cycle starts on its own. It keeps going until you press <strong class="text-ez-ink">Stop working</strong>,
                                    run out of the items it uses, or 8 hours pass, even while you are logged out.
                                </p>
                                <p>These jobs in {{ $cityName }} need nothing but level 1, so any of them is a good first pick:</p>
                                <x-manual.action-table :rows="$firstJobs" :show-location="false" :show-skill="true" />
                                <x-manual.callout>Stopping is safe: every cycle that already finished is paid out. Only the cycle in progress is lost.</x-manual.callout>
                                @break

                            @case('eat')
                                <p>
                                    Every cycle of work adds a little <strong class="text-ez-ink">hunger</strong> and <strong class="text-ez-ink">fatigue</strong>.
                                    Past 50, your timers start to slow down. Neither stops you working, but at 100 they start costing health.
                                </p>
                                <ul class="list-disc space-y-1 pl-5">
                                    <li><strong class="text-ez-ink">Eat</strong> food from the Rations panel to bring hunger down. You can eat while working.</li>
                                    <li><strong class="text-ez-ink">Rest</strong> by not working: fatigue falls by about 1 point a minute while you are idle.</li>
                                </ul>
                                <p>A good early food source: cook <a href="{{ route('manual.item', 'borscht') }}" class="text-ez-signal hover:underline" wire:navigate>Borscht</a> in {{ $cityName }}, or fish for <a href="{{ route('manual.item', 'salt_fish') }}" class="text-ez-signal hover:underline" wire:navigate>Salt Fish</a> in Gdansk.</p>
                                <p><a href="{{ route('manual.guide', 'survival') }}" class="text-ez-signal hover:underline" wire:navigate>Read Staying alive →</a></p>
                                @break

                            @case('level')
                                <p>
                                    Getting from level <em>L</em> to <em>L</em>+1 takes <strong class="text-ez-ink">L × 100 XP</strong>, so level 2 costs 100 XP, level 3 another 200, and so on.
                                    Each level also makes that skill's actions <strong class="text-ez-ink">1% faster</strong>, and unlocks actions with a higher level requirement.
                                </p>
                                <p><a href="{{ route('manual.skills') }}" class="text-ez-signal hover:underline" wire:navigate>Browse every skill and what it unlocks →</a></p>
                                @break

                            @case('pda')
                                <p>Your <strong class="text-ez-ink">PDA</strong> (the <span class="font-mono text-ez-signal">[ OPEN PDA ]</span> button) holds everything that isn't about the city you are standing in:</p>
                                <ul class="grid gap-2 sm:grid-cols-2">
                                    @foreach ([
                                        ['map', 'Map', 'Where you are and where routes go.'],
                                        ['arrow-trending-up', 'Travel routes', 'Set off for a neighbouring city.'],
                                        ['academic-cap', 'Skill tracker', 'Every skill level and XP bar.'],
                                        ['archive-box', 'Inventory', 'Your pack: eat, use and inspect items.'],
                                        ['signal', 'Radio', 'Talk to every survivor in the Zone.'],
                                        ['arrows-right-left', 'Trade board', 'Swap goods at a trade post.'],
                                        ['clipboard-document-list', 'Contracts', 'Start story quests and jobs.'],
                                        ['swatch', 'Cosmetics', 'Looks only. Nobody buys strength.'],
                                    ] as [$icon, $name, $copy])
                                        <li class="ez-card flex items-start gap-3 p-3">
                                            <flux:icon :name="$icon" variant="mini" class="mt-0.5 shrink-0 text-ez-signal" />
                                            <span><span class="block text-sm font-medium text-ez-ink">{{ $name }}</span><span class="block text-xs text-ez-muted">{{ $copy }}</span></span>
                                        </li>
                                    @endforeach
                                </ul>
                                @break

                            @case('travel')
                                <p>Cities are linked by routes. From {{ $cityName }} you can reach:</p>
                                <ul class="flex flex-wrap gap-2">
                                    @foreach ($startingCity?->neighbors ?? [] as $neighbor)
                                        <li>
                                            <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($neighbor)) }}" class="ez-card inline-flex items-center gap-2 px-3 py-2 text-sm text-ez-ink hover:border-ez-signal/60" wire:navigate>
                                                <flux:icon.map-pin variant="micro" class="text-ez-signal" /> {{ $neighbor->city }}
                                                <span class="font-mono text-xs text-ez-muted"><x-manual.duration :seconds="$neighbor->pivot->duration_seconds ?? 10" /></span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <x-manual.callout type="warn">
                                    Some cities have <strong>trouble</strong> waiting at the edge: dogs, looters or a militia checkpoint. You can fight, pay them off with items, or run back.
                                    Pripyat is irradiated and its work starts at higher levels, so Warsaw is the friendlier first trip.
                                </x-manual.callout>
                                <p><a href="{{ route('manual.guide', 'travel') }}" class="text-ez-signal hover:underline" wire:navigate>Read Travel & trouble →</a></p>
                                @break

                            @case('quest')
                                <p>
                                    Open <strong class="text-ez-ink">Contracts</strong> in your PDA and start the first story chapter, <strong class="text-ez-ink">Static</strong>.
                                    Its contact, Viktor the Operator, is right here in {{ $cityName }}. When a quest step is available in your city, it appears on your city menu with a button to speak to the contact or hand over items.
                                </p>
                                <p><a href="{{ route('manual.quests') }}" class="text-ez-signal hover:underline" wire:navigate>See the story chain and jobs →</a></p>
                                @break
                        @endswitch
                    </div>
                </li>
            @endforeach
        </ol>

        <x-manual.pager :previous="$previous" :next="$next" />
    </div>
</x-manual.layout>
