@props([
    'mapData' => ['nodes' => [], 'edges' => [], 'player' => null],
    'compact' => false,
    'dark' => false,
])

@php
    $player = $mapData['player'] ?? null;
    $chipBase = $compact
        ? 'shrink-0 rounded-md border px-2 py-1 text-[10px] font-medium uppercase tracking-wider transition-colors'
        : 'shrink-0 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors';
@endphp

<div
    x-data="geoMap(@js($mapData), @js(['compact' => $compact, 'dark' => $dark]))"
    @class([
        'relative flex w-full flex-col',
        'dark h-full gap-2' => $dark && $compact,
        'dark gap-3' => $dark && ! $compact,
        'gap-3' => ! $dark,
    ])
    data-geo-map
>
    {{-- Where am I + search --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        @if (! $compact && $player)
            <div class="min-w-0 text-sm text-zinc-600 dark:text-zinc-300" data-test="map-status">
                <template x-if="transit">
                    <p class="flex items-center gap-2">
                        <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-amber-500"></span>
                        <span>En route from <span class="font-semibold text-zinc-900 dark:text-white" x-text="transit.from.label"></span> to <span class="font-semibold text-zinc-900 dark:text-white" x-text="transit.to.label"></span>.</span>
                    </p>
                </template>
                <template x-if="!transit && currentCity">
                    <p class="flex items-center gap-2">
                        <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500 ring-4 ring-emerald-500/20"></span>
                        <span>You are in <span class="font-semibold text-zinc-900 dark:text-white" x-text="currentCity.label"></span><span x-text="`, ${currentCity.country}`"></span>
                            · <span x-text="`${nextStops.length} ${nextStops.length === 1 ? 'route' : 'routes'} out`"></span></span>
                    </p>
                </template>
            </div>
        @endif

        <div class="relative {{ $compact ? 'w-full' : 'w-full sm:ml-auto sm:w-64' }}" @click.outside="searchOpen = false">
            <label class="sr-only" for="geo-map-search-{{ $compact ? 'pda' : 'page' }}">Find a city</label>
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input
                id="geo-map-search-{{ $compact ? 'pda' : 'page' }}"
                type="search"
                autocomplete="off"
                placeholder="Find a city or country…"
                x-model="query"
                @focus="searchOpen = true"
                @input="searchOpen = true; searchIndex = 0"
                @keydown="onSearchKey($event)"
                role="combobox"
                :aria-expanded="(searchOpen && searchResults.length > 0).toString()"
                aria-controls="geo-map-results-{{ $compact ? 'pda' : 'page' }}"
                class="w-full rounded-lg border border-zinc-200 bg-white py-1.5 pl-8 pr-3 text-zinc-900 placeholder:text-zinc-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 {{ $compact ? 'text-xs' : 'text-sm' }}"
            >
            <ul
                id="geo-map-results-{{ $compact ? 'pda' : 'page' }}"
                role="listbox"
                x-show="searchOpen && searchResults.length > 0"
                x-cloak
                class="absolute inset-x-0 top-full z-30 mt-1 overflow-hidden rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
            >
                <template x-for="(result, index) in searchResults" :key="result.id">
                    <li
                        role="option"
                        :aria-selected="(index === searchIndex).toString()"
                        @mousedown.prevent="chooseSearchResult(result.id)"
                        @mouseenter="searchIndex = index"
                        :class="index === searchIndex ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                        class="flex cursor-pointer items-center justify-between gap-2 px-3 py-1.5 text-sm"
                    >
                        <span class="min-w-0 truncate">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100" x-text="result.label"></span>
                            <span class="text-zinc-500 dark:text-zinc-400" x-text="result.country"></span>
                        </span>
                        <span x-show="result.here" class="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Here</span>
                        <span x-show="result.reachable" class="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-sky-600 dark:text-sky-400">Can travel</span>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    {{-- Quick views --}}
    <div role="toolbar" aria-label="Map views" class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-0.5">
        <template x-for="preset in presets" :key="preset.key">
            <button
                type="button"
                @click="applyPreset(preset.key)"
                :aria-pressed="(activePreset === preset.key).toString()"
                :class="activePreset === preset.key
                    ? 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-600'
                    : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100'"
                class="{{ $chipBase }} inline-flex items-center gap-1.5"
            >
                <svg x-show="preset.key === 'around'" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
                <span x-text="preset.label"></span>
                <span x-show="preset.count" class="opacity-60" x-text="preset.count"></span>
            </button>
        </template>
    </div>

    {{-- Map --}}
    <div
        x-ref="viewport"
        tabindex="0"
        aria-label="Interactive world map. Drag or use arrow keys to move, plus and minus to zoom, Tab to move between cities."
        @class([
            'relative cursor-grab touch-none select-none overflow-hidden border outline-none focus-visible:ring-2 focus-visible:ring-emerald-500',
            'min-h-[280px] flex-1 rounded-xl border-zinc-800' => $compact,
            'h-[min(68vh,620px)] min-h-[360px] rounded-2xl border-zinc-200 dark:border-zinc-700' => ! $compact,
        ])
    >
        <svg x-ref="svg" class="absolute inset-0 block h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <g data-layer="land" x-ref="landLayer"></g>
            <g data-layer="routes" x-ref="routesLayer"></g>
            <g data-layer="cities" x-ref="citiesLayer"></g>
        </svg>

        {{-- Off-screen pointers --}}
        <div x-ref="hints" class="pointer-events-none absolute inset-0 [&>*]:pointer-events-auto"></div>

        {{-- Loading / failure --}}
        <div x-show="!geometryReady" x-transition.opacity class="pointer-events-none absolute left-3 top-3 rounded-md bg-white/80 px-2 py-1 text-[11px] text-zinc-500 dark:bg-zinc-900/80 dark:text-zinc-400">
            <span x-show="!geometryFailed">Loading coastlines…</span>
            <span x-show="geometryFailed" x-cloak>Coastlines unavailable — cities and routes still work.</span>
        </div>

        {{-- Zoom controls --}}
        <div data-map-ui data-map-controls class="absolute right-3 top-3 flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white/95 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/95">
            <button type="button" @click="zoomBy(1.6)" class="grid h-8 w-8 place-items-center text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="Zoom in" title="Zoom in">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </button>
            <button type="button" @click="zoomBy(1 / 1.6)" class="grid h-8 w-8 place-items-center border-t border-zinc-200 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="Zoom out" title="Zoom out">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/></svg>
            </button>
            <template x-if="currentCity">
                <button type="button" @click="findMe()" class="grid h-8 w-8 place-items-center border-t border-zinc-200 text-emerald-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-emerald-400 dark:hover:bg-zinc-800" aria-label="Show my location" title="Show my location">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
                </button>
            </template>
            <button type="button" @click="applyPreset('world')" class="grid h-8 w-8 place-items-center border-t border-zinc-200 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="Show the whole world" title="Show the whole world">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
            </button>
        </div>

        {{-- First-use hint --}}
        <div
            x-show="showHint && !selectedId"
            x-transition.opacity
            class="pointer-events-none absolute bottom-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-zinc-900/75 px-3 py-1 text-[11px] text-white backdrop-blur-sm"
        >
            Drag to move · scroll or pinch to zoom · tap a city
        </div>

        {{-- Selected city --}}
        <template x-if="selectedInfo">
            <div
                data-map-ui
                data-test="map-city-card"
                @class([
                    'absolute z-20 cursor-auto rounded-xl border border-zinc-200 bg-white/95 shadow-xl backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-900/95',
                    'inset-x-2 bottom-2 p-3' => $compact,
                    'bottom-2 left-2 right-2 p-3 sm:bottom-3 sm:left-3 sm:right-auto sm:w-80 sm:p-4' => ! $compact,
                ])
                role="dialog"
                :aria-label="`${selectedInfo.node.label} details`"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-zinc-900 dark:text-white {{ $compact ? 'text-sm' : 'text-base' }}" x-text="selectedInfo.node.label"></p>
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400" x-text="[selectedInfo.node.country, selectedInfo.node.continent].filter(Boolean).join(' · ')"></p>
                    </div>
                    <button type="button" @click="clearSelection()" class="-mr-1 -mt-1 rounded p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" aria-label="Close">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="{{ $compact ? 'mt-1.5' : 'mt-2' }} flex flex-wrap gap-1">
                    <template x-for="fact in [
                        @if (! $compact) selectedInfo.node.biome, @endif
                        selectedInfo.node.loot_tier ? `${selectedInfo.node.loot_tier} loot` : null,
                        selectedInfo.node.weather ? `Weather: ${selectedInfo.node.weather}` : null,
                        selectedInfo.node.has_trade_post ? 'Trade post' : null,
                    ].filter(Boolean)" :key="fact">
                        <span class="inline-block rounded-md bg-zinc-100 px-1.5 py-0.5 text-[11px] text-zinc-600 first-letter:uppercase dark:bg-zinc-800 dark:text-zinc-300" x-text="fact"></span>
                    </template>
                </div>

                {{-- Route status --}}
                <div class="{{ $compact ? 'mt-2 text-xs' : 'mt-3 text-sm' }}">
                    <template x-if="selectedInfo.mode === 'here'">
                        <p class="flex items-center gap-2 font-medium text-emerald-700 dark:text-emerald-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span x-text="`You are here · ${nextStops.length} ${nextStops.length === 1 ? 'route' : 'routes'} out`"></span>
                        </p>
                    </template>
                    <template x-if="selectedInfo.mode === 'arriving'">
                        <p class="flex items-center gap-2 font-medium text-amber-700 dark:text-amber-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                            You are on your way here.
                        </p>
                    </template>
                    <template x-if="selectedInfo.mode === 'direct'">
                        <p class="flex items-center gap-2 font-medium text-sky-700 dark:text-sky-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>
                            <span x-text="`Direct route from ${currentCity.label}` + (selectedInfo.duration ? ` · about ${selectedInfo.duration}` : '')"></span>
                        </p>
                    </template>
                    <template x-if="selectedInfo.mode === 'route'">
                        <div>
                            <p class="font-medium text-zinc-800 dark:text-zinc-200" x-text="`${selectedInfo.stops} moves away`"></p>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400" x-text="`${currentCity.label} → ${[...selectedInfo.via, selectedInfo.node.label].join(' → ')}`"></p>
                        </div>
                    </template>
                    <template x-if="selectedInfo.mode === 'unreachable'">
                        <p class="text-zinc-500 dark:text-zinc-400">No route connects your city to here.</p>
                    </template>
                    <template x-if="selectedInfo.mode === 'unplaced'">
                        <p class="text-zinc-500 dark:text-zinc-400">Start your run from the city menu to travel.</p>
                    </template>
                    <template x-if="selectedInfo.mode === 'guest'">
                        <p class="text-zinc-500 dark:text-zinc-400">
                            <a href="{{ route('login') }}" class="font-medium text-emerald-700 underline underline-offset-2 dark:text-emerald-400">Log in</a> to travel here.
                        </p>
                    </template>
                </div>

                {{-- Job objectives --}}
                <template x-for="objective in selectedInfo.objectives" :key="objective.quest">
                    <p class="mt-2 rounded-lg border border-amber-300/70 bg-amber-50 px-2.5 py-1.5 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                        <span class="font-semibold">Objective:</span>
                        <span x-text="objective.action ? `${objective.action} — ${objective.quest}` : objective.quest"></span>
                    </p>
                </template>

                {{-- Travel --}}
                <template x-if="selectedInfo.travelTarget">
                    <div class="{{ $compact ? 'mt-2' : 'mt-3' }}">
                        <button
                            type="button"
                            @click="travel(selectedInfo.travelTarget.id)"
                            :disabled="!selectedInfo.canTravel"
                            data-test="map-travel-button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 {{ $compact ? 'py-1.5' : 'py-2' }} text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:text-zinc-500 dark:focus-visible:ring-offset-zinc-900 dark:disabled:bg-zinc-800 dark:disabled:text-zinc-500"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            <span x-text="`Travel to ${selectedInfo.travelTarget.label}`"></span>
                        </button>
                        <p x-show="selectedInfo.mode === 'route'" class="mt-1.5 text-center text-[11px] text-zinc-500 dark:text-zinc-400" x-text="`First stop of ${selectedInfo.stops} on the way to ${selectedInfo.node.label}.`"></p>
                        <p x-show="!selectedInfo.canTravel && selectedInfo.restriction" class="mt-1.5 text-center text-[11px] text-amber-700 dark:text-amber-400" x-text="selectedInfo.restriction"></p>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- Where you can go from here --}}
    <template x-if="nextStops.length > 0">
        <div class="flex items-center gap-2 overflow-x-auto pb-0.5">
            <span class="shrink-0 text-zinc-500 dark:text-zinc-400 {{ $compact ? 'text-[10px] uppercase tracking-wider' : 'text-xs font-medium' }}">Next stops</span>
            <template x-for="stop in nextStops" :key="stop.id">
                <button
                    type="button"
                    @click="focusCity(stop.id)"
                    :class="selectedId === stop.id
                        ? 'border-sky-500 bg-sky-50 text-sky-800 dark:border-sky-400 dark:bg-sky-500/15 dark:text-sky-200'
                        : 'border-zinc-200 bg-white text-zinc-700 hover:border-sky-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-sky-500'"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs transition-colors"
                >
                    <span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>
                    <span x-text="stop.label"></span>
                    <span x-show="stop.objective" class="inline-block h-1.5 w-1.5 rotate-45 bg-amber-500" title="Job objective"></span>
                </button>
            </template>
        </div>
    </template>

    {{-- Legend --}}
    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-zinc-600 dark:text-zinc-400 {{ $compact ? 'text-[10px]' : 'text-xs' }}" aria-label="Map legend">
        @if ($player)
            <li class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-500/30"></span>You are here</li>
            <li class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-sky-500"></span>Can travel now</li>
            <li class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rotate-45 bg-amber-500"></span>Job objective</li>
        @endif
        <li class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full bg-slate-400 dark:bg-slate-500"></span>City</li>
        <li class="flex items-center gap-1.5">
            <svg width="22" height="8" aria-hidden="true"><path d="M1 6 Q11 0 21 6" fill="none" stroke="currentColor" stroke-opacity="0.6" stroke-width="1.5"/></svg>
            Route
        </li>
        <li class="flex items-center gap-1.5">
            <svg width="22" height="8" aria-hidden="true"><path d="M1 6 Q11 0 21 6" fill="none" stroke="#10b981" stroke-width="2.5" stroke-dasharray="2 4" stroke-linecap="round"/></svg>
            Way to selected city
        </li>
    </ul>

    @if ($player)
        <form x-ref="travelForm" method="POST" action="{{ route('travel.store') }}" class="hidden">
            @csrf
            <input type="hidden" name="city_id" value="">
        </form>
    @endif
</div>
