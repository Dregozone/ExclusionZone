@php($selectedUser = $this->selectedUser)
@php($selectedRole = $this->selectedRole)
@php($currentRole = $selectedUser?->role)

<section class="w-full">
    <div class="mb-6">
        <p class="ez-label !text-amber-400">{{ __('Admin console') }}</p>
            <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('Change User Role') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Review the selected user\'s current role and permissions, then assign a new role.') }}</flux:text>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
        <div class="ez-panel p-6">
            <form wire:submit="changeRole" class="space-y-6">
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

                @if ($selectedUser)
                    <div class="ez-card p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedUser->name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $selectedUser->email }}</div>
                            </div>

                            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __('Current role: :role', ['role' => $currentRole?->name ?? __('Unassigned')]) }}
                            </div>
                        </div>
                    </div>
                @endif

                <flux:field>
                    <flux:label>{{ __('New Role') }}</flux:label>
                    <flux:select wire:model="selectedRoleId" placeholder="{{ __('Select a role…') }}" searchable>
                        <flux:select.option value="">{{ __('Select a role…') }}</flux:select.option>
                        @foreach ($this->roles as $role)
                            <flux:select.option value="{{ $role->id }}">{{ $role->name }} — {{ $role->key }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedRoleId" />
                </flux:field>

                <flux:button type="submit" variant="primary" icon="shield-check">
                    {{ __('Apply Role') }}
                </flux:button>
            </form>
        </div>

        <div class="space-y-6">
            <div class="ez-panel p-6">
                <div class="mb-4">
                    <flux:heading size="lg">{{ __('Current Role') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('The selected user\'s active role and task-derived permissions.') }}</flux:text>
                </div>

                @if ($currentRole)
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <div class="inline-flex rounded-sm border border-emerald-400/50 px-3 py-1 font-mono text-sm text-emerald-300">
                            {{ $currentRole->name }}
                        </div>

                        <div class="inline-flex rounded-full border border-zinc-200 px-3 py-1 text-xs uppercase tracking-[0.2em] text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                            {{ $currentRole->key }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        @forelse ($currentRole->tasks->sortBy('description') as $task)
                            <div wire:key="current-task-{{ $task->id }}" class="ez-card px-4 py-3">
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $task->description }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $task->key }}</div>
                            </div>
                        @empty
                            <flux:text>{{ __('This role does not have any permissions assigned yet.') }}</flux:text>
                        @endforelse
                    </div>
                @else
                    <flux:text>{{ __('Choose a user to inspect their current role and permissions.') }}</flux:text>
                @endif
            </div>

            <div class="ez-panel p-6">
                <div class="mb-4">
                    <flux:heading size="lg">{{ __('Selected Role Preview') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('These permissions will apply after you save the change.') }}</flux:text>
                </div>

                @if ($selectedRole)
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <div class="inline-flex rounded-sm border border-emerald-400/50 px-3 py-1 font-mono text-sm text-emerald-300">
                            {{ $selectedRole->name }}
                        </div>

                        <div class="inline-flex rounded-full border border-zinc-200 px-3 py-1 text-xs uppercase tracking-[0.2em] text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                            {{ $selectedRole->key }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        @forelse ($selectedRole->tasks->sortBy('description') as $task)
                            <div wire:key="selected-task-{{ $task->id }}" class="ez-card px-4 py-3">
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $task->description }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $task->key }}</div>
                            </div>
                        @empty
                            <flux:text>{{ __('This role does not have any permissions assigned yet.') }}</flux:text>
                        @endforelse
                    </div>
                @else
                    <flux:text>{{ __('Choose a replacement role to preview its permissions.') }}</flux:text>
                @endif
            </div>
        </div>
    </div>
</section>
