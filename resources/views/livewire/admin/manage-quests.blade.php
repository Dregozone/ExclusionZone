<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Quests') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create and edit quests with multi-step objectives and configurable rewards.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Quest') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Quest') : __('Create Quest') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-6">

                {{-- Basic info --}}
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field class="sm:col-span-2">
                        <flux:label>{{ __('Quest Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('e.g. Help a local') }}" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field class="sm:col-span-2">
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="description" placeholder="{{ __('Overview shown on the job board…') }}" rows="3" />
                        <flux:error name="description" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Quest Type') }}</flux:label>
                        <flux:select wire:model.live="quest_type">
                            <flux:select.option value="job">{{ __('Job') }}</flux:select.option>
                            <flux:select.option value="story">{{ __('Story') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="quest_type" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Active') }}</flux:label>
                        <flux:select wire:model="is_active">
                            <flux:select.option value="1">{{ __('Yes — visible on job board') }}</flux:select.option>
                            <flux:select.option value="0">{{ __('No — hidden from players') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="is_active" />
                    </flux:field>

                    @if ($quest_type === 'story')
                        <flux:field>
                            <flux:label>{{ __('Sequence Order') }}</flux:label>
                            <flux:input type="number" wire:model="sequence_order" min="1" placeholder="{{ __('e.g. 1') }}" />
                            <flux:error name="sequence_order" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Prerequisite Quest') }}</flux:label>
                            <flux:select wire:model="prerequisite_quest_id">
                                <flux:select.option value="">{{ __('None (first quest)') }}</flux:select.option>
                                @foreach ($this->storyQuests as $storyQuest)
                                    @if ($storyQuest->id !== $editingId)
                                        <flux:select.option value="{{ $storyQuest->id }}">{{ $storyQuest->name }}</flux:select.option>
                                    @endif
                                @endforeach
                            </flux:select>
                            <flux:error name="prerequisite_quest_id" />
                        </flux:field>
                    @endif

                    @if ($quest_type === 'job')
                        <flux:field>
                            <flux:label>{{ __('Repeatable') }}</flux:label>
                            <flux:select wire:model="is_repeatable">
                                <flux:select.option value="0">{{ __('No — one-time job') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('Yes — can be replayed') }}</flux:select.option>
                            </flux:select>
                            <flux:error name="is_repeatable" />
                        </flux:field>
                    @endif
                </div>

                {{-- Reward profile --}}
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                    <flux:heading size="sm" class="mb-3">{{ __('Reward') }}</flux:heading>
                    <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Leave skill or item blank for no reward of that type.') }}</p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <flux:field>
                            <flux:label>{{ __('Reward Skill') }}</flux:label>
                            <flux:select wire:model="reward_skill_id">
                                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                                @foreach ($this->skills as $skill)
                                    <flux:select.option value="{{ $skill->id }}">{{ $skill->display_name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="reward_skill_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('XP Amount') }}</flux:label>
                            <flux:input type="number" wire:model="reward_xp_amount" min="1" placeholder="{{ __('e.g. 1000') }}" />
                            <flux:error name="reward_xp_amount" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Reward Item') }}</flux:label>
                            <flux:select wire:model="reward_item_id">
                                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                                @foreach ($this->items as $item)
                                    <flux:select.option value="{{ $item->id }}">{{ $item->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="reward_item_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Item Quantity') }}</flux:label>
                            <flux:input type="number" wire:model="reward_item_quantity" min="1" />
                            <flux:error name="reward_item_quantity" />
                        </flux:field>
                    </div>
                </div>

                {{-- Steps --}}
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm">{{ __('Quest Steps') }}</flux:heading>
                        <flux:button wire:click="addStep" variant="ghost" size="sm" icon="plus">{{ __('Add Step') }}</flux:button>
                    </div>

                    @if (empty($steps))
                        <p class="rounded-xl border border-dashed border-zinc-300 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            {{ __('No steps yet. Add at least one step to define the quest objectives.') }}
                        </p>
                    @endif

                    <div class="space-y-4">
                        @foreach ($steps as $index => $step)
                            <div wire:key="step-{{ $index }}" class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="font-mono text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Step') }} {{ $index + 1 }}</span>
                                    <flux:button wire:click="removeStep({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-600">{{ __('Remove') }}</flux:button>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label>{{ __('City') }}</flux:label>
                                        <flux:select wire:model="steps.{{ $index }}.city_id" searchable>
                                            <flux:select.option value="">{{ __('Select a city…') }}</flux:select.option>
                                            @foreach ($this->cities as $city)
                                                <flux:select.option value="{{ $city->id }}">{{ $city->city }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error name="steps.{{ $index }}.city_id" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Person of Interest') }}</flux:label>
                                        <flux:input wire:model="steps.{{ $index }}.person_of_interest" placeholder="{{ __('e.g. Local Man') }}" />
                                        <flux:error name="steps.{{ $index }}.person_of_interest" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('Action Label') }}</flux:label>
                                        <flux:input wire:model="steps.{{ $index }}.action_label" placeholder="{{ __('e.g. Speak to local man') }}" />
                                        <flux:error name="steps.{{ $index }}.action_label" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('Interaction Text') }}</flux:label>
                                        <flux:textarea wire:model="steps.{{ $index }}.interaction_text" placeholder="{{ __('Dialogue shown to the player on interaction…') }}" rows="3" />
                                        <flux:error name="steps.{{ $index }}.interaction_text" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Required Item') }}</flux:label>
                                        <flux:select wire:model="steps.{{ $index }}.required_item_id">
                                            <flux:select.option value="">{{ __('None') }}</flux:select.option>
                                            @foreach ($this->items as $item)
                                                <flux:select.option value="{{ $item->id }}">{{ $item->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error name="steps.{{ $index }}.required_item_id" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Required Quantity') }}</flux:label>
                                        <flux:input type="number" wire:model="steps.{{ $index }}.required_item_quantity" min="1" />
                                        <flux:error name="steps.{{ $index }}.required_item_quantity" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Consumes Item') }}</flux:label>
                                        <flux:select wire:model="steps.{{ $index }}.consumes_item">
                                            <flux:select.option value="0">{{ __('No') }}</flux:select.option>
                                            <flux:select.option value="1">{{ __('Yes — remove from inventory') }}</flux:select.option>
                                        </flux:select>
                                        <flux:error name="steps.{{ $index }}.consumes_item" />
                                    </flux:field>

                                    @if ($is_repeatable)
                                        <flux:field class="sm:col-span-2">
                                            <flux:label>{{ __('Requirement Variants (JSON)') }}</flux:label>
                                            <flux:textarea wire:model="steps.{{ $index }}.requirement_variants_json" rows="4"
                                                placeholder='[{"required_item_id": 1, "required_item_quantity": 2}, {"required_item_id": 5, "required_item_quantity": 1}]' />
                                            <flux:description>{{ __('JSON array of item/quantity options. One is randomly selected per run. Leave blank to use the fixed Required Item above.') }}</flux:description>
                                            <flux:error name="steps.{{ $index }}.requirement_variants_json" />
                                        </flux:field>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Quest') : __('Create Quest') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted quests') }}</label>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('Steps') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->quests as $quest)
                    <tr wire:key="quest-{{ $quest->id }}" class="{{ $quest->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $quest->name }}
                                @if ($quest->deleted_at)
                                    <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                                @endif
                            </p>
                            <p class="mt-0.5 max-w-xs truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $quest->description }}</p>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-md:hidden">{{ $quest->steps_count }}</td>
                        <td class="px-4 py-3 max-lg:hidden">
                            @if ($quest->quest_type === 'story')
                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    {{ __('Story') }} #{{ $quest->sequence_order }}
                                </span>
                            @elseif ($quest->is_repeatable)
                                <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">{{ __('Recurring') }}</span>
                            @else
                                <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">{{ __('Job') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-lg:hidden">
                            @if ($quest->is_active)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Active') }}</span>
                            @else
                                <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($quest->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $quest->id }})" wire:confirm="{{ __('Restore this quest?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $quest->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $quest->id }})" wire:confirm="{{ __('Soft-delete this quest?') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No quests found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
