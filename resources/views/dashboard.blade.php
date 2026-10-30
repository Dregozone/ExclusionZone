<x-layouts::app :title="__('City Menu')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if (session('status'))
            <x-session-toast :toast="['text' => session('status')]" />
        @endif

        @if ($last_work_report)
            <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-100" data-test="last-work-report">
                {{ $last_work_report }}
            </div>
        @endif

        @if ($active_work)
            <div
                @if ($active_work['type'] === 'city_action') data-work-loop data-available-at="{{ $active_work['available_at_iso'] }}" @endif
                x-data="{
                    remainingSeconds: {{ $active_work['remaining_seconds'] }},
                    deadline: Date.parse(@js($active_work['available_at_iso'])),
                    timerId: null,
                    isLoop: @js($active_work['type'] === 'city_action'),
                    nextRefreshAt: 0,
                    init() {
                        this.tick();
                        this.timerId = window.setInterval(() => this.tick(), 1000);
                    },
                    tick() {
                        this.remainingSeconds = Math.max(0, Math.ceil((this.deadline - Date.now()) / 1000));

                        if (this.isLoop && this.remainingSeconds === 0 && Date.now() >= this.nextRefreshAt) {
                            this.nextRefreshAt = Date.now() + 5000;
                            this.refreshLoop();
                        }
                    },
                    async refreshLoop() {
                        try {
                            const response = await fetch(window.location.href, { headers: { Accept: 'text/html' } });

                            if (! response.ok) {
                                return;
                            }

                            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                            const loop = page.querySelector('[data-work-loop]');

                            if (loop === null) {
                                window.location.reload();

                                return;
                            }

                            this.deadline = Date.parse(loop.dataset.availableAt);

                            page.querySelectorAll('[data-work-live]').forEach((fresh) => {
                                const current = document.querySelector(`[data-work-live='${fresh.dataset.workLive}']`);

                                if (current !== null) {
                                    current.innerHTML = fresh.innerHTML;
                                }
                            });

                            this.tick();
                        } catch {
                            // Offline for a moment: tick() tries again shortly, and the server keeps settling meanwhile.
                        }
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
                    <img
                        src="{{ $active_work['image_url'] }}"
                        alt=""
                        class="aspect-3/2 w-full self-start rounded-[1.75rem] border border-amber-300/70 object-cover dark:border-amber-500/30"
                        data-test="active-work-image"
                    >

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
                                    @if ($active_work['type'] === 'city_action')
                                        Until the next payout. Repeats automatically.
                                    @else
                                        Server unlocks completion {{ $active_work['available_at_human'] }}.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($active_work['type'] === 'city_action')
                            <div class="rounded-[1.5rem] border border-emerald-300/70 bg-emerald-50/80 p-5 text-sm text-zinc-600 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-zinc-300" data-work-live="loop-progress" data-test="work-loop-progress">
                                <p>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $active_work['cycles_completed'] }} {{ Str::plural('cycle', $active_work['cycles_completed']) }} completed</span>
                                    · +{{ $active_work['xp_earned'] }} XP so far. Keeps running if you leave, and stops {{ $active_work['loop_ends_at_human'] }}.
                                </p>
                                @if ($active_work['last_cycle_summary'])
                                    <p class="mt-2">Last cycle: {{ $active_work['last_cycle_summary'] }}</p>
                                @endif
                            </div>
                        @endif

                        @if ($active_work['weather']->isBad())
                            <div class="rounded-[1.5rem] border border-sky-300/70 bg-sky-50/80 p-5 text-sm text-zinc-600 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-zinc-300" data-test="active-work-weather">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $active_work['weather']->label() }}:</span>
                                {{ match ($active_work['type']) {
                                    'travel' => "This route takes {$active_work['weather']->timerIncreasePercent()}% longer in the {$active_work['weather']->value}.",
                                    'brain_scorcher' => "The Brain Scorcher runs {$active_work['weather']->timerIncreasePercent()}% slower in the {$active_work['weather']->value}.",
                                    default => $active_work['weather']->effectsSummary(),
                                } }}
                            </div>
                        @endif

                        @if ($active_work['skill_name'])
                            <div class="rounded-[1.5rem] border border-zinc-200/80 bg-white/80 p-5 dark:border-zinc-700 dark:bg-zinc-900/80" data-work-live="training-focus">
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
                        @elseif ($active_work['type'] === 'brain_scorcher')
                            <div class="rounded-[1.5rem] border border-fuchsia-300/70 bg-fuchsia-50/80 p-5 text-sm text-zinc-600 dark:border-fuchsia-500/30 dark:bg-fuchsia-500/10 dark:text-zinc-300">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Offering note:</span>
                                The offering burns when you complete the work. It does not grant skill experience.
                            </div>
                        @else
                            <div class="rounded-[1.5rem] border border-zinc-200/80 bg-white/80 p-5 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/80 dark:text-zinc-300">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">Travel note:</span>
                                This route does not grant skill experience. Cancel before arrival if you want to stay in {{ $active_work['from_city'] }}.
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-3">
                            @if ($active_work['type'] !== 'city_action')
                                <form method="POST" action="{{ route('work.complete') }}">
                                    @csrf
                                    <flux:button type="submit" variant="primary" x-bind:disabled="remainingSeconds > 0">
                                        Complete work
                                    </flux:button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('work.cancel') }}">
                                @csrf
                                <flux:button type="submit" variant="ghost" data-test="cancel-work">
                                    {{ $active_work['type'] === 'city_action' ? 'Stop working' : 'Cancel' }}
                                </flux:button>
                            </form>

                            <p class="self-center text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $active_work['type'] === 'city_action' ? 'Stopping pays out finished cycles. The unfinished one earns nothing.' : 'Completion is always verified on the server, even if the local timer is modified.' }}
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
                    @if ($city?->has_trade_post)
                        <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400" data-test="trade-post-badge">
                            <flux:icon.arrows-right-left variant="micro" />
                            Trade post · open the Trade tab on your PDA
                        </p>
                    @endif
                </div>

                <div class="grid gap-2 text-right text-sm text-zinc-600 dark:text-zinc-400">
                    <p>Role: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ Str::headline($player->role_key) }}</span></p>
                    <p>Premium: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $player->hasPremiumEntitlement() ? 'Active' : 'Inactive' }}</span></p>
                    <p>Muted: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $player->isMuted() ? 'Yes' : 'No' }}</span></p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div @class([
                    'rounded-2xl p-4',
                    'bg-zinc-50 dark:bg-zinc-800/70' => ! $weather?->isBad(),
                    'border border-sky-300/70 bg-sky-50 dark:border-sky-500/30 dark:bg-sky-500/10' => $weather?->isBad(),
                ]) data-test="current-weather">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Current weather</p>
                    <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $weather?->label() }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $weather?->effectsSummary() }}</p>
                </div>
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
                                    @if (count($entry['requirements']) > 0)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Requires: <span class="text-amber-600 dark:text-amber-500">{{ collect($entry['requirements'])->map(fn ($requirement) => $requirement['item']?->name.' ×'.$requirement['quantity'])->implode(', ') }}</span></p>
                                    @endif
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
                                @if (count($entry['required_items']) > 0)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400" data-test="required-items-{{ $entry['action']->action_key }}">
                                        Uses:
                                        @foreach ($entry['required_items'] as $requiredItem)
                                            <span @class([
                                                'text-emerald-600 dark:text-emerald-500' => $requiredItem['owned'] >= $requiredItem['quantity'],
                                                'text-amber-600 dark:text-amber-500' => $requiredItem['owned'] < $requiredItem['quantity'],
                                            ])>{{ $requiredItem['item']->name }} ×{{ $requiredItem['quantity'] }} ({{ $requiredItem['owned'] }} carried)</span>@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('city-action.store') }}">
                                @csrf
                                <input type="hidden" name="city_action_id" value="{{ $entry['action']->id }}">
                                <flux:button type="submit" variant="primary" :disabled="! $entry['available']" data-test="action-{{ $entry['action']->action_key }}">
                                    {{ $entry['available'] ? 'Begin work' : (! $can_perform_city_actions ? 'Unavailable' : ($entry['user_level'] < $entry['required_level'] ? 'Locked' : 'Missing items')) }}
                                </flux:button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($brain_scorcher)
            <div
                x-data="{
                    offerings: @js($brain_scorcher['offerings']),
                    itemId: @js($brain_scorcher['offerings'][0]['item_id'] ?? null),
                    quantity: 1,
                    get offering() {
                        return this.offerings.find((offering) => offering.item_id === Number(this.itemId)) ?? null;
                    },
                    get odds() {
                        if (! this.offering) {
                            return null;
                        }

                        const quantities = Object.keys(this.offering.odds_by_quantity).map(Number);
                        const quantity = Math.min(Math.max(1, Number(this.quantity) || 1), Math.max(...quantities));

                        return this.offering.odds_by_quantity[quantity];
                    }
                }"
                class="rounded-3xl border border-fuchsia-300/60 bg-white p-6 dark:border-fuchsia-500/30 dark:bg-zinc-900"
                data-test="brain-scorcher"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-fuchsia-700 dark:text-fuchsia-300">High risk · high reward</p>
                        <h2 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">The Brain Scorcher</h2>
                        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
                            A Soviet psi-emitter still crackles under the cooling towers. Feed it an offering and it may hand back something worse, the same, something better, a Scorched item found nowhere else, or nothing at all. Rarer items and bigger offerings run hotter.
                        </p>
                    </div>
                    <span class="rounded-full bg-fuchsia-100 px-2.5 py-1 text-xs font-medium text-fuchsia-700 dark:bg-fuchsia-500/10 dark:text-fuchsia-300">{{ $brain_scorcher['duration_seconds'] }}s burn</span>
                </div>

                @if (count($brain_scorcher['offerings']) === 0)
                    <p class="mt-5 rounded-2xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        Your pack is empty. Bring something to burn.
                    </p>
                @else
                    <form method="POST" action="{{ route('brain-scorcher.store') }}" class="mt-5 grid gap-5 lg:grid-cols-[1fr_1.2fr]">
                        @csrf

                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Offering</flux:label>
                                <flux:select name="item_id" x-model="itemId" data-test="brain-scorcher-item">
                                    @foreach ($brain_scorcher['offerings'] as $offering)
                                        <flux:select.option value="{{ $offering['item_id'] }}">{{ $offering['name'] }} ({{ $offering['rarity'] }}) · {{ $offering['owned'] }} carried</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>

                            <flux:field>
                                <flux:label>Quantity</flux:label>
                                <flux:input type="number" name="quantity" min="1" x-model="quantity" x-bind:max="offering?.owned" data-test="brain-scorcher-quantity" />
                                <flux:description>More copies raise the odds, up to {{ \App\Actions\Game\FeedBrainScorcher::MAX_EFFECTIVE_QUANTITY }}. You get one item back at most.</flux:description>
                            </flux:field>

                            <flux:button type="submit" variant="primary" :disabled="! $brain_scorcher['available']" data-test="brain-scorcher-submit">
                                {{ $brain_scorcher['available'] ? 'Feed the Brain Scorcher' : 'Unavailable' }}
                            </flux:button>
                        </div>

                        <div class="grid grid-cols-2 content-start gap-2 text-sm sm:grid-cols-5" x-show="odds">
                            @foreach (['nothing' => 'Burned', 'downgrade' => 'Worse', 'same' => 'Same', 'upgrade' => 'Better', 'jackpot' => 'Scorched'] as $outcome => $label)
                                <div @class([
                                    'rounded-2xl p-3',
                                    'bg-zinc-50 dark:bg-zinc-800/70' => $outcome !== 'jackpot',
                                    'border border-fuchsia-300/70 bg-fuchsia-50 dark:border-fuchsia-500/30 dark:bg-fuchsia-500/10' => $outcome === 'jackpot',
                                ])>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                                    <p class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100" x-text="(odds?.{{ $outcome }} ?? 0) + '%'"></p>
                                </div>
                            @endforeach
                        </div>
                    </form>
                @endif
            </div>
        @endif

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
