<section class="w-full">
    <div class="mb-6">
        <p class="ez-label !text-amber-400">{{ __('Admin console') }}</p>
            <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('Move Player') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Select a player and a destination city, then confirm to teleport them.') }}</flux:text>
    </div>

    <div class="ez-panel p-6">
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
