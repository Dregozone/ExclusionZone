<section class="w-full">
    <div class="mb-5">
        <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('World Map') }}</flux:heading>
        <flux:text class="mt-1">
            @auth
                {{ __('See where you are, where you can go next, and how far every other city is. Pick a city for details and travel straight from the map.') }}
            @else
                {{ __('Every city in the zone and the routes between them. Pick a city for details.') }}
            @endauth
        </flux:text>
    </div>

    <div class="ez-panel p-2 sm:p-4">
        <x-geo-map :map-data="$this->mapData" />
    </div>
</section>
