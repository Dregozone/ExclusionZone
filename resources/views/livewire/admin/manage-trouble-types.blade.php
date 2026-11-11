<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Trouble') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Who can block a survivor arriving in a city, how hard they fight, and what they demand to let them through.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Trouble') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Trouble') : __('Create Trouble') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Trouble Key') }}</flux:label>
                        <flux:input wire:model="key" placeholder="{{ __('e.g. barricade_looters') }}" />
                        <flux:error name="key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Who causes it') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('e.g. Barricade looters') }}" />
                        <flux:description>{{ __('Shown as "… block the way in", so name them in the plural.') }}</flux:description>
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('What the survivor sees as they arrive…') }}" rows="3" />
                    <flux:error name="description" />
                </flux:field>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                    <flux:heading size="sm">{{ __('Fight') }}</flux:heading>
                    <flux:text class="mt-1 text-xs">{{ __('The survivor’s level in the tested skill against the fighter level, and their health, decide the chance to win. Damage is rolled between the minimum and maximum.') }}</flux:text>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <flux:field>
                            <flux:label>{{ __('Skill tested') }}</flux:label>
                            <flux:select wire:model="skill_key">
                                @foreach (\App\Livewire\Admin\ManageTroubleTypes::COMBAT_SKILL_KEYS as $skillKey)
                                    <flux:select.option value="{{ $skillKey }}">{{ Str::headline($skillKey) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="skill_key" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Fighter level') }}</flux:label>
                            <flux:input type="number" wire:model="fighter_level" min="1" max="100" />
                            <flux:error name="fighter_level" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Damage min') }}</flux:label>
                            <flux:input type="number" wire:model="damage_min" min="0" max="100" />
                            <flux:error name="damage_min" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Damage max') }}</flux:label>
                            <flux:input type="number" wire:model="damage_max" min="0" max="100" />
                            <flux:error name="damage_max" />
                        </flux:field>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <flux:field>
                        <flux:label>{{ __('XP for winning') }}</flux:label>
                        <flux:input type="number" wire:model="xp_reward" min="0" />
                        <flux:error name="xp_reward" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Loot item') }}</flux:label>
                        <flux:select wire:model="loot_item_id">
                            <flux:select.option value="">{{ __('None') }}</flux:select.option>
                            @foreach ($this->items as $item)
                                <flux:select.option value="{{ $item->id }}">{{ $item->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="loot_item_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Loot quantity') }}</flux:label>
                        <flux:input type="number" wire:model="loot_quantity" min="0" />
                        <flux:error name="loot_quantity" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Demand') }}</flux:label>
                        <flux:input type="number" wire:model="demand_value" min="1" />
                        <flux:description>{{ __('Goods value to buy a way in. Each item is worth its rarity: Common 1 up to Scorched 5.') }}</flux:description>
                        <flux:error name="demand_value" />
                    </flux:field>
                </div>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Trouble') : __('Create Trouble') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted trouble') }}</label>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Who') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Fight') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('Reward') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Demand') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->troubleTypes as $troubleType)
                    <tr wire:key="trouble-type-{{ $troubleType->id }}" class="{{ $troubleType->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $troubleType->name }}
                            @if ($troubleType->deleted_at)
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                            @endif
                            <p class="font-mono text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ $troubleType->key }}</p>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ Str::headline($troubleType->skill_key) }} · {{ __('level') }} {{ $troubleType->fighter_level }}
                            <p class="text-xs">{{ __('Damage') }} {{ $troubleType->damage_min }}–{{ $troubleType->damage_max }}</p>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-md:hidden">
                            {{ $troubleType->xp_reward }} XP
                            @if ($troubleType->lootItem && $troubleType->loot_quantity > 0)
                                · {{ $troubleType->loot_quantity }}× {{ $troubleType->lootItem->name }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $troubleType->demand_value }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($troubleType->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $troubleType->id }})" wire:confirm="{{ __('Restore this trouble?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $troubleType->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $troubleType->id }})" wire:confirm="{{ __('Soft-delete this trouble? Survivors already facing it still have to deal with it.') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No trouble found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
