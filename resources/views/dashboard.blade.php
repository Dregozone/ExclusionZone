<x-layouts::app :title="__('City Menu')">
    @php
        $healthTone = fn (array $state): string => match (true) {
            $state['timer_increase_percent'] >= 50 => 'danger',
            $state['timer_increase_percent'] > 0 => 'warn',
            default => 'ok',
        };
        $needTone = fn (array $state): string => match (true) {
            $state['value'] >= $state['maximum'] => 'danger',
            $state['timer_increase_percent'] > 0 => 'warn',
            default => 'ok',
        };
        $coordinates = $city && $city->lat !== null && $city->lng !== null
            ? sprintf('%.4f° %s · %.4f° %s', abs($city->lat), $city->lat >= 0 ? 'N' : 'S', abs($city->lng), $city->lng >= 0 ? 'E' : 'W')
            : null;
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 pb-28">
        @if (session('status'))
            <x-session-toast :toast="['text' => session('status')]" />
        @endif

        {{-- Survivor HUD: always-visible condition strip --}}
        <div class="ez-panel sticky top-2 z-20 px-4 py-3" data-work-live="hud" data-test="survivor-hud">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="relative flex size-2.5 shrink-0 text-emerald-400" aria-hidden="true">
                        <span class="ez-ping absolute inset-0 rounded-full"></span>
                        <span class="relative size-2.5 rounded-full bg-current"></span>
                    </span>
                    <p class="truncate text-sm">
                        <span class="font-medium text-ez-ink">{{ $player->name }}</span>
                        <span class="text-ez-muted">in</span>
                        <span class="ez-display text-lg text-ez-ink">{{ $city?->city }}</span>
                    </p>
                </div>

                <div class="grid basis-full grid-cols-3 gap-3 sm:basis-auto sm:flex-1 sm:gap-4 sm:min-w-[22rem]">
                    <x-zone.meter compact :label="__('Health')" :value="$condition['health']['value']" :maximum="$condition['health']['maximum']" :tone="$healthTone($condition['health'])" :reading="$condition['health']['value']" />
                    <x-zone.meter compact :label="__('Hunger')" :value="$condition['hunger']['value']" :maximum="$condition['hunger']['maximum']" :tone="$needTone($condition['hunger'])" :reading="$condition['hunger']['value']" />
                    <x-zone.meter compact :label="__('Fatigue')" :value="$condition['fatigue']['value']" :maximum="$condition['fatigue']['maximum']" :tone="$needTone($condition['fatigue'])" :reading="$condition['fatigue']['value']" />
                </div>

                <span @class([
                    'rounded-sm border px-2 py-1 font-mono text-[11px] whitespace-nowrap',
                    'border-zinc-700 text-ez-muted' => $condition['timer_increase_percent'] === 0,
                    'border-amber-400/60 text-amber-400' => $condition['timer_increase_percent'] > 0,
                ])>
                    {{ $condition['timer_increase_percent'] === 0 ? 'No drag on timers' : "▲ Timers +{$condition['timer_increase_percent']}%" }}
                </span>
            </div>
        </div>

        @if ($death_report)
            {{-- Journal page: what happened, in your own notebook --}}
            <div class="ez-paper mx-auto mt-3 w-full max-w-3xl -rotate-[0.4deg] p-6 sm:p-8" role="alert" data-test="death-report">
                <span class="ez-stamp absolute top-5 right-5 text-xs sm:text-sm" aria-hidden="true">Pulled back</span>
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.25em] text-ez-stamp">You died</p>
                <h2 class="ez-display mt-3 max-w-[18ch] text-4xl text-ez-paper-ink sm:text-5xl">Someone pulled you back</h2>
                <p class="mt-4 max-w-2xl font-serif text-[15px] leading-6 text-ez-paper-ink">{{ $death_report }}</p>
                <form method="POST" action="{{ route('death-report.dismiss') }}" class="mt-6">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-sm bg-ez-paper-ink px-5 py-2.5 font-mono text-sm text-ez-paper transition hover:bg-black" data-test="dismiss-death-report">I'll be more careful</button>
                </form>
            </div>
        @endif

        @if ($trouble)
            {{-- Encounter takeover: hazard bar, heat bloom, three choices --}}
            <section class="ez-panel overflow-hidden !border-red-500/50" role="alert" data-test="trouble-panel">
                <div class="ez-hazard h-2" aria-hidden="true"></div>
                <div class="relative p-5 sm:p-7">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_80%_0%,rgb(255_107_74/0.22),transparent_55%)]" aria-hidden="true"></div>
                    <div class="relative">
                        <p class="flex items-center gap-2 font-mono text-xs font-semibold uppercase tracking-[0.22em] text-red-400">
                            <flux:icon.exclamation-triangle variant="micro" />
                            Trouble at the edge of {{ $city?->city }}
                        </p>
                        <h2 class="ez-display mt-3 max-w-[20ch] text-4xl text-ez-ink sm:text-6xl">{{ $trouble['name'] }} block the way in</h2>
                        <p class="mt-4 max-w-3xl text-sm leading-6 text-ez-muted">{{ $trouble['description'] }} Nothing else can be done here until you deal with them.</p>
                        @if ($trouble['route_stops'] !== [])
                            <p class="mt-2 text-sm text-ez-muted" data-test="trouble-route">
                                Get past them and your route carries on to {{ implode(' → ', $trouble['route_stops']) }}.
                            </p>
                        @endif
                    </div>

                    <div class="relative mt-6 grid gap-4 lg:grid-cols-3">
                        <div class="ez-card ez-heat flex min-w-0 flex-col p-4">
                            <h3 class="ez-display text-2xl text-ez-ink">Attack</h3>
                            <p class="mt-1 font-numeral text-5xl font-light text-ez-ink tabular-nums" aria-hidden="true">{{ $trouble['win_chance_percent'] }}<span class="ml-1 font-mono text-xs tracking-widest text-ez-muted">% TO WIN</span></p>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-800" aria-hidden="true">
                                <div class="h-full bg-linear-to-r from-red-500 via-amber-400 to-emerald-400" style="width: {{ $trouble['win_chance_percent'] }}%"></div>
                            </div>
                            <p class="mt-3 flex-1 text-sm text-ez-muted">
                                Tests your {{ $trouble['skill_name'] }} against fighters of level {{ $trouble['fighter_level'] }}.
                                You take {{ $trouble['damage_min'] }}–{{ $trouble['damage_max'] }} damage if you lose, and {{ config('trouble.fight.damage_taken_on_win_percent') }}% of that if you win. Winning clears the way and pays XP and loot.
                            </p>
                            <p class="mt-3 text-sm font-medium text-ez-ink" data-test="trouble-win-chance">{{ $trouble['win_chance_percent'] }}% chance to win</p>
                            <form method="POST" action="{{ route('trouble.attack') }}" class="mt-3">
                                @csrf
                                <flux:button type="submit" variant="danger" class="w-full" :disabled="! $trouble['can_attack']" data-test="trouble-attack">Attack</flux:button>
                            </form>
                        </div>

                        <div
                            x-data="{ quantities: {}, demand: @js($trouble['demand_value']), values: @js(collect($trouble['barter_goods'])->mapWithKeys(fn ($goods) => [$goods['item_id'] => $goods['value']])), get offered() { return Object.entries(this.quantities).reduce((total, [itemId, quantity]) => total + (this.values[itemId] ?? 0) * Math.max(0, Number(quantity) || 0), 0); } }"
                            class="ez-card flex min-w-0 flex-col p-4"
                        >
                            <h3 class="ez-display text-2xl text-ez-ink">Buy your way in</h3>
                            <p class="mt-2 text-sm text-ez-muted">
                                They want goods worth {{ $trouble['demand_value'] }}. Each item is worth its rarity: Common 1, Uncommon 2, Rare 3, Epic 4, Scorched 5.
                            </p>
                            @if ($trouble['can_buy_in'])
                                <form method="POST" action="{{ route('trouble.buy-in') }}" class="mt-3 flex flex-1 flex-col gap-3">
                                    @csrf
                                    <div class="max-h-56 space-y-1.5 overflow-y-auto pr-1">
                                        @foreach ($trouble['barter_goods'] as $goods)
                                            <label class="flex items-center justify-between gap-3 border-b border-dashed border-zinc-700 pb-1.5 text-sm">
                                                <span class="min-w-0 truncate text-ez-ink">{{ $goods['name'] }} <span class="text-ez-muted">· {{ $goods['rarity'] }} · ×{{ $goods['owned'] }}</span></span>
                                                <input type="number" name="items[{{ $goods['item_id'] }}]" min="0" max="{{ $goods['owned'] }}" value="0" x-model="quantities[{{ $goods['item_id'] }}]" class="w-16 shrink-0 rounded-sm border border-zinc-600 bg-zinc-950 px-2 py-1 text-right font-mono text-sm text-ez-ink" data-test="trouble-offer-{{ $goods['item_id'] }}">
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="font-mono text-sm text-ez-muted">Offer worth <span class="font-semibold text-ez-ink" x-text="offered">0</span> of {{ $trouble['demand_value'] }}</p>
                                    <flux:button type="submit" variant="primary" class="w-full" x-bind:disabled="offered < demand" data-test="trouble-buy-in">Hand it over</flux:button>
                                </form>
                            @else
                                <p class="mt-3 rounded-sm border border-dashed border-zinc-600 p-3 text-sm text-ez-muted" data-test="trouble-cannot-buy-in">
                                    You don't carry enough to pay them off.
                                </p>
                            @endif
                        </div>

                        <div class="ez-card flex min-w-0 flex-col p-4">
                            <h3 class="ez-display text-2xl text-ez-ink">Run away</h3>
                            <p class="mt-2 flex-1 text-sm text-ez-muted">
                                @if ($trouble['origin_city'])
                                    Head back to {{ $trouble['origin_city'] }} on the usual travel timer. Once you run, you cannot turn back, and the rest of your route is dropped.
                                @else
                                    There is nowhere to run back to.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('trouble.run') }}" class="mt-3">
                                @csrf
                                <flux:button type="submit" variant="ghost" class="w-full" :disabled="! $trouble['can_run']" data-test="trouble-run">Run back</flux:button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($last_work_report)
            <div class="ez-panel flex items-start gap-3 px-4 py-3" data-test="last-work-report">
                <span class="ez-label shrink-0 pt-0.5 !text-amber-400">Report</span>
                <p class="ez-phosphor text-sm">{{ $last_work_report }}</p>
            </div>
        @endif

        @if ($active_work)
            <div
                @if ($active_work['type'] === 'city_action') data-work-loop data-available-at="{{ $active_work['available_at_iso'] }}" @endif
                x-data="{
                    remainingSeconds: {{ $active_work['remaining_seconds'] }},
                    totalSeconds: {{ max(1, (int) ($active_work['duration_seconds'] ?? $active_work['remaining_seconds'])) }},
                    deadline: Date.parse(@js($active_work['available_at_iso'])),
                    timerId: null,
                    isLoop: @js($active_work['type'] === 'city_action'),
                    isTravel: @js($active_work['type'] === 'travel'),
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

                        if (this.isTravel && this.remainingSeconds === 0 && Date.now() >= this.nextRefreshAt) {
                            this.nextRefreshAt = Date.now() + 5000;
                            window.location.reload();
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
                    },
                    ringOffset() {
                        return 276.46 * Math.min(1, this.remainingSeconds / this.totalSeconds);
                    }
                }"
                x-init="init()"
                class="ez-panel overflow-hidden !border-amber-400/40"
            >
                <div class="grid gap-0 xl:grid-cols-[0.9fr_1.1fr]">
                    <div class="relative min-h-56">
                        <img
                            src="{{ $active_work['image_url'] }}"
                            alt=""
                            class="absolute inset-0 h-full w-full object-cover opacity-70 saturate-[.7]"
                            data-test="active-work-image"
                        >
                        <div class="absolute inset-0 bg-linear-to-t from-ez-ground via-ez-ground/40 to-transparent xl:bg-linear-to-r" aria-hidden="true"></div>
                        <div class="relative flex h-full flex-col items-center justify-center gap-2 p-6">
                            <div class="relative size-44">
                                <svg viewBox="0 0 100 100" class="size-full -rotate-90" aria-hidden="true">
                                    <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" class="text-zinc-700" stroke-width="5" />
                                    <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" class="text-amber-400" stroke-width="5" stroke-linecap="round" stroke-dasharray="276.46" x-bind:stroke-dashoffset="ringOffset()" style="transition: stroke-dashoffset 1s linear" />
                                </svg>
                                <div class="absolute inset-0 grid place-content-center text-center">
                                    <p class="ez-numeral text-5xl text-ez-ink" x-text="formattedRemaining()" role="timer" aria-live="off"></p>
                                    <p class="ez-label mt-1">Remaining</p>
                                </div>
                            </div>
                            <p class="max-w-xs text-center text-xs text-ez-muted">
                                @if ($active_work['type'] === 'city_action')
                                    Until the next payout. Repeats automatically.
                                @elseif ($active_work['type'] === 'travel')
                                    Until you reach {{ $active_work['to_city'] }}. You arrive automatically.
                                @else
                                    Server unlocks completion {{ $active_work['available_at_human'] }}.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4 p-5 sm:p-6">
                        <div>
                            <p class="ez-label !text-amber-400">Active timer</p>
                            <h2 class="ez-display mt-2 text-4xl text-ez-ink">{{ $active_work['title'] }}</h2>
                            <p class="mt-2 max-w-2xl text-sm text-ez-muted">{{ $active_work['description'] }}</p>
                        </div>

                        @if ($active_work['type'] === 'city_action')
                            <div class="rounded-sm border border-zinc-700 bg-zinc-950/60 p-4 text-sm" data-work-live="loop-progress" data-test="work-loop-progress">
                                <p class="ez-phosphor">
                                    &gt; {{ $active_work['cycles_completed'] }} {{ Str::plural('cycle', $active_work['cycles_completed']) }} completed
                                    · +{{ $active_work['xp_earned'] }} XP so far. Keeps running if you leave, and stops {{ $active_work['loop_ends_at_human'] }}.
                                </p>
                                @if ($active_work['last_cycle_summary'])
                                    <p class="ez-phosphor mt-2 opacity-80">&gt; Last cycle: {{ $active_work['last_cycle_summary'] }}</p>
                                @endif
                            </div>
                        @endif

                        @if ($active_work['enemy'])
                            @php($enemy = $active_work['enemy'])
                            <div class="ez-card ez-heat p-4 text-sm text-ez-muted" data-work-live="enemy" data-test="work-enemy">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="font-medium text-red-300">Fighting: {{ $enemy['name'] }}</p>
                                    <p class="font-mono text-xs">{{ $enemy['health'] }} / {{ $enemy['maximum'] }} health</p>
                                </div>
                                <div class="ez-meter mt-2" aria-hidden="true">
                                    <div class="ez-meter-fill text-red-400" style="width: {{ (int) round($enemy['health'] / max(1, $enemy['maximum']) * 100) }}%"></div>
                                </div>
                                <p class="mt-2">{{ $enemy['description'] }} One round is fought each cycle. You break off and flee if your health drops to {{ config('combat.flee_at_health') }}.</p>
                            </div>
                        @endif

                        @if ($active_work['weather']->isBad())
                            <div class="rounded-sm border border-sky-400/40 bg-sky-500/10 p-4 text-sm text-ez-muted" data-test="active-work-weather">
                                <span class="font-medium text-sky-300">{{ $active_work['weather']->label() }}:</span>
                                {{ match ($active_work['type']) {
                                    'travel' => "This route takes {$active_work['weather']->timerIncreasePercent()}% longer in the {$active_work['weather']->value}.",
                                    'brain_scorcher' => "The Brain Scorcher runs {$active_work['weather']->timerIncreasePercent()}% slower in the {$active_work['weather']->value}.",
                                    default => $active_work['weather']->effectsSummary(),
                                } }}
                            </div>
                        @endif

                        @if ($active_work['skill_name'])
                            <div class="rounded-sm border border-zinc-700 p-4" data-work-live="training-focus">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="ez-label">Training focus</p>
                                        <p class="mt-1 text-lg font-semibold text-ez-ink">{{ $active_work['skill_name'] }}</p>
                                        <p class="mt-1 text-sm text-ez-muted">Level {{ $active_work['skill_level'] }} · {{ $active_work['skill_xp'] }} / {{ $active_work['skill_next_level_xp'] }} XP</p>
                                    </div>

                                    <div class="text-right text-sm text-ez-muted">
                                        <p>{{ $active_work['skill_xp_remaining'] }} XP to next level</p>
                                        <p class="mt-1 font-mono text-ez-ink">{{ $active_work['duration_seconds'] }}s total duration</p>
                                    </div>
                                </div>

                                <div class="ez-meter mt-3" aria-hidden="true">
                                    <div class="ez-meter-fill text-amber-400" style="width: {{ $active_work['skill_progress_percent'] }}%"></div>
                                </div>
                            </div>
                        @elseif ($active_work['type'] === 'brain_scorcher')
                            <div class="rounded-sm border border-fuchsia-400/40 bg-fuchsia-500/10 p-4 text-sm text-ez-muted">
                                <span class="font-medium text-fuchsia-300">Offering note:</span>
                                The offering burns when you complete the work. It does not grant skill experience.
                            </div>
                        @else
                            <div class="rounded-sm border border-zinc-700 p-4 text-sm text-ez-muted">
                                @if ($active_work['route_stops'] !== [])
                                    <p data-test="travel-route">
                                        <span class="font-medium text-ez-ink">Route:</span>
                                        {{ implode(' → ', [$active_work['from_city'], $active_work['to_city'], ...$active_work['route_stops']]) }}
                                    </p>
                                    <p class="mt-2">
                                        After {{ $active_work['to_city'] }} the journey carries on to {{ Arr::last($active_work['route_stops']) }} on its own.
                                    </p>
                                @endif
                                <p @class(['mt-2' => $active_work['route_stops'] !== []])>
                                    <span class="font-medium text-ez-ink">Travel note:</span>
                                    @if ($active_work['is_retreat'])
                                        This route does not grant skill experience, and there is no turning back.
                                    @else
                                        This route does not grant skill experience. Cancel before arrival if you want to stay in {{ $active_work['from_city'] }}.
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-3">
                            @if ($active_work['type'] === 'brain_scorcher')
                                <form method="POST" action="{{ route('work.complete') }}">
                                    @csrf
                                    <flux:button type="submit" variant="primary" x-bind:disabled="remainingSeconds > 0">
                                        Complete work
                                    </flux:button>
                                </form>
                            @endif

                            @unless ($active_work['is_retreat'])
                                <form method="POST" action="{{ route('work.cancel') }}">
                                    @csrf
                                    <flux:button type="submit" variant="ghost" data-test="cancel-work">
                                        {{ $active_work['type'] === 'city_action' ? 'Stop working' : 'Cancel' }}
                                    </flux:button>
                                </form>
                            @endunless

                            <p class="text-xs text-ez-muted">
                                {{ match (true) {
                                    $active_work['is_retreat'] => 'Running from trouble cannot be canceled.',
                                    $active_work['type'] === 'city_action' => 'Stopping pays out finished cycles. The unfinished one earns nothing.',
                                    $active_work['type'] === 'travel' => 'Canceling keeps you in '.$active_work['from_city'].'. Arrival is always verified on the server.',
                                    default => 'Completion is always verified on the server, even if the local timer is modified.',
                                } }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            {{-- Location: the place name is the hero --}}
            <section class="flex min-w-0 flex-col gap-5 pt-2" data-test="location-hero">
                <div>
                    <p class="ez-label !text-ez-signal">{{ $coordinates ?? 'Position unknown' }} · Current location</p>
                    <h1 class="ez-display mt-2 text-[clamp(3.75rem,11vw,8.5rem)] text-ez-ink">{{ $city?->city }}</h1>
                    <p class="mt-3 text-sm text-ez-muted">{{ $country?->country }} · {{ $city?->biome }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($city?->has_trade_post)
                        <p class="inline-flex items-center gap-1.5 rounded-sm border border-emerald-400/50 px-2.5 py-1 font-mono text-[11px] text-emerald-300" data-test="trade-post-badge">
                            <flux:icon.arrows-right-left variant="micro" />
                            Trade post · open the Trade tab on your PDA
                        </p>
                    @endif
                    <p class="inline-flex items-center rounded-sm border border-zinc-700 px-2.5 py-1 font-mono text-[11px] text-ez-muted">
                        {{ Str::headline($player->role_key) }} · Premium {{ $player->hasPremiumEntitlement() ? 'active' : 'inactive' }} · {{ $player->isMuted() ? 'Muted on radio' : 'Radio open' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-px overflow-hidden rounded-sm border border-zinc-700 bg-zinc-700 md:grid-cols-4">
                    <div @class([
                        'col-span-2 p-4 md:col-span-1',
                        'bg-ez-ground/90' => ! $weather?->isBad(),
                        'bg-sky-950/90' => $weather?->isBad(),
                    ]) data-test="current-weather">
                        <p class="ez-label">Current weather</p>
                        <p @class(['mt-2 text-xl font-semibold', 'text-ez-ink' => ! $weather?->isBad(), 'text-sky-300' => $weather?->isBad()])>{{ $weather?->label() }}</p>
                        <p class="mt-1 text-xs text-ez-muted">{{ $weather?->effectsSummary() }}</p>
                    </div>
                    <div class="bg-ez-ground/90 p-4">
                        <p class="ez-label">Average temperature</p>
                        <p class="ez-numeral mt-2 text-4xl text-ez-ink">{{ $country?->avg_temp_c }}°C</p>
                    </div>
                    <div class="bg-ez-ground/90 p-4">
                        <p class="ez-label">Rain chance</p>
                        <p class="ez-numeral mt-2 text-4xl text-ez-ink">{{ $city?->rain_chance_pct }}%</p>
                    </div>
                    <div @class(['col-span-2 p-4 md:col-span-1', 'bg-ez-ground/90' => $city?->trouble_chance_pct < 50, 'bg-red-950/80' => $city?->trouble_chance_pct >= 50])>
                        <p class="ez-label">Trouble chance</p>
                        <p @class(['ez-numeral mt-2 text-4xl', 'text-ez-ink' => $city?->trouble_chance_pct < 50, 'text-red-300' => $city?->trouble_chance_pct >= 50])>{{ $city?->trouble_chance_pct }}%</p>
                    </div>
                </div>

                <div class="ez-panel ez-scanlines flex items-start gap-3 px-4 py-3">
                    <span class="ez-label shrink-0 pt-0.5 !text-ez-phosphor">Intercept</span>
                    <p class="ez-phosphor text-sm"><span class="font-semibold">Scout report:</span> {{ $local_event }}</p>
                </div>
            </section>

            {{-- Survivor condition: Geiger panel --}}
            <section class="ez-panel min-w-0 p-5 sm:p-6" data-work-live="condition" data-test="survivor-condition">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="ez-label">Field readout</p>
                        <h2 class="ez-display mt-1 text-3xl text-ez-ink">Survivor condition</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        @php($drag = min(100, $condition['timer_increase_percent']))
                        <svg viewBox="0 0 120 70" class="h-12 w-20" aria-hidden="true">
                            <path d="M10 64a50 50 0 0 1 100 0" fill="none" stroke="currentColor" class="text-zinc-700" stroke-width="7" />
                            <path d="M85 20.7a50 50 0 0 1 25 43.3" fill="none" stroke="currentColor" class="text-red-500/80" stroke-width="7" />
                            <g transform="rotate({{ -90 + round($drag * 1.8) }} 60 64)">
                                <g class="ez-needle">
                                    <line x1="60" y1="64" x2="60" y2="20" stroke="currentColor" class="text-amber-400" stroke-width="2.5" stroke-linecap="round" />
                                </g>
                            </g>
                            <circle cx="60" cy="64" r="5" fill="currentColor" class="text-ez-ink" />
                        </svg>
                        <span @class([
                            'rounded-sm border px-2 py-1 font-mono text-[11px]',
                            'border-emerald-400/50 text-emerald-300' => $condition['timer_increase_percent'] === 0,
                            'border-amber-400/60 text-amber-300' => $condition['timer_increase_percent'] > 0,
                        ]) data-test="condition-timer-penalty">
                            {{ $condition['timer_increase_percent'] === 0 ? 'No timer penalty' : "Timers +{$condition['timer_increase_percent']}%" }}
                        </span>
                    </div>
                </div>

                <div class="mt-5 grid gap-4">
                    @php($health = $condition['health'])
                    <x-zone.meter
                        :label="__('Health')"
                        :value="$health['value']"
                        :maximum="$health['maximum']"
                        :tone="$healthTone($health)"
                        :reading="$health['label'].' · '.$health['value'].' / '.$health['maximum']"
                        :caption="$health['timer_increase_percent'] === 0 ? 'No effect on timers.' : 'Timers +'.$health['timer_increase_percent'].'%.'"
                        data-test="condition-health"
                    />

                    @foreach (['hunger' => 'Hunger', 'fatigue' => 'Fatigue'] as $need => $needLabel)
                        @php($state = $condition[$need])
                        <x-zone.meter
                            :label="$needLabel"
                            :value="$state['value']"
                            :maximum="$state['maximum']"
                            :tone="$needTone($state)"
                            :reading="$state['label'].' · '.$state['value'].' / '.$state['maximum']"
                            :caption="$state['timer_increase_percent'] === 0 ? 'No effect on timers.' : 'Timers +'.$state['timer_increase_percent'].'%.'"
                            data-test="condition-{{ $need }}"
                        />
                    @endforeach
                </div>

                <details class="group mt-4 text-sm text-ez-muted">
                    <summary class="ez-label cursor-pointer list-none hover:!text-ez-ink"><span class="group-open:hidden">+ How condition works</span><span class="hidden group-open:inline">− How condition works</span></summary>
                    <p class="mt-2 leading-6">Work and travel make you hungry and tired. You can keep going, but every timer runs slower, and pushing on while starving or spent wears down your health. Eat to ease hunger, and patch up wounds with medical supplies or at a field clinic. Fatigue fades and wounds slowly mend while you rest between jobs, unless you are starving. If your health reaches zero, you die.</p>
                </details>

                <div class="mt-5 border-t border-zinc-700 pt-4">
                    <p class="ez-label">Rations</p>
                    @if ($condition['foods'] !== [])
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($condition['foods'] as $food)
                                <form method="POST" action="{{ route('food.eat') }}" class="ez-card flex items-center justify-between gap-3 p-3">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $food['item_id'] }}">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-ez-ink">{{ $food['name'] }} <span class="font-mono text-ez-muted">×{{ $food['owned'] }}</span></p>
                                        <p class="text-xs text-ez-muted">
                                            {{ collect(['Health +'.$food['health_restored'] => $food['health_restored'], 'Hunger -'.$food['hunger_restored'] => $food['hunger_restored'], 'Fatigue -'.$food['fatigue_restored'] => $food['fatigue_restored']])->filter()->keys()->implode(' · ') }}
                                        </p>
                                    </div>
                                    <flux:button type="submit" size="sm" data-test="eat-{{ $food['item_id'] }}">Eat</flux:button>
                                </form>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 rounded-sm border border-dashed border-zinc-600 p-3 text-sm text-ez-muted">
                            No food in your pack. Cook borscht in Kyiv, or bring back rations and fish from your runs.
                        </p>
                    @endif

                    @if ($condition['medical_items'] !== [])
                        <p class="ez-label mt-4">Medical</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2" data-test="medical-items">
                            @foreach ($condition['medical_items'] as $medicalItem)
                                <form method="POST" action="{{ route('medical.use') }}" class="ez-card flex items-center justify-between gap-3 !border-red-400/40 p-3">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $medicalItem['item_id'] }}">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-ez-ink">{{ $medicalItem['name'] }} <span class="font-mono text-ez-muted">×{{ $medicalItem['owned'] }}</span></p>
                                        <p class="text-xs text-red-300">Health +{{ $medicalItem['health_restored'] }}</p>
                                    </div>
                                    <flux:button type="submit" size="sm" data-test="use-medical-{{ $medicalItem['item_id'] }}">Use</flux:button>
                                </form>
                            @endforeach
                        </div>
                    @elseif ($condition['health']['value'] < $condition['health']['maximum'])
                        <p class="mt-2 rounded-sm border border-dashed border-zinc-600 p-3 text-sm text-ez-muted">
                            No medical supplies. Raid pharmacies in Kyiv, roll bandages in Warsaw, or pay a field clinic in Kyiv or Warsaw to patch you up.
                        </p>
                    @endif
                </div>
            </section>
        </div>

        @if ($quest_step_actions->isNotEmpty())
            {{-- Field contacts: notes pinned in the survivor's journal --}}
            <section>
                <div class="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <p class="ez-label">Active quest objectives available at this location.</p>
                        <h2 class="ez-display mt-1 text-3xl text-ez-ink">Field contacts</h2>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($quest_step_actions as $entry)
                        <div @class(['ez-paper p-5', '-rotate-[0.6deg]' => $loop->odd, 'rotate-[0.5deg]' => $loop->even])>
                            <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.2em] text-ez-stamp">{{ $entry['quest_name'] }}</p>
                            <h3 class="mt-2 text-2xl leading-tight font-semibold text-ez-paper-ink">{{ $entry['step']->person_of_interest }}</h3>
                            @if (count($entry['requirements']) > 0)
                                <p class="mt-2 text-sm text-ez-paper-muted">Requires: <span class="font-medium text-ez-stamp">{{ collect($entry['requirements'])->map(fn ($requirement) => $requirement['item']?->name.' ×'.$requirement['quantity'])->implode(', ') }}</span></p>
                            @endif
                            <form method="POST" action="{{ route('quest-step.interact', $entry['step']) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="inline-flex min-h-10 items-center rounded-sm bg-ez-paper-ink px-4 py-2 font-mono text-sm text-ez-paper transition hover:bg-black">
                                    {{ $entry['step']->action_label }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- City actions: pins on the local map --}}
        <section>
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="ez-label">Every action grants XP and loot tied to the current city.</p>
                    <h2 class="ez-display mt-1 text-3xl text-ez-ink">City actions</h2>
                </div>
                <p class="flex items-center gap-4 font-mono text-[11px] text-ez-muted">
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-ez-signal" aria-hidden="true"></span>Safe work</span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-ez-heat" aria-hidden="true"></span>Combat risk</span>
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
                @foreach ($actions as $entry)
                    @php($isHostile = $entry['enemy_names'] !== [])
                    <div @class(['ez-card ez-pin flex min-w-0 flex-col gap-3 p-4 pt-5', 'ez-pin-heat ez-heat' => $isHostile, 'opacity-80' => ! $entry['available']])>
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-lg leading-tight font-semibold text-ez-ink">{{ $entry['action']->label }}</h3>
                            <span @class([
                                'shrink-0 rounded-sm border px-2 py-0.5 font-mono text-[10px] uppercase tracking-wider',
                                'border-zinc-600 text-ez-muted' => $entry['action']->risk_level === 'low',
                                'border-amber-400/50 text-amber-300' => $entry['action']->risk_level === 'medium',
                                'border-red-400/60 text-red-300' => ! in_array($entry['action']->risk_level, ['low', 'medium'], true),
                            ])>{{ Str::headline($entry['action']->risk_level) }}</span>
                        </div>
                        <p class="text-sm text-ez-muted">{{ $entry['action']->description }}</p>
                        <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-zinc-400">
                            {{ $entry['action']->skill?->display_name }} · level {{ $entry['required_level'] }} required · reward {{ collect([data_get($entry['action']->reward_profile, 'item_key'), data_get($entry['action']->reward_profile, 'health') ? 'health +'.data_get($entry['action']->reward_profile, 'health') : null])->filter()->implode(', ') }}
                        </p>
                        <div class="grid gap-1 text-xs">
                            <p class="text-ez-muted" data-test="action-costs-{{ $entry['action']->action_key }}">
                                Each cycle: hunger +{{ $entry['action']->hunger_cost }} · fatigue +{{ $entry['action']->fatigue_cost }}
                            </p>
                            @if ($isHostile)
                                <p class="flex items-start gap-1 text-red-300" data-test="action-enemies-{{ $entry['action']->action_key }}">
                                    <flux:icon.exclamation-triangle variant="micro" class="mt-px shrink-0" />
                                    <span>Fight: {{ implode(', ', $entry['enemy_names']) }} · each cycle is one round, and you can get hurt</span>
                                </p>
                            @endif
                            @if (count($entry['required_items']) > 0)
                                <p class="text-ez-muted" data-test="required-items-{{ $entry['action']->action_key }}">
                                    Uses:
                                    @foreach ($entry['required_items'] as $requiredItem)
                                        <span @class([
                                            'text-emerald-300' => $requiredItem['owned'] >= $requiredItem['quantity'],
                                            'text-amber-300' => $requiredItem['owned'] < $requiredItem['quantity'],
                                        ])>{{ $requiredItem['item']->name }} ×{{ $requiredItem['quantity'] }} ({{ $requiredItem['owned'] }} carried)</span>@if (! $loop->last), @endif
                                    @endforeach
                                </p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('city-action.store') }}" class="mt-auto pt-1">
                            @csrf
                            <input type="hidden" name="city_action_id" value="{{ $entry['action']->id }}">
                            <flux:button type="submit" :variant="$isHostile ? 'danger' : 'primary'" class="w-full" :disabled="! $entry['available']" data-test="action-{{ $entry['action']->action_key }}">
                                {{ $entry['available'] ? 'Begin work' : (! $can_perform_city_actions ? 'Unavailable' : ($entry['user_level'] < $entry['required_level'] ? 'Locked' : 'Missing items')) }}
                            </flux:button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($brain_scorcher)
            {{-- Brain Scorcher: seen through a thermal scope --}}
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
                class="ez-panel relative overflow-hidden !border-fuchsia-400/40 bg-[radial-gradient(ellipse_at_90%_110%,rgb(224_69_43/0.28),transparent_55%),radial-gradient(ellipse_at_0%_0%,rgb(107_26_143/0.45),transparent_60%)] p-5 sm:p-6"
                data-test="brain-scorcher"
            >
                <div class="ez-sweep pointer-events-none absolute inset-0" aria-hidden="true"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="ez-label !text-fuchsia-300">High risk · high reward</p>
                        <h2 class="ez-display mt-2 bg-linear-to-r from-amber-100 via-amber-400 to-red-500 bg-clip-text text-4xl text-transparent sm:text-5xl">The Brain Scorcher</h2>
                        <p class="mt-3 max-w-2xl text-sm text-ez-muted">
                            A Soviet psi-emitter still crackles under the cooling towers. Feed it an offering and it may hand back something worse, the same, something better, a Scorched item found nowhere else, or nothing at all. Rarer items and bigger offerings run hotter.
                        </p>
                    </div>
                    <span class="rounded-sm border border-fuchsia-400/50 px-2.5 py-1 font-mono text-xs text-fuchsia-200">{{ $brain_scorcher['duration_seconds'] }}s burn</span>
                </div>

                @if (count($brain_scorcher['offerings']) === 0)
                    <p class="relative mt-5 rounded-sm border border-dashed border-zinc-600 p-4 text-sm text-ez-muted">
                        Your pack is empty. Bring something to burn.
                    </p>
                @else
                    <form method="POST" action="{{ route('brain-scorcher.store') }}" class="relative mt-5 grid gap-5 lg:grid-cols-[1fr_1.2fr]">
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

                        <div class="grid grid-cols-2 content-start gap-px overflow-hidden rounded-sm border border-zinc-700 bg-zinc-700 text-sm sm:grid-cols-5" x-show="odds">
                            @foreach (['nothing' => 'Burned', 'downgrade' => 'Worse', 'same' => 'Same', 'upgrade' => 'Better', 'jackpot' => 'Scorched'] as $outcome => $label)
                                <div @class([
                                    'p-3',
                                    'bg-ez-ground/90' => $outcome !== 'jackpot',
                                    'col-span-2 bg-fuchsia-950/90 sm:col-span-1' => $outcome === 'jackpot',
                                ])>
                                    <p class="ez-label">{{ $label }}</p>
                                    <p @class(['ez-numeral mt-1 text-4xl', 'text-ez-ink' => $outcome !== 'jackpot', 'text-amber-300' => $outcome === 'jackpot']) x-text="(odds?.{{ $outcome }} ?? 0) + '%'"></p>
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
            :canPerformCityActions="$can_perform_city_actions"
            :cityActionRestriction="$city_action_restriction"
            :jobs="$jobs"
        />
    </div>
</x-layouts::app>
