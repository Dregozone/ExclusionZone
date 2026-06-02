<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Locations') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create, edit, and soft-delete cities with their neighbour connections.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Location') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Location') : __('Create Location') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('City Name') }}</flux:label>
                        <flux:input wire:model="city" placeholder="{{ __('e.g. Kyiv') }}" />
                        <flux:error name="city" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Country') }}</flux:label>
                        <flux:select wire:model="country_id" placeholder="{{ __('Select a country…') }}" searchable>
                            <flux:select.option value="">{{ __('Select a country…') }}</flux:select.option>
                            @foreach ($this->countries as $country)
                                <flux:select.option value="{{ $country->id }}">{{ $country->country }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="country_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Biome') }}</flux:label>
                        <flux:input wire:model="biome" placeholder="{{ __('e.g. irradiated urban ruins') }}" />
                        <flux:error name="biome" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Baseline Loot Tier') }}</flux:label>
                        <flux:select wire:model="baseline_loot_tier">
                            @foreach (['low', 'medium', 'medium-high', 'high', 'rare'] as $tier)
                                <flux:select.option value="{{ $tier }}">{{ ucfirst($tier) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="baseline_loot_tier" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Rain Chance (%)') }}</flux:label>
                        <flux:input type="number" wire:model="rain_chance_pct" min="0" max="100" />
                        <flux:error name="rain_chance_pct" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Trouble Chance (%)') }}</flux:label>
                        <flux:input type="number" wire:model="trouble_chance_pct" min="0" max="100" />
                        <flux:error name="trouble_chance_pct" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Latitude') }}</flux:label>
                        <flux:input type="number" wire:model="lat" step="0.00001" min="-90" max="90" placeholder="-90 to 90" />
                        <flux:error name="lat" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Longitude') }}</flux:label>
                        <flux:input type="number" wire:model="lng" step="0.00001" min="-180" max="180" placeholder="-180 to 180" />
                        <flux:error name="lng" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Neighbour Locations') }}</flux:label>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Select the cities that can be travelled to from this location.') }}</flux:text>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->allCities as $city)
                            @if ($city->id !== $editingId)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2 text-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800 {{ in_array($city->id, $neighborIds) ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-950/30' : '' }}">
                                    <input
                                        type="checkbox"
                                        wire:model="neighborIds"
                                        value="{{ $city->id }}"
                                        class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600"
                                    />
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ $city->city }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <flux:error name="neighborIds" />
                </flux:field>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Location') : __('Create Location') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted locations') }}</label>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('City') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Country') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Biome') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-xl:hidden">{{ __('Loot') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-2xl:hidden">{{ __('Coordinates') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->cities as $location)
                    <tr wire:key="loc-{{ $location->id }}" class="{{ $location->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $location->city }}
                            @if ($location->deleted_at)
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden">{{ $location->country?->country }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden">{{ $location->biome }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-xl:hidden">{{ $location->baseline_loot_tier }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500 dark:text-zinc-500 max-2xl:hidden">
                            @if ($location->lat !== null && $location->lng !== null)
                                {{ number_format($location->lat, 5) }}, {{ number_format($location->lng, 5) }}
                            @else
                                <span class="italic text-zinc-400 dark:text-zinc-600">not set</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($location->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $location->id }})" wire:confirm="{{ __('Restore this location?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $location->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $location->id }})" wire:confirm="{{ __('Soft-delete this location?') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No locations found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
