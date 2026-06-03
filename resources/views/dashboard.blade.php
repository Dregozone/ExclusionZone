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

        @if ($quest_step_actions->isNotEmpty())
            <div class="rounded-3xl border border-emerald-200/60 bg-white p-6 dark:border-emerald-800/40 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Field contacts</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Active quest objectives available at this location.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3">
                    @foreach ($quest_step_actions as $entry)
                        <div class="rounded-2xl border border-emerald-200/60 bg-emerald-50/50 p-4 dark:border-emerald-800/30 dark:bg-emerald-900/10">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-500">{{ $entry['quest_name'] }}</p>
                                    <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry['step']->person_of_interest }}</h3>
                                </div>
                                <form method="POST" action="{{ route('quest-step.interact', $entry['step']) }}">
                                    @csrf
                                    <flux:button type="submit" variant="primary" size="sm">
                                        {{ $entry['step']->action_label }}
                                    </flux:button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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

        <x-pda
            :neighbors="$neighbors"
            :skills="$skills"
            :inventory="$inventory"
            :cosmetics="$cosmetics"
            :player="$player"
            :city="$city"
            :country="$country"
            :mapData="$map_data"
            :currentCityId="$current_city_id"
            :canPerformCityActions="$can_perform_city_actions"
            :cityActionRestriction="$city_action_restriction"
            :jobs="$jobs"
        />
    </div>
</x-layouts::app>
