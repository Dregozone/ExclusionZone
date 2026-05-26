<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Move Player') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Select a player and a destination city, then confirm to teleport them.') }}</flux:text>
    </div>

    <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="movePlayer" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Player') }}</flux:label>
                <flux:select wire:model="selectedUserId" placeholder="{{ __('Select a player…') }}" searchable>
                    <flux:select.option value="">{{ __('Select a player…') }}</flux:select.option>
                    @foreach ($this->users as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedUserId" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Destination City') }}</flux:label>
                <flux:select wire:model="selectedCityId" placeholder="{{ __('Select a city…') }}" searchable>
                    <flux:select.option value="">{{ __('Select a city…') }}</flux:select.option>
                    @foreach ($this->cities as $city)
                        <flux:select.option value="{{ $city->id }}">{{ $city->city }} — {{ $city->country?->country }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedCityId" />
            </flux:field>

            <flux:button type="submit" variant="primary" icon="map-pin">
                {{ __('Confirm Move') }}
            </flux:button>
        </form>
    </div>
</section>
