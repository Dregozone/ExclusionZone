<x-manual.layout
    title="Survivor's Manual"
    eyebrow="Field guide · readable by anyone, no account needed"
    lede="Everything you need to survive the Exclusion Zone: how to start, how the screen works, every skill and what it unlocks, every city and what you can do there, and where every item comes from."
>
    {{-- Start here --}}
    <a href="{{ route('manual.guide', 'getting-started') }}" class="ez-panel group relative block overflow-hidden transition hover:border-ez-signal/60" wire:navigate>
        <img src="{{ asset('images/work/travel.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30 transition group-hover:opacity-40" />
        <div class="absolute inset-0 bg-gradient-to-r from-ez-ground via-ez-ground/85 to-transparent"></div>
        <div class="relative grid gap-6 p-6 sm:p-10 md:grid-cols-[1fr_auto] md:items-end">
            <div>
                <p class="ez-label !text-amber-400">New here? Start with this</p>
                <p class="ez-display mt-3 text-5xl text-ez-ink sm:text-6xl">Just arrived</p>
                <p class="mt-3 max-w-xl leading-7 text-ez-muted">
                    You wake up in {{ $startingCity?->city ?? 'Kyiv' }} with an empty pack and every skill at level 1.
                    This guide walks you through your first hour: your first job, your first meal, your first trip.
                </p>
            </div>
            <span class="ez-button self-start md:self-end">Read the first steps →</span>
        </div>
    </a>

    {{-- The Zone in numbers --}}
    <dl class="grid grid-cols-2 border-y border-zinc-700 lg:grid-cols-4">
        @foreach ([
            [$counts['cities'], 'Cities', route('manual.locations')],
            [$counts['skills'], 'Skills', route('manual.skills')],
            [$counts['actions'], 'City actions', route('manual.skills')],
            [$counts['items'], 'Items', route('manual.items')],
        ] as [$figure, $label, $url])
            <a href="{{ $url }}" @class(['group border-zinc-700 px-4 py-6 transition hover:bg-white/5 sm:px-6', 'border-r' => ! $loop->last, 'max-lg:border-b' => $loop->index < 2, 'max-lg:border-r-0' => $loop->index === 1]) wire:navigate>
                <dd class="ez-numeral text-6xl text-ez-ink group-hover:text-ez-signal sm:text-7xl">{{ $figure }}</dd>
                <dt class="ez-label mt-2">{{ $label }} →</dt>
            </a>
        @endforeach
    </dl>

    {{-- Guides in reading order --}}
    <x-manual.section id="guides" eyebrow="Learning the basics" title="Guides, in reading order" intro="Short, practical pages. Read them top to bottom the first time; come back to any one when you need it.">
        <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($guides as $slug => $guide)
                <li>
                    <a href="{{ route('manual.guide', $slug) }}" class="ez-card group flex h-full flex-col overflow-hidden transition hover:border-ez-signal/60" wire:navigate>
                        <div class="relative">
                            <img src="{{ asset($guide['image']) }}" alt="" class="aspect-[16/9] w-full object-cover opacity-70 transition group-hover:opacity-90" loading="lazy" />
                            <span class="ez-numeral absolute bottom-2 left-3 text-5xl text-ez-ink drop-shadow-lg">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <p class="flex items-center gap-2 font-semibold text-ez-ink group-hover:text-ez-signal">
                                <flux:icon :name="$guide['icon']" variant="mini" class="text-ez-signal" />
                                {{ $guide['title'] }}
                            </p>
                            <p class="mt-1.5 text-sm leading-6 text-ez-muted">{{ $guide['summary'] }}</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ol>
    </x-manual.section>

    {{-- Skills bar --}}
    <x-manual.section id="skills" eyebrow="Skill manuals" title="Pick a skill" intro="Each skill page lists every action that trains it: the level you need, where to do it, what it uses up and what it gives back.">
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($skills as $skill)
                <li>
                    <a href="{{ route('manual.skill', $skill->key) }}" class="ez-card group relative block overflow-hidden transition hover:border-ez-signal/60" wire:navigate>
                        <img src="{{ asset(file_exists(public_path("images/work/{$skill->key}.jpg")) ? "images/work/{$skill->key}.jpg" : 'images/work/default.jpg') }}" alt="" class="aspect-[4/3] w-full object-cover opacity-55 transition group-hover:scale-105 group-hover:opacity-75" loading="lazy" />
                        <div class="absolute inset-0 bg-gradient-to-t from-ez-ground via-ez-ground/40 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-3">
                            <p class="font-semibold text-ez-ink group-hover:text-ez-signal">{{ $skill->display_name }}</p>
                            <p class="font-mono text-[11px] text-ez-muted">{{ $skill->actions_count }} {{ Str::plural('action', $skill->actions_count) }}</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-manual.section>

    {{-- Reference --}}
    <x-manual.section id="reference" eyebrow="Reference" title="Look something up">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Locations', 'Every city, its weather, danger, neighbours and actions.', 'manual.locations', 'map'],
                ['Items', 'Where every item comes from and what it is used for.', 'manual.items', 'archive-box'],
                ['Quests & jobs', 'The story chain and repeatable contracts.', 'manual.quests', 'clipboard-document-list'],
                ['FAQ', 'Short answers to the questions new survivors ask most.', 'manual.faq', 'question-mark-circle'],
            ] as [$label, $copy, $routeName, $icon])
                <a href="{{ route($routeName) }}" class="ez-card ez-pin group p-5 pt-6 transition hover:border-ez-signal/60" wire:navigate>
                    <flux:icon :name="$icon" class="text-ez-signal" />
                    <p class="mt-3 text-lg font-semibold text-ez-ink group-hover:text-ez-signal">{{ $label }}</p>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $copy }}</p>
                </a>
            @endforeach
        </div>
    </x-manual.section>
</x-manual.layout>
