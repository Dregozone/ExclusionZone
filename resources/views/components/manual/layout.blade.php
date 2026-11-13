@props([
    'title',
    'eyebrow' => "Survivor's Manual",
    'lede' => null,
    'image' => null,
    'breadcrumbs' => [],
    'toc' => [],
])

@php
    $sections = [
        ['label' => 'Overview', 'route' => 'manual.index', 'active' => request()->routeIs('manual.index')],
        ['label' => 'Guides', 'route' => ['manual.guide', 'getting-started'], 'active' => request()->routeIs('manual.guide')],
        ['label' => 'Skills', 'route' => 'manual.skills', 'active' => request()->routeIs('manual.skills', 'manual.skill')],
        ['label' => 'Locations', 'route' => 'manual.locations', 'active' => request()->routeIs('manual.locations', 'manual.location')],
        ['label' => 'Items', 'route' => 'manual.items', 'active' => request()->routeIs('manual.items', 'manual.item')],
        ['label' => 'Quests', 'route' => 'manual.quests', 'active' => request()->routeIs('manual.quests')],
        ['label' => 'FAQ', 'route' => 'manual.faq', 'active' => request()->routeIs('manual.faq')],
    ];
@endphp

<x-layouts::app :title="str_contains($title, 'Manual') ? $title : $title.' · Manual'">
    <div class="mx-auto w-full max-w-6xl" data-manual>
        {{-- Manual bar: section tabs + search --}}
        <div class="mb-8 flex flex-col gap-3 border-b border-zinc-700 pb-3 xl:flex-row xl:items-center xl:justify-between">
            <nav aria-label="Manual sections" class="-mx-1 flex gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($sections as $section)
                    <a
                        href="{{ is_array($section['route']) ? route(...$section['route']) : route($section['route']) }}"
                        @class([
                            'shrink-0 rounded-sm px-3 py-2 text-sm transition',
                            'bg-ez-signal/10 font-medium text-ez-signal' => $section['active'],
                            'text-ez-muted hover:bg-white/5 hover:text-ez-ink' => ! $section['active'],
                        ])
                        @if ($section['active']) aria-current="page" @endif
                        wire:navigate
                    >{{ $section['label'] }}</a>
                @endforeach
            </nav>

            <div
                class="relative w-full xl:w-80"
                x-data="manualSearch(@js(route('manual.search')))"
                @click.outside="open = false"
                @keydown.escape="open = false; $refs.input.blur()"
            >
                <label for="manual-search" class="sr-only">Search the manual</label>
                <flux:icon.magnifying-glass variant="micro" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-zinc-500" />
                <input
                    id="manual-search"
                    x-ref="input"
                    x-model="query"
                    @focus="load()"
                    @input="active = 0; open = true"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="go()"
                    type="search"
                    autocomplete="off"
                    placeholder="Search skills, cities, items…"
                    role="combobox"
                    aria-controls="manual-search-results"
                    :aria-expanded="open && query.length > 0"
                    class="w-full rounded-sm border border-zinc-700 bg-ez-ground/80 py-2 pr-10 pl-9 text-sm text-ez-ink placeholder:text-zinc-500 focus:border-ez-signal focus:outline-none"
                />
                <kbd class="pointer-events-none absolute top-1/2 right-2.5 hidden -translate-y-1/2 rounded border border-zinc-700 px-1.5 font-mono text-[10px] text-zinc-500 sm:block">/</kbd>

                <div
                    x-show="open && query.length > 0"
                    x-cloak
                    id="manual-search-results"
                    role="listbox"
                    class="absolute right-0 left-0 z-40 mt-1 max-h-96 overflow-y-auto rounded-sm border border-zinc-700 bg-ez-surface shadow-2xl"
                >
                    <template x-if="loading">
                        <p class="px-3 py-3 text-sm text-ez-muted">Tuning in…</p>
                    </template>
                    <template x-for="(result, index) in results" :key="result.url">
                        <a
                            :href="result.url"
                            role="option"
                            :aria-selected="index === active"
                            @mouseenter="active = index"
                            :class="index === active ? 'bg-ez-signal/10' : ''"
                            class="flex items-start justify-between gap-3 border-b border-zinc-800 px-3 py-2.5 last:border-0"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-ez-ink" x-text="result.title"></span>
                                <span class="block truncate text-xs text-ez-muted" x-text="result.hint"></span>
                            </span>
                            <span class="ez-label shrink-0 pt-0.5 !text-[10px]" x-text="result.type"></span>
                        </a>
                    </template>
                    <template x-if="!loading && entries !== null && results.length === 0">
                        <p class="px-3 py-3 text-sm text-ez-muted">Nothing on that frequency. Try a skill, city or item name.</p>
                    </template>
                </div>
            </div>
        </div>

        {{-- Breadcrumbs --}}
        @if ($breadcrumbs !== [])
            <nav aria-label="Breadcrumb" class="mb-4">
                <ol class="flex flex-wrap items-center gap-1.5 font-mono text-[11px] tracking-wider text-zinc-500 uppercase">
                    <li><a href="{{ route('manual.index') }}" class="hover:text-ez-signal" wire:navigate>Manual</a></li>
                    @foreach ($breadcrumbs as $label => $url)
                        <li aria-hidden="true">/</li>
                        <li>
                            @if ($url)
                                <a href="{{ $url }}" class="hover:text-ez-signal" wire:navigate>{{ $label }}</a>
                            @else
                                <span class="text-ez-muted" aria-current="page">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        {{-- Page heading --}}
        <header @class(['mb-10 grid gap-6', 'lg:grid-cols-[1.25fr_0.75fr] lg:items-end' => $image])>
            <div>
                <p class="ez-label !text-ez-signal">{{ $eyebrow }}</p>
                <h1 class="ez-display mt-2 text-5xl text-ez-ink sm:text-6xl">{{ $title }}</h1>
                @if ($lede)
                    <p class="mt-4 max-w-2xl text-lg leading-8 text-ez-muted">{{ $lede }}</p>
                @endif
                @isset($meta)
                    <div class="mt-5">{{ $meta }}</div>
                @endisset
            </div>

            @if ($image)
                <figure class="relative overflow-hidden rounded-sm border border-zinc-700">
                    <img src="{{ asset($image) }}" alt="" class="aspect-[3/2] w-full object-cover opacity-80" loading="lazy" />
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ez-ground via-transparent to-transparent"></div>
                </figure>
            @endif
        </header>

        @if ($toc !== [])
            <div class="grid gap-10 xl:grid-cols-[minmax(0,1fr)_12rem]">
                <div class="min-w-0 space-y-14">{{ $slot }}</div>
                <aside class="hidden xl:block" aria-label="On this page">
                    <div class="sticky top-6">
                        <p class="ez-label">On this page</p>
                        <ol class="mt-3 space-y-1 border-l border-zinc-700">
                            @foreach ($toc as $anchor => $label)
                                <li><a href="#{{ $anchor }}" class="-ml-px block border-l border-transparent py-1 pl-3 text-sm text-ez-muted hover:border-ez-signal hover:text-ez-ink">{{ $label }}</a></li>
                            @endforeach
                        </ol>
                    </div>
                </aside>
            </div>
        @else
            <div class="space-y-14">{{ $slot }}</div>
        @endif

        <footer class="mt-20 flex flex-col gap-3 border-t border-zinc-700 pt-6 pb-4 text-sm text-ez-muted sm:flex-row sm:items-center sm:justify-between">
            <p>Figures on these pages are read live from the game, so they are always current.</p>
            @guest
                <a href="{{ route('register') }}" class="ez-button !min-h-10 !py-2 text-sm">Create a survivor</a>
            @else
                <a href="{{ route('dashboard') }}" class="ez-button ez-button-ghost !min-h-10 !py-2 text-sm">Back to your city</a>
            @endguest
        </footer>
    </div>
</x-layouts::app>
