<section class="w-full">
    <div class="mb-6">
        <p class="ez-label !text-amber-400">{{ __('Admin console') }}</p>
            <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('Give Item') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Select a player and an item, enter a quantity, then confirm to add it to their inventory.') }}</flux:text>
    </div>

    <div class="ez-panel p-6">
        <form wire:submit="giveItem" class="space-y-6">
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
                <flux:label>{{ __('Item') }}</flux:label>
                <flux:select wire:model="selectedItemId" placeholder="{{ __('Select an item…') }}" searchable>
                    <flux:select.option value="">{{ __('Select an item…') }}</flux:select.option>
                    @foreach ($this->items as $item)
                        <flux:select.option value="{{ $item->id }}">{{ $item->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedItemId" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Quantity') }}</flux:label>
                <flux:input type="number" wire:model="quantity" min="1" max="10000" step="1" />
                <flux:error name="quantity" />
            </flux:field>

            <flux:button type="submit" variant="primary" icon="gift">
                {{ __('Give Item') }}
            </flux:button>
        </form>
    </div>
</section>
