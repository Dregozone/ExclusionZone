@php
    $parts = [
        1 => ['Status bar', 'Your health, hunger and fatigue at a glance. An amber “Timers +10%” tag means your condition is slowing your work.'],
        2 => ['Where you are', 'The city, its country and its terrain. Everything below belongs to this city.'],
        3 => ['Conditions', 'Today’s weather, average temperature, rain chance and trouble chance. A red trouble tile means arrivals here often meet trouble.'],
        4 => ['Intercept', 'Scout reports picked up on the radio: flavour, rumours and warnings about the area.'],
        5 => ['Field readout', 'Your condition in detail, with the food (Rations) and medical items in your pack. Press Eat or Use right here, even mid-job.'],
        6 => ['Quest objectives', 'Appears only when a quest you have started has a step in this city: speak to a contact or hand over items.'],
        7 => ['Action cards', 'One card per job in this city. Each shows its risk, the skill it trains, the level it needs, what it gives, what each cycle costs and what items it uses up.'],
        8 => ['Open PDA', 'Map, travel, skills, inventory, radio, trade board, contracts and cosmetics.'],
    ];
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 02 · The game screen"
    lede="The city menu is where you spend most of your time. Here is every part of it, numbered, with what it does."
    :breadcrumbs="['Guides' => route('manual.guide', 'getting-started'), $guide['title'] => null]"
    :toc="['diagram' => 'The screen, labelled', 'cards' => 'Reading an action card', 'buttons' => 'Button states', 'timer' => 'While you work']"
