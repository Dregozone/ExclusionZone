<x-manual.layout
    title="Skills"
    lede="Every skill starts at level 1 and has no cap. Pick one to see every action that trains it: the level you need, where to go, what it uses and what it gives."
    :breadcrumbs="['Skills' => null]"
>
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <span class="ez-label">Tiers</span>
        @foreach ($tiers as $tier)
            <span class="rounded-sm border border-zinc-700 px-2.5 py-1 text-ez-ink">{{ Str::title($tier->tier) }} <span class="font-mono text-xs text-ez-muted">{{ $tier->level_min }}{{ $tier->level_max ? '–'.$tier->level_max : '+' }}</span></span>
        @endforeach
        <a href="{{ route('manual.guide', 'skills-and-work') }}" class="ml-auto text-ez-signal hover:underline" wire:navigate>How XP and levels work →</a>
    </div>

    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($skills as ['skill' => $skill, 'actions' => $actions])
            @php
                $cities = $actions->map(fn (array $row) => $row['action']->city->city)->unique()->sort()->values();
                $image = file_exists(public_path("images/work/{$skill->key}.jpg")) ? "images/work/{$skill->key}.jpg" : 'images/work/default.jpg';
            @endphp
            <li class="min-w-0">
                <a href="{{ route('manual.skill', $skill->key) }}" class="ez-card group flex h-full flex-col overflow-hidden transition hover:border-ez-signal/60" wire:navigate>
                    <div class="relative">
                        <img src="{{ asset($image) }}" alt="" class="aspect-[2/1] w-full object-cover opacity-60 transition group-hover:opacity-85" loading="lazy" />
                        <div class="absolute inset-0 bg-gradient-to-t from-ez-surface via-transparent to-transparent"></div>
                        <p class="ez-display absolute bottom-2 left-4 text-4xl text-ez-ink">{{ $skill->display_name }}</p>
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col p-4">
                        <p class="text-sm text-ez-muted">{{ $skill->description }}</p>
                        <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-zinc-800 pt-3 text-xs">
                            <div><dt class="ez-label !text-[9px]">Actions</dt><dd class="ez-numeral mt-1 text-3xl text-ez-ink">{{ $actions->count() }}</dd></div>
                            <div><dt class="ez-label !text-[9px]">Cities</dt><dd class="ez-numeral mt-1 text-3xl text-ez-ink">{{ $cities->count() }}</dd></div>
                            <div><dt class="ez-label !text-[9px]">Levels</dt><dd class="ez-numeral mt-1 text-3xl text-ez-signal">{{ $actions->isEmpty() ? '—' : $actions->min(fn ($row) => $row['action']->min_level).'–'.$actions->max(fn ($row) => $row['action']->min_level) }}</dd></div>
                        </dl>
                        @if ($cities->isNotEmpty())
                            <p class="mt-3 truncate text-xs text-zinc-500">{{ $cities->implode(' · ') }}</p>
                        @else
                            <p class="mt-3 text-xs text-zinc-500">Trained through trouble fights and quests.</p>
                        @endif
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
</x-manual.layout>
