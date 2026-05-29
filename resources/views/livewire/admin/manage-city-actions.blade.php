<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage City Actions') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create, edit, and soft-delete actions available in each city.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Action') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Action') : __('Create Action') }}</flux:heading>
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
                        <flux:label>{{ __('Skill') }}</flux:label>
                        <flux:select wire:model="skill_key" placeholder="{{ __('Select a skill…') }}" searchable>
                            <flux:select.option value="">{{ __('Select a skill…') }}</flux:select.option>
                            @foreach ($this->skills as $skill)
                                <flux:select.option value="{{ $skill->key }}">{{ $skill->display_name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="skill_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Action Key') }}</flux:label>
                        <flux:input wire:model="action_key" placeholder="{{ __('e.g. scavenge_reactor_zone') }}" />
                        <flux:error name="action_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Label') }}</flux:label>
                        <flux:input wire:model="label" placeholder="{{ __('e.g. Scavenge reactor zone') }}" />
                        <flux:error name="label" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Min Level') }}</flux:label>
                        <flux:input type="number" wire:model="min_level" min="1" max="100" />
                        <flux:error name="min_level" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Risk Level') }}</flux:label>
                        <flux:select wire:model="risk_level">
                            @foreach (['low', 'medium', 'high', 'extreme'] as $risk)
                                <flux:select.option value="{{ $risk }}">{{ ucfirst($risk) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="risk_level" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('Flavour description of the action…') }}" rows="3" />
                    <flux:error name="description" />
                </flux:field>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                    <flux:heading size="sm" class="mb-3">{{ __('Reward Profile') }}</flux:heading>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <flux:field>
                            <flux:label>{{ __('XP') }}</flux:label>
                            <flux:input type="number" wire:model="reward_xp" min="1" />
                            <flux:error name="reward_xp" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Item Key') }}</flux:label>
                            <flux:input wire:model="reward_item_key" placeholder="{{ __('e.g. scrap_metal') }}" />
                            <flux:error name="reward_item_key" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Quantity') }}</flux:label>
                            <flux:input type="number" wire:model="reward_quantity" min="1" />
                            <flux:error name="reward_quantity" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Loot Tier') }}</flux:label>
                            <flux:select wire:model="reward_loot_tier">
                                @foreach (['low', 'medium', 'medium-high', 'high', 'rare'] as $tier)
                                    <flux:select.option value="{{ $tier }}">{{ ucfirst($tier) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="reward_loot_tier" />
                        </flux:field>
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Action') : __('Create Action') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted actions') }}</label>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Label') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('City') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Skill') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-xl:hidden">{{ __('Risk') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->actions as $action)
                    <tr wire:key="action-{{ $action->id }}" class="{{ $action->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $action->label }}
                            @if ($action->deleted_at)
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-md:hidden">{{ $action->city?->city }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden">{{ $action->skill_key }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-xl:hidden">{{ $action->risk_level }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($action->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $action->id }})" wire:confirm="{{ __('Restore this action?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $action->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $action->id }})" wire:confirm="{{ __('Soft-delete this action?') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No actions found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
