<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('World Map') }}</flux:heading>
        <flux:text class="mt-1">{{ __('All cities and their travel connections. Arrows show the allowed direction of travel. Dashed lines show cross-continental routes.') }}</flux:text>
    </div>

    @php
        $mapData       = $this->mapData;
        $currentCityId = $this->currentCityId;
    @endphp

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-6 border-b border-zinc-100 px-6 py-4 dark:border-zinc-800 text-sm">
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-emerald-300 dark:ring-emerald-700"></div>
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Your location') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-zinc-300 dark:bg-zinc-600 ring-1 ring-zinc-400 dark:ring-zinc-500"></div>
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('City') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg width="28" height="12" class="shrink-0">
                    <line x1="2" y1="6" x2="20" y2="6" stroke="currentColor" stroke-width="1.5" class="text-zinc-400 dark:text-zinc-600" />
                    <polygon points="20,3 26,6 20,9" fill="currentColor" class="text-zinc-400 dark:text-zinc-600" />
                </svg>
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Travel route') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg width="32" height="12" class="shrink-0">
                    <line x1="2" y1="6" x2="22" y2="6" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,2" />
                    <polygon points="22,3 28,6 22,9" fill="#f59e0b" />
                </svg>
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Cross-continental route') }}</span>
            </div>
        </div>

        <div class="p-6">
            <x-geo-map :map-data="$mapData" :current-city-id="$currentCityId" />
        </div>
    </div>
</section>
