<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Enemies') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create, edit, and soft-delete the enemies survivors fight. City actions using an enemy\'s combat skill in its city start a fight with it.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Enemy') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Enemy') : __('Create Enemy') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('City') }}</flux:label>
                        <flux:select wire:model="city_id" placeholder="{{ __('Select a city…') }}" searchable>
                            <flux:select.option value="">{{ __('Select a city…') }}</flux:select.option>
                            @foreach ($this->cities as $city)
                                <flux:select.option value="{{ $city->id }}">{{ $city->city }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="city_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Combat skill tested') }}</flux:label>
                        <flux:select wire:model="skill_key">
                            @foreach ($this->combatSkills as $skill)
                                <flux:select.option value="{{ $skill->key }}">{{ $skill->display_name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="skill_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Enemy Key') }}</flux:label>
                        <flux:input wire:model="key" placeholder="{{ __('e.g. feral_dog') }}" />
                        <flux:error name="key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('e.g. Feral Dog') }}" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <div class="grid gap-5 sm:grid-cols-4">
                    <flux:field>
                        <flux:label>{{ __('Health') }}</flux:label>
                        <flux:input type="number" wire:model="health" min="1" max="1000" />
                        <flux:error name="health" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Min damage') }}</flux:label>
                        <flux:input type="number" wire:model="min_damage" min="0" max="100" />
                        <flux:error name="min_damage" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Max damage') }}</flux:label>
                        <flux:input type="number" wire:model="max_damage" min="0" max="100" />
                        <flux:error name="max_damage" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('XP reward') }}</flux:label>
                        <flux:input type="number" wire:model="xp_reward" min="0" />
                        <flux:error name="xp_reward" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('What the survivor is up against…') }}" rows="3" />
                    <flux:error name="description" />
                </flux:field>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                    <div class="mb-3 flex items-center justify-between gap-4">
                        <div>
                            <flux:heading size="sm">{{ __('Loot') }}</flux:heading>
                            <flux:text class="mt-1 text-xs">{{ __('Each entry is rolled on its own when the enemy is beaten.') }}</flux:text>
                        </div>
                        <flux:button wire:click="addLoot" variant="ghost" size="sm" icon="plus">{{ __('Add Loot') }}</flux:button>
                    </div>

                    <div class="space-y-3">
                        @forelse ($loot as $index => $entry)
                            <div wire:key="loot-{{ $index }}" class="grid items-start gap-4 sm:grid-cols-[1fr_8rem_8rem_auto]">
                                <flux:field>
                                    <flux:label>{{ __('Item') }}</flux:label>
                                    <flux:select wire:model="loot.{{ $index }}.item_key" placeholder="{{ __('Select an item…') }}" searchable>
                                        <flux:select.option value="">{{ __('Select an item…') }}</flux:select.option>
                                        @foreach ($this->items as $item)
                                            <flux:select.option value="{{ $item->key }}">{{ $item->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="loot.{{ $index }}.item_key" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>{{ __('Chance %') }}</flux:label>
                                    <flux:input type="number" wire:model="loot.{{ $index }}.chance_percent" min="1" max="100" />
                                    <flux:error name="loot.{{ $index }}.chance_percent" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>{{ __('Quantity') }}</flux:label>
                                    <flux:input type="number" wire:model="loot.{{ $index }}.quantity" min="1" />
                                    <flux:error name="loot.{{ $index }}.quantity" />
                                </flux:field>

                                <flux:button wire:click="removeLoot({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-600 sm:mt-7">{{ __('Remove') }}</flux:button>
                            </div>
                        @empty
                            <flux:text class="text-sm">{{ __('Drops nothing.') }}</flux:text>
                        @endforelse
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Enemy') : __('Create Enemy') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted enemies') }}</label>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('City') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Skill') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Health') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('Damage') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-xl:hidden">{{ __('XP') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->enemies as $enemy)
                    <tr wire:key="enemy-{{ $enemy->id }}" class="{{ $enemy->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $enemy->name }}
                            @if ($enemy->deleted_at)
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-md:hidden">{{ $enemy->city?->city }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden">{{ $enemy->skill_key }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $enemy->health }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-md:hidden">{{ $enemy->min_damage }}–{{ $enemy->max_damage }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-xl:hidden">{{ $enemy->xp_reward }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($enemy->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $enemy->id }})" wire:confirm="{{ __('Restore this enemy?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $enemy->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $enemy->id }})" wire:confirm="{{ __('Soft-delete this enemy?') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No enemies found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
