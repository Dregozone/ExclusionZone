<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="ez-label !text-amber-400">{{ __('Admin console') }}</p>
            <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('Manage Items') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create, edit, and soft-delete loot items.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Item') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="ez-panel mb-6 p-6">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Item') : __('Create Item') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Item Key') }}</flux:label>
                        <flux:input wire:model="key" placeholder="{{ __('e.g. scrap_metal') }}" />
                        <flux:error name="key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('e.g. Scrap Metal') }}" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Rarity') }}</flux:label>
                    <flux:select wire:model="rarity">
                        @foreach (\App\ItemRarity::cases() as $rarityOption)
                            <flux:select.option value="{{ $rarityOption->value }}">{{ $rarityOption->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>{{ __('Decides what counts as better or worse at the Brain Scorcher. Scorched items are only handed out by the Brain Scorcher.') }}</flux:description>
                    <flux:error name="rarity" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:checkbox wire:model="is_quest_item" />
                    <flux:label>{{ __('Quest item') }}</flux:label>
                    <flux:description>{{ __('Quest items stay with the survivor who earned them and cannot be traded.') }}</flux:description>
                    <flux:error name="is_quest_item" />
                </flux:field>

                <div class="ez-card p-4">
                    <flux:heading size="sm">{{ __('Food') }}</flux:heading>
                    <flux:text class="mt-1 text-xs">{{ __('How much eating one eases each need (0–100). An item that eases hunger or fatigue can be eaten from the dashboard.') }}</flux:text>
                    <div class="mt-3 grid gap-4 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>{{ __('Hunger restored') }}</flux:label>
                            <flux:input type="number" wire:model="hunger_restored" min="0" max="100" />
                            <flux:error name="hunger_restored" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Fatigue restored') }}</flux:label>
                            <flux:input type="number" wire:model="fatigue_restored" min="0" max="100" />
                            <flux:error name="fatigue_restored" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Health restored') }}</flux:label>
                            <flux:input type="number" wire:model="health_restored" min="0" max="100" />
                            <flux:description>{{ __('Health given back when eaten or used.') }}</flux:description>
                            <flux:error name="health_restored" />
                        </flux:field>
                    </div>

                    <flux:field variant="inline" class="mt-4">
                        <flux:checkbox wire:model="is_medical" />
                        <flux:label>{{ __('Medical item') }}</flux:label>
                        <flux:description>{{ __('Used from the dashboard to restore its health instead of eaten, like bandages or a medkit.') }}</flux:description>
                        <flux:error name="is_medical" />
                    </flux:field>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Attack bonus') }}</flux:label>
                        <flux:input type="number" wire:model="attack_bonus" min="0" max="100" />
                        <flux:description>{{ __('Added to every blow in a fight. Survivors fight with the held item (worn in an arm) with the highest bonus; 0 means it is not a weapon.') }}</flux:description>
                        <flux:error name="attack_bonus" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Defence bonus') }}</flux:label>
                        <flux:input type="number" wire:model="defence_bonus" min="0" max="100" />
                        <flux:description>{{ __('Taken off every enemy\'s chance to land a blow, in percent. Only worn armour counts, and the worn item with the highest bonus is used; 0 means it is not armour.') }}</flux:description>
                        <flux:error name="defence_bonus" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Base price (scrip)') }}</flux:label>
                    <flux:input type="number" wire:model="base_price" min="0" />
                    <flux:description>{{ __('What trade post bazaars buy and sell this item around. 0 means bazaars will not take it; quest items are never bought.') }}</flux:description>
                    <flux:error name="base_price" />
                </flux:field>

                <div class="space-y-4 rounded-sm border border-zinc-700 p-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Worn on') }}</flux:label>
                            <flux:select wire:model="equip_slot">
                                <flux:select.option value="">{{ __('Not wearable') }}</flux:select.option>
                                @foreach (\App\EquipSlot::ITEM_SLOTS as $slotKey => $slotLabel)
                                    <flux:select.option value="{{ $slotKey }}">{{ $slotLabel }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="equip_slot" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Speeds up skill') }}</flux:label>
                            <flux:select wire:model="bonus_skill_key">
                                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                                @foreach ($this->skills as $skillKey => $skillName)
                                    <flux:select.option value="{{ $skillKey }}">{{ $skillName }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="bonus_skill_key" />
                        </flux:field>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ([
                            'work_speed_bonus_percent' => [__('Work speed %'), __('How much faster that skill\'s work runs.')],
                            'hunger_reduction_percent' => [__('Hunger eased %'), __('Less hunger from work and travel.')],
                            'fatigue_reduction_percent' => [__('Fatigue eased %'), __('Less fatigue from work and travel.')],
                            'healing_bonus_percent' => [__('Healing bonus %'), __('More health from food, medicine and rest.')],
                            'radiation_protection_percent' => [__('Radiation protection %'), __('Less health lost to radiation.')],
                        ] as $field => [$fieldLabel, $fieldDescription])
                            <flux:field>
                                <flux:label>{{ $fieldLabel }}</flux:label>
                                <flux:input type="number" wire:model="{{ $field }}" min="0" max="100" />
                                <flux:description>{{ $fieldDescription }}</flux:description>
                                <flux:error name="{{ $field }}" />
                            </flux:field>
                        @endforeach
                    </div>
                    <p class="text-xs text-zinc-500">{{ __('Bonuses only apply while the item is worn, and add up across worn items up to the caps in config/equipment.php.') }}</p>
                </div>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('What this item is and how it might be used…') }}" rows="3" />
                    <flux:error name="description" />
                </flux:field>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Item') : __('Create Item') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted items') }}</label>
    </div>

    <div class="ez-panel overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('Key') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Rarity') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->items as $item)
                    <tr wire:key="item-{{ $item->id }}" class="{{ $item->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $item->name }}
                            @if ($item->deleted_at)
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500 dark:text-zinc-400 max-md:hidden">{{ $item->key }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $item->rarity->label() }}
                            @if ($item->is_quest_item)
                                <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Quest') }}</span>
                            @endif
                            @if ($item->isFood())
                                <span class="ml-2 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Food') }}</span>
                            @endif
                            @if ($item->isWeapon())
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Weapon +:bonus', ['bonus' => $item->attack_bonus]) }}</span>
                            @endif
                            @if ($item->isArmour())
                                <span class="ml-2 inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">{{ __('Armour +:bonus', ['bonus' => $item->defence_bonus]) }}</span>
                            @endif
                            @if ($item->isMedical())
                                <span class="ml-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">{{ __('Medical') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden truncate max-w-xs">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($item->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $item->id }})" wire:confirm="{{ __('Restore this item?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $item->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Soft-delete this item?') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No items found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