>
    <x-manual.section id="diagram" title="The screen, labelled" intro="Hover or tap a number to highlight that part of the screen.">
        <div x-data="{ focus: null }" class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            {{-- Schematic of the city menu --}}
            <div class="ez-panel space-y-3 p-3 text-[11px] sm:p-4" aria-hidden="true">
                @php
                    $zone = fn (int $number): string => "relative rounded-sm border p-3 transition";
                @endphp
                <div :class="focus === 1 ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700'" class="{{ $zone(1) }} flex flex-wrap items-center gap-3">
                    <span class="text-ez-ink">● Survivor in <em class="ez-display text-sm">Kyiv</em></span>
                    @foreach (['Health' => ['73', 'text-emerald-400', 73], 'Hunger' => ['46', 'text-emerald-400', 46], 'Fatigue' => ['60', 'text-amber-400', 60]] as $meter => [$value, $tone, $width])
                        <span class="flex min-w-24 flex-1 flex-col gap-1">
                            <span class="flex justify-between font-mono text-[9px] text-ez-muted uppercase"><span>{{ $meter }}</span><span>{{ $value }}</span></span>
                            <span class="ez-meter !h-1.5 {{ $tone }}"><span class="ez-meter-fill block" style="width: {{ $width }}%"></span></span>
                        </span>
                    @endforeach
                    <span class="rounded-sm border border-amber-400/60 px-1.5 py-0.5 font-mono text-[9px] text-amber-300">Timers +10%</span>
                    <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">1</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-[1.3fr_1fr]">
                    <div class="space-y-3">
                        <div :class="focus === 2 ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700'" class="{{ $zone(2) }}">
                            <p class="font-mono text-[9px] text-ez-signal uppercase">50.45° N · Current location</p>
                            <p class="ez-display text-5xl text-ez-ink">Kyiv</p>
                            <p class="text-ez-muted">Ukraine · metro ruins/fortified sectors</p>
                            <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">2</span>
                        </div>
                        <div :class="focus === 3 ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700'" class="{{ $zone(3) }} grid grid-cols-4 gap-2">
                            @foreach (['Weather' => 'Clear', 'Temp' => '8°C', 'Rain' => '34%', 'Trouble' => '72%'] as $label => $value)
                                <div @class(['rounded-sm p-1.5', 'bg-red-900/30' => $label === 'Trouble'])>
                                    <p class="font-mono text-[8px] text-ez-muted uppercase">{{ $label }}</p>
                                    <p @class(['ez-numeral text-xl', 'text-red-300' => $label === 'Trouble', 'text-ez-ink' => $label !== 'Trouble'])>{{ $value }}</p>
                                </div>
                            @endforeach
                            <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">3</span>
                        </div>
                        <div :class="focus === 4 ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700'" class="{{ $zone(4) }}">
                            <p class="ez-phosphor">INTERCEPT &gt; Hostile movement reported nearby.</p>
                            <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">4</span>
                        </div>
                    </div>
                    <div :class="focus === 5 ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700'" class="{{ $zone(5) }} space-y-2">
                        <p class="ez-display text-lg text-ez-ink">Survivor condition</p>
                        @foreach (['Health' => 73, 'Hunger' => 46, 'Fatigue' => 60] as $meter => $width)
                            <span class="block ez-meter !h-1.5 {{ $meter === 'Fatigue' ? 'text-amber-400' : 'text-ez-signal' }}"><span class="ez-meter-fill block" style="width: {{ $width }}%"></span></span>
                        @endforeach
                        <p class="pt-1 font-mono text-[9px] text-ez-muted uppercase">Rations</p>
                        <div class="flex items-center justify-between rounded-sm border border-zinc-700 p-1.5"><span class="text-ez-ink">Borscht ×2</span><span class="rounded-sm bg-zinc-700 px-1.5 text-ez-ink">Eat</span></div>
                        <p class="font-mono text-[9px] text-ez-muted uppercase">Medical</p>
                        <div class="flex items-center justify-between rounded-sm border border-red-500/40 p-1.5"><span class="text-ez-ink">Antiseptic ×1</span><span class="rounded-sm bg-zinc-700 px-1.5 text-ez-ink">Use</span></div>
                        <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">5</span>
                    </div>
                </div>

                <div :class="focus === 6 ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700 border-dashed'" class="{{ $zone(6) }} flex items-center justify-between">
                    <span class="text-ez-ink">Viktor the Operator · <span class="text-ez-muted">Listen to Viktor's findings</span></span>
                    <span class="rounded-sm bg-ez-signal px-2 py-0.5 text-ez-signal-ink">Speak</span>
                    <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">6</span>
                </div>

                <div class="flex items-end justify-between gap-3">
                    <p class="ez-display text-2xl text-ez-ink">City actions</p>
                    <span :class="focus === 8 ? 'border-ez-signal bg-ez-signal/10' : 'border-ez-signal/50'" class="relative rounded-sm border px-2 py-1 font-mono text-ez-signal">[ OPEN PDA ]
                        <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">8</span>
                    </span>
                </div>
                <div :class="focus === 7 ? 'border-ez-signal bg-ez-signal/10' : 'border-zinc-700'" class="{{ $zone(7) }} grid gap-2 sm:grid-cols-2">
                    @foreach ([['District patrols', 'Begin work', 'bg-ez-signal text-ez-signal-ink', false], ['Clear the ruins', 'Begin work', 'bg-orange-600 text-white', true]] as [$label, $button, $tone, $fight])
                        <div @class(['rounded-sm border p-2', 'border-orange-500/50' => $fight, 'border-zinc-700' => ! $fight])>
                            <p class="flex justify-between text-ez-ink"><span class="font-semibold">{{ $label }}</span><span class="font-mono text-[8px] text-amber-300 uppercase">Medium</span></p>
                            <p class="mt-1 font-mono text-[8px] text-ez-muted uppercase">{{ $fight ? 'Melee combat' : 'Scavenging' }} · Level 1 required</p>
                            @if ($fight)<p class="mt-1 text-orange-300">⚠ Fight: Looter</p>@endif
                            <p class="mt-2 rounded-sm py-1 text-center {{ $tone }}">{{ $button }}</p>
                        </div>
                    @endforeach
                    <span class="absolute -top-2.5 -left-2.5 flex size-6 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">7</span>
                </div>
            </div>

            {{-- Legend --}}
            <ol class="space-y-2">
                @foreach ($parts as $number => [$name, $copy])
                    <li>
                        <button
                            type="button"
                            @mouseenter="focus = {{ $number }}"
                            @mouseleave="focus = null"
                            @focus="focus = {{ $number }}"
                            @blur="focus = null"
                            @click="focus = focus === {{ $number }} ? null : {{ $number }}"
                            :class="focus === {{ $number }} ? 'border-ez-signal/70 bg-ez-signal/5' : 'border-zinc-800'"
                            class="flex w-full items-start gap-3 rounded-sm border p-3 text-left transition"
                        >
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-ez-signal font-mono text-[11px] font-semibold text-ez-signal-ink">{{ $number }}</span>
                            <span>
                                <span class="block font-medium text-ez-ink">{{ $name }}</span>
                                <span class="mt-0.5 block text-sm leading-6 text-ez-muted">{{ $copy }}</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </div>
    </x-manual.section>

    <x-manual.section id="cards" title="Reading an action card">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="ez-card ez-pin p-5 pt-6">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-lg font-semibold text-ez-ink">Borscht cooking</p>
                    <x-manual.risk level="low" />
                </div>
                <p class="mt-2 text-sm text-ez-muted">Simmer foraged beets and clean water into borscht for the metro shelters.</p>
                <p class="ez-label mt-3">Cooking · Level 1 required · Reward Borscht</p>
                <p class="mt-3 text-sm text-ez-muted">Each cycle: hunger +2 · fatigue +2</p>
                <p class="text-sm text-ez-muted">Uses: <span class="text-ez-signal">Clean Water ×1 (4 carried)</span>, <span class="text-amber-300">Foraged Produce ×2 (0 carried)</span></p>
                <p class="mt-4 rounded-sm bg-zinc-700/70 py-2 text-center text-sm text-ez-ink">Missing items</p>
            </div>
            <dl class="space-y-4 text-sm leading-6">
                @foreach ([
                    ['Risk badge', 'Low, medium, high or extreme. High-risk actions can have an accident chance that costs health.'],
                    ['Skill · level · reward', 'Which skill gains the XP, the level you need to start, and the item each cycle gives.'],
                    ['Each cycle', 'How much hunger and fatigue every cycle adds.'],
                    ['Uses', 'Items used up every cycle, with how many you carry. Teal means you have enough, amber means you are short.'],
                    ['Red card + “Fight”', 'This action is a fight: each cycle is one round against the enemy named, and you can get hurt.'],
                ] as [$term, $definition])
                    <div>
                        <dt class="font-medium text-ez-ink">{{ $term }}</dt>
                        <dd class="text-ez-muted">{{ $definition }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </x-manual.section>

    <x-manual.section id="buttons" title="Button states">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Begin work', 'bg-ez-signal text-ez-signal-ink', 'You can start this now.'],
                ['Begin work', 'bg-orange-600 text-white', 'You can start, but it is a fight.'],
                ['Missing items', 'bg-zinc-700/70 text-ez-ink', 'You are short of an item it uses.'],
                ['Locked', 'bg-zinc-700/70 text-ez-muted', 'Your skill level is too low.'],
            ] as [$label, $tone, $copy])
                <li class="ez-card p-4">
                    <p class="rounded-sm py-2 text-center text-sm font-medium {{ $tone }}">{{ $label }}</p>
                    <p class="mt-3 text-sm text-ez-muted">{{ $copy }}</p>
                </li>
            @endforeach
        </ul>
    </x-manual.section>

    <x-manual.section id="timer" title="While you work" intro="When a job or journey is running, an Active timer panel shows a scene for the work, the time left on this cycle and what the last cycle paid.">
        <div class="grid gap-4 md:grid-cols-3">
            <x-manual.callout title="One thing at a time">You can run one job, one journey or one Brain Scorcher burn at a time. Stop or finish it before starting something else.</x-manual.callout>
            <x-manual.callout title="Eat on the go">Eating and using medical items work while a timer runs, so you never need to stop to look after yourself.</x-manual.callout>
            <x-manual.callout type="warn" title="Away from the keyboard">Work loops keep paying out for up to 8 hours while you are away. Come back before then to start a fresh loop.</x-manual.callout>
        </div>
    </x-manual.section>

    <x-manual.pager :previous="$previous" :next="$next" />
</x-manual.layout>
