<x-layouts::app :title="__('City Menu')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        @if ($active_work)
            <div
                x-data="{
                    remainingSeconds: {{ $active_work['remaining_seconds'] }},
                    deadline: Date.parse(@js($active_work['available_at_iso'])),
                    timerId: null,
                    init() {
                        this.tick();
                        this.timerId = window.setInterval(() => this.tick(), 1000);
                    },
                    tick() {
                        this.remainingSeconds = Math.max(0, Math.ceil((this.deadline - Date.now()) / 1000));
                    },
                    formattedRemaining() {
                        const minutes = Math.floor(this.remainingSeconds / 60);
                        const seconds = String(this.remainingSeconds % 60).padStart(2, '0');

                        return `${minutes}:${seconds}`;
                    }
                }"
                x-init="init()"
                class="overflow-hidden rounded-[2rem] border border-amber-300/70 bg-linear-to-br from-amber-50 via-orange-50 to-white p-6 shadow-sm dark:border-amber-500/30 dark:from-amber-500/10 dark:via-zinc-900 dark:to-zinc-900"
            >
                <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <div class="rounded-[1.75rem] border border-dashed border-amber-300/80 bg-white/70 p-6 dark:border-amber-500/30 dark:bg-zinc-900/70">
                        <p class="text-xs uppercase tracking-[0.3em] text-amber-700 dark:text-amber-200">Work image</p>
                        <div class="mt-4 flex min-h-56 items-center justify-center rounded-[1.5rem] bg-zinc-100/80 text-center text-sm text-zinc-500 dark:bg-zinc-800/80 dark:text-zinc-400">
                            Placeholder image area for your work scene.
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-amber-700 dark:text-amber-200">Active timer</p>
                                <h2 class="mt-3 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $active_work['title'] }}</h2>
                                <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ $active_work['description'] }}</p>
                            </div>

                            <div class="rounded-[1.5rem] border border-amber-300/70 bg-white/80 px-5 py-4 text-right dark:border-amber-500/30 dark:bg-zinc-900/80">
                                <p class="text-xs uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Time remaining</p>
                                <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-zinc-100" x-text="formattedRemaining()"></p>
                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    Server unlocks completion {{ $active_work['available_at_human'] }}.
                                </p>
                            </div>
                        </div>

                        @if ($active_work['skill_name'])
                            <div class="rounded-[1.5rem] border border-zinc-200/80 bg-white/80 p-5 dark:border-zinc-700 dark:bg-zinc-900/80">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Training focus</p>
                                        <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $active_work['skill_name'] }}</p>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Level {{ $active_work['skill_level'] }} · {{ $active_work['skill_xp'] }} / {{ $active_work['skill_next_level_xp'] }} XP</p>
                                    </div>

                                    <div class="min-w-44 text-right text-sm text-zinc-600 dark:text-zinc-400">
                                        <p>{{ $active_work['skill_xp_remaining'] }} XP to next level</p>
                                        <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $active_work['duration_seconds'] }}s total duration</p>
                                    </div>
                                </div>

                                <div class="mt-4 h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                                    <div class="h-full rounded-full bg-amber-500 transition-all duration-500" style="width: {{ $active_work['skill_progress_percent'] }}%"></div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-[1.5rem] border border-zinc-200/80 bg-white/80 p-5 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/80 dark:text-zinc-300">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Travel note:</span>
                                This route does not grant skill experience. Cancel before arrival if you want to stay in {{ $active_work['from_city'] }}.
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-3">
                            <form method="POST" action="{{ route('work.complete') }}">
                                @csrf
                                <flux:button type="submit" variant="primary" x-bind:disabled="remainingSeconds > 0">
                                    Complete work
                                </flux:button>
                            </form>

                            <form method="POST" action="{{ route('work.cancel') }}">
                                @csrf
                                <flux:button type="submit" variant="ghost">
                                    Cancel
                                </flux:button>
                            </form>

                            <p class="self-center text-xs text-zinc-500 dark:text-zinc-400">
                                Completion is always verified on the server, even if the local timer is modified.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Current location</p>
                        <h1 class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $city?->city }}</h1>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $country?->country }} · {{ $city?->biome }}</p>
                    </div>

                    <div class="grid gap-2 text-right text-sm text-zinc-600 dark:text-zinc-400">
                        <p>Role: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ Str::headline($player->role_key) }}</span></p>
                        <p>Premium: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $player->hasPremiumEntitlement() ? 'Active' : 'Inactive' }}</span></p>
                        <p>Muted: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $player->isMuted() ? 'Yes' : 'No' }}</span></p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Average temperature</p>
                        <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $country?->avg_temp_c }}°C</p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Rain chance</p>
                        <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $city?->rain_chance_pct }}%</p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Trouble chance</p>
                        <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $city?->trouble_chance_pct }}%</p>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-zinc-200/70 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Scout report:</span> {{ $local_event }}
                </div>
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Travel routes</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">One move is one turn. Each destination refreshes the city action list.</p>

                @if (! $can_perform_city_actions && $city_action_restriction)
                    <p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        {{ $city_action_restriction }}
                    </p>
                @endif

                <div class="mt-4 grid gap-3">
                    @forelse ($neighbors as $neighbor)
                        <form method="POST" action="{{ route('travel.store') }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            @csrf
                            <input type="hidden" name="city_id" value="{{ $neighbor->id }}">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $neighbor->city }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $neighbor->country?->country }} · {{ $neighbor->baseline_loot_tier }} loot</p>
                                </div>

                                <flux:button type="submit" variant="primary" :disabled="! $can_perform_city_actions" data-test="travel-{{ Str::slug($neighbor->city) }}">
                                    {{ $can_perform_city_actions ? 'Begin travel' : 'Unavailable' }}
                                </flux:button>
                            </div>
                        </form>
                    @empty
                        <p class="rounded-2xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            No linked travel routes are available from this city yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">City actions</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Every action grants XP and loot tied to the current city.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4">
                    @foreach ($actions as $entry)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry['action']->label }}</h3>
                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ Str::headline($entry['action']->risk_level) }}</span>
                                    </div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $entry['action']->description }}</p>
                                    <p class="text-xs uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-500">
                                        {{ $entry['action']->skill?->display_name }} · level {{ $entry['required_level'] }} required · reward {{ data_get($entry['action']->reward_profile, 'item_key') }}
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('city-action.store') }}">
                                    @csrf
                                    <input type="hidden" name="city_action_id" value="{{ $entry['action']->id }}">
                                    <flux:button type="submit" variant="primary" :disabled="! $entry['available']" data-test="action-{{ $entry['action']->action_key }}">
                                        {{ $entry['available'] ? 'Begin work' : ($can_perform_city_actions ? 'Locked' : 'Unavailable') }}
                                    </flux:button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6">
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Skill tracker</h2>
                    <div class="mt-4 grid gap-3">
                        @foreach ($skills as $skill)
                            <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $skill->skill?->display_name }}</p>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $skill->skill?->description }}</p>
                                    </div>
                                    <div class="text-right text-sm">
                                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Lv {{ $skill->level }}</p>
                                        <p class="text-zinc-600 dark:text-zinc-400">{{ $skill->xp }} XP</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Inventory</h2>
                    <div class="mt-4 grid gap-3">
                        @forelse ($inventory as $inventoryItem)
                            <div class="flex items-center justify-between rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $inventoryItem->item?->name }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $inventoryItem->item?->description }}</p>
                                </div>
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">x{{ $inventoryItem->quantity }}</span>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                Your pack is empty. Complete a city action to collect loot.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_1fr]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Survivor hooks</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">These entry points keep chat, trading, and combat in the MVP flow while the full systems grow.</p>

                <div class="mt-4 grid gap-3">
                    @foreach ($hooks as $hook)
                        <form method="POST" action="{{ route('feature-hook.store', $hook['route']) }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            @csrf
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $hook['label'] }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $hook['description'] }}</p>
                                </div>

                                <flux:button type="submit" variant="ghost" :disabled="! $hook['available']">
                                    {{ $hook['available'] ? 'Open' : 'Unavailable' }}
                                </flux:button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Premium cosmetics</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Pure style upgrades. No stat boosts, no yield bonuses, no combat modifiers.</p>
                    </div>
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $player->hasPremiumEntitlement() ? 'Unlocked' : 'Locked' }}
                    </span>
                </div>

                <div class="mt-4 grid gap-3">
                    @foreach ($cosmetics as $type => $options)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-sm font-medium uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">{{ Str::headline($type) }}</p>
                            <div class="mt-3 grid gap-3">
                                @foreach ($options as $cosmetic)
                                    <form method="POST" action="{{ route('cosmetics.store') }}" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-800/70">
                                        @csrf
                                        <input type="hidden" name="premium_cosmetic_id" value="{{ $cosmetic->id }}">
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cosmetic->name }}</p>
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Gameplay bonus: {{ $cosmetic->gameplay_bonus }}</p>
                                        </div>

                                        <flux:button type="submit" variant="ghost" :disabled="! $player->hasPremiumEntitlement()">
                                            Equip
                                        </flux:button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($player->isModerator() || $player->isAdmin())
            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Moderation console</h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Apply a temporary mute for abusive radio traffic.</p>

                    <form method="POST" action="{{ route('moderation.mutes.store') }}" class="mt-4 grid gap-4">
                        @csrf
                        <flux:input name="target_user_id" label="Target user ID" type="number" />
                        <flux:input name="duration_minutes" label="Duration (minutes)" type="number" value="60" />
                        <flux:input name="reason" label="Reason" type="text" />

                        <flux:button type="submit" variant="primary">Apply mute</flux:button>
                    </form>
                </div>

                @if ($player->isAdmin())
                    <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Admin controls</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Admins alone can change another user role. Every change is audited.</p>

                        <form method="POST" action="{{ route('admin.roles.update') }}" class="mt-4 grid gap-4">
                            @csrf
                            <flux:input name="target_user_id" label="Target user ID" type="number" />
                            <flux:select name="role_key" label="Role">
                                <option value="user">User</option>
                                <option value="premium">Premium</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </flux:select>

                            <flux:button type="submit" variant="primary">Update role</flux:button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts::app>
