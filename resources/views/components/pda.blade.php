@props([
    'skills',
    'inventory',
    'gear',
    'bazaar',
    'alerts',
    'cosmetics',
    'player',
    'city',
    'country',
    'jobs',
])

@php
    $isStaff = $player->isModerator() || $player->isAdmin();
    $tabs = collect([
        'skills' => ['Skills', 'Skill Tracker', 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5'],
        'pack' => ['Pack', 'Inventory', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
        'gear' => ['Gear', 'Worn Gear', 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
        'bazaar' => ['Bazaar', 'Bazaar', 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z'],
        'radio' => ['Radio', 'Radio Comms', 'M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z'],
        'trade' => ['Trade', 'Trade Board', 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
        'contracts' => ['Jobs', 'Contracts', 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z'],
        'premium' => ['Premium', 'Cosmetics', 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z'],
        'admin' => ['Admin', 'Admin Console', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
    ])->when(! $isStaff, fn ($tabs) => $tabs->except('admin'));
    $alertTotal = array_sum($alerts);
    $rarityText = [
        'common' => 'text-ez-muted',
        'uncommon' => 'text-emerald-300',
        'rare' => 'text-sky-300',
        'epic' => 'text-fuchsia-300',
        'scorched' => 'text-orange-300',
    ];
    $shop = [
        'sellRatio' => (int) config('shop.sell_ratio_percent'),
        'barterBonus' => $bazaar['barter_sell_bonus_percent'],
        'bulkTenths' => (int) config('shop.bulk_bonus_tenths_percent_per_item'),
        'maxBulk' => (int) config('shop.maximum_bulk_bonus_percent'),
    ];
@endphp

<div
    x-data="{
        open: sessionStorage.getItem('pda_open') === 'true',
        tab: @js($tabs->keys()).includes(sessionStorage.getItem('pda_tab')) ? sessionStorage.getItem('pda_tab') : 'pack',
        tabNames: @js($tabs->map(fn (array $tab): string => $tab[1])),
        alerts: @js($alerts),
        get alertTotal() {
            return Object.values(this.alerts).reduce((total, count) => total + count, 0);
        },
        init() {
            this.$watch('open', v => { sessionStorage.setItem('pda_open', v); this.seeCurrentTab(); });
            this.$watch('tab', v => { sessionStorage.setItem('pda_tab', v); this.seeCurrentTab(); this.showCurrentTab(); });
            this.$watch('open', v => v && this.$nextTick(() => this.showCurrentTab()));
            this.$nextTick(() => this.seeCurrentTab());

            {{-- Counts come back with every live refresh of the city menu, such as after eating or a work cycle. --}}
            window.addEventListener('ez:live-refreshed', (event) => {
                const fresh = event.detail.page.querySelector('[data-pda-alerts]');

                if (fresh === null) {
                    return;
                }

                this.applyCounts(JSON.parse(fresh.dataset.pdaAlerts));
            });

            {{-- Without a live broadcasting connection, ask for fresh counts every minute while the page is on screen. --}}
            setInterval(() => this.poll(), 60000);
            document.addEventListener('visibilitychange', () => this.poll());

            {{-- With live broadcasting, a new message or trade change raises the bell straight away. --}}
            if (window.Echo) {
                try {
                    @if ($player->canPerformTask('chat_send'))
                        window.Echo.private('radio').listen('RadioMessageSent', (event) => {
                            if (event.user_id !== {{ $player->id }}) {
                                this.raise('radio');
                            }
                        });
                    @endif
                    @if ($player->canPerformTask('trade_create'))
                        window.Echo.private('App.Models.User.{{ $player->id }}').listen('TradeUpdated', () => this.raise('trade'));
                    @endif
                } catch {}
            }
        },
        isBroadcasting() {
            return window.Echo?.connector?.pusher?.connection?.state === 'connected';
        },
        async poll() {
            if (document.hidden || this.isBroadcasting()) {
                return;
            }

            try {
                const response = await fetch(@js(route('pda-alerts.index')), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (response.ok) {
                    this.applyCounts(await response.json());
                }
            } catch {}
        },
        applyCounts(counts) {
            Object.keys(counts).forEach((alertTab) => {
                if (! (this.open && this.tab === alertTab)) {
                    this.alerts[alertTab] = counts[alertTab];
                }
            });
        },
        raise(alertTab) {
            if (this.open && this.tab === alertTab) {
                this.markSeen(alertTab);

                return;
            }

            this.alerts[alertTab] = (this.alerts[alertTab] ?? 0) + 1;
        },
        {{-- On a narrow phone the tab bar scrolls sideways, so keep the open tab in view. --}}
        showCurrentTab() {
            this.$root.querySelector('[role=tab][aria-selected=true]')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        },
        seeCurrentTab() {
            if (this.open && (this.alerts[this.tab] ?? 0) > 0) {
                this.markSeen(this.tab);
            }
        },
        markSeen(alertTab) {
            this.alerts[alertTab] = 0;
            window.Livewire?.dispatch('pda-tab-seen', { tab: alertTab });

            {{-- The radio and trade tabs are Livewire components that hear the event above; the Jobs tab is not. --}}
            if (alertTab === 'contracts') {
                fetch(@js(route('pda-alerts.seen', 'contracts')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': @js(csrf_token()), Accept: 'application/json' },
                    credentials: 'same-origin',
                }).catch(() => {});
            }
        },
    }"
    @keydown.escape.window="open = false"
>
    {{-- Floating toggle button, with a red bell when something on the PDA needs a look --}}
    <button
        @click="open = !open"
        type="button"
        :aria-expanded="open.toString()"
        aria-controls="pda-device"
        class="fixed right-[max(1rem,env(safe-area-inset-right))] bottom-[max(1rem,env(safe-area-inset-bottom))] z-30 flex min-h-12 items-center gap-2.5 rounded-sm border px-4 py-3 font-mono text-sm tracking-widest shadow-lg transition-colors select-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
        :class="open
            ? 'bg-zinc-800 border-emerald-400 text-emerald-300 shadow-[0_0_24px_rgba(111,211,200,0.35)]'
            : 'bg-zinc-900 border-emerald-400/60 text-emerald-300 shadow-[0_0_18px_rgba(111,211,200,0.18)] hover:border-emerald-400 hover:shadow-[0_0_24px_rgba(111,211,200,0.35)]'"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3v.008h-3V9.75Z" />
        </svg>
        <span class="hidden sm:inline" x-text="open ? '[ CLOSE PDA ]' : '[ OPEN PDA ]'"></span>
        <span class="sm:hidden" x-text="open ? 'CLOSE' : 'PDA'"></span>
        <span
            x-show="alertTotal > 0"
            @if ($alertTotal === 0) x-cloak @endif
            class="absolute -top-2.5 -right-2.5 flex h-6 min-w-6 items-center justify-center gap-0.5 rounded-full bg-red-600 px-1.5 text-[11px] font-bold tracking-normal text-white shadow-[0_0_12px_rgba(239,68,68,0.6)] ring-2 ring-ez-ground"
            data-test="pda-alert-badge"
        >
            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a6 6 0 0 0-6 6c0 1.887-.454 3.665-1.257 5.234a.75.75 0 0 0 .515 1.076 32.91 32.91 0 0 0 3.256.508 3.5 3.5 0 0 0 6.972 0 32.903 32.903 0 0 0 3.256-.508.75.75 0 0 0 .515-1.076A11.448 11.448 0 0 1 16 8a6 6 0 0 0-6-6ZM8.05 14.943a33.54 33.54 0 0 0 3.9 0 2 2 0 0 1-3.9 0Z" clip-rule="evenodd" /></svg>
            <span x-text="alertTotal > 9 ? '9+' : alertTotal">{{ $alertTotal > 9 ? '9+' : $alertTotal }}</span>
            <span class="sr-only">new on your PDA</span>
        </span>
    </button>

    {{-- Backdrop --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 z-40 bg-ez-ground/80"
        aria-hidden="true"
    ></div>
    {{-- PDA Device --}}
    <div
        id="pda-device"
        role="dialog"
        aria-modal="true"
        aria-label="Field PDA"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-x-2 top-2 bottom-2 z-50 flex flex-col sm:inset-auto sm:left-1/2 sm:top-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:h-[85dvh] sm:w-auto sm:min-w-0 sm:aspect-[11/20] {{-- sm:max-w-2xl --}}"
    >
        {{-- Outer device body (metallic grey casing) --}}
        <div class="relative flex min-h-0 flex-1 flex-col overflow-hidden rounded-[2rem] bg-linear-to-b from-[#46524f] via-[#27302f] to-[#121817] p-3.5 pb-4 pt-2.5 shadow-[0_25px_80px_rgba(0,0,0,0.9),inset_0_1px_1px_rgba(255,255,255,0.18),inset_0_-2px_4px_rgba(0,0,0,0.3)] ring-1 ring-white/10">

            {{-- Corner screws (decorative) --}}
            <div class="absolute top-2.5 left-2.5 h-3 w-3 rounded-full bg-linear-to-br from-[#5b6866] to-[#1c2423] shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>
            <div class="absolute top-2.5 right-2.5 h-3 w-3 rounded-full bg-linear-to-br from-[#5b6866] to-[#1c2423] shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>
            <div class="absolute bottom-3 left-2.5 h-3 w-3 rounded-full bg-linear-to-br from-[#5b6866] to-[#1c2423] shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>
            <div class="absolute bottom-3 right-2.5 h-3 w-3 rounded-full bg-linear-to-br from-[#5b6866] to-[#1c2423] shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>

            {{-- Device header strip --}}
            <div class="mb-2 flex items-center justify-between px-4" aria-hidden="true">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.85)]"></div>
                    <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-zinc-400">EX-PDA · MK VII</span>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Signal bars --}}
                    <div class="flex items-end gap-[2px]">
                        <div class="h-[5px] w-[3px] rounded-[1px] bg-zinc-500"></div>
                        <div class="h-[8px] w-[3px] rounded-[1px] bg-zinc-500"></div>
                        <div class="h-[11px] w-[3px] rounded-[1px] bg-zinc-500"></div>
                        <div class="h-[13px] w-[3px] rounded-[1px] bg-zinc-400"></div>
                    </div>
                    {{-- Battery --}}
                    <div class="flex items-center gap-[1px]">
                        <div class="flex h-[11px] w-[18px] items-center rounded-[2px] border border-zinc-500 p-[2px]">
                            <div class="h-full w-3/4 rounded-[1px] bg-emerald-500"></div>
                        </div>
                        <div class="h-[6px] w-[2px] rounded-r bg-zinc-500"></div>
                    </div>
                </div>
            </div>

            {{-- Screen bezel --}}
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-[1.5rem] bg-zinc-900 p-2.5 shadow-[inset_0_3px_8px_rgba(0,0,0,0.9),inset_0_-1px_2px_rgba(255,255,255,0.04)]">

                {{-- Screen --}}
                <div class="ez-scanlines flex min-h-0 flex-1 flex-col overflow-hidden rounded-[1.1rem] bg-zinc-950">

                    {{-- Status bar --}}
                    <div class="flex shrink-0 items-center gap-2 border-b border-zinc-800/80 bg-zinc-900/50 px-4 py-2">
                        <span class="truncate font-mono text-xs text-emerald-400">{{ $city?->city ?? 'UNKNOWN' }}</span>
                        @if ($country)
                            <span class="text-xs text-zinc-700">·</span>
                            <span class="truncate font-mono text-xs text-zinc-500">{{ strtoupper($country->country) }}</span>
                        @endif
                        <span class="ml-auto mr-3 shrink-0 font-mono text-[10px] uppercase tracking-wide text-zinc-600" x-text="tabNames[tab] ?? ''"></span>
                        <button
                            @click="open = false"
                            type="button"
                            class="shrink-0 rounded p-0.5 text-zinc-600 transition-colors hover:text-zinc-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-500"
                            aria-label="Close PDA"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Tab content area --}}
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain scroll-smooth" tabindex="-1">
                        {{-- SKILLS tab --}}
                        <div x-show="tab === 'skills'" class="p-4" data-work-live="skills">
                            <div class="mb-3 flex items-baseline justify-between gap-3">
                                <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Skill Tracker</p>
                                <p class="font-mono text-[10px] uppercase tracking-wider text-zinc-500" data-test="skills-total-level">
                                    Total LV <span class="text-sm font-semibold text-emerald-400">{{ number_format($skills->sum('level')) }}</span>
                                </p>
                            </div>
                            <ul class="space-y-1.5">
                                @foreach ($skills as $skill)
                                    <li class="flex items-center gap-3 rounded-md border border-zinc-800/80 bg-zinc-900/40 p-2 transition-colors hover:border-emerald-500/40 hover:bg-zinc-900/70" data-test="skill-row-{{ $skill->skill?->key }}">
                                        <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-md ring-1 ring-white/10">
                                            <img src="{{ asset($skill->skill?->imagePath() ?? 'images/work/default.jpg') }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                                            <span class="absolute inset-0 bg-linear-to-t from-black/50 to-transparent" aria-hidden="true"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-baseline justify-between gap-2">
                                                <p class="min-w-0 truncate text-sm font-medium text-zinc-100" title="{{ $skill->skill?->description }}">{{ $skill->skill?->display_name }}</p>
                                                <p class="shrink-0 font-mono text-[10px] uppercase tracking-wider text-zinc-500">LV <span class="text-base leading-none font-semibold text-emerald-400">{{ $skill->level }}</span></p>
                                            </div>
                                            <div
                                                class="ez-meter mt-1.5 !h-1.5 text-emerald-400"
                                                role="progressbar"
                                                aria-label="{{ $skill->skill?->display_name }} progress to level {{ $skill->level + 1 }}"
                                                aria-valuemin="0"
                                                aria-valuemax="{{ $skill->xpForNextLevel() }}"
                                                aria-valuenow="{{ $skill->xp }}"
                                            >
                                                <div class="ez-meter-fill" style="width: {{ $skill->progressPercent() }}%"></div>
                                            </div>
                                            <div class="mt-1 flex items-baseline justify-between gap-2 font-mono text-[10px] tabular-nums text-zinc-500">
                                                <span data-test="skill-xp"><span class="text-zinc-300">{{ number_format($skill->xp) }}</span> / {{ number_format($skill->xpForNextLevel()) }} XP</span>
                                                <span class="truncate">{{ number_format($skill->xpToNextLevel()) }} to LV{{ $skill->level + 1 }}</span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>


                        {{-- PACK (inventory) tab: pictures at a glance, tap one for the details --}}
                        <div x-show="tab === 'pack'" x-data="{ selected: null }" class="p-4">
                            <p class="mb-3 font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Inventory</p>
                            <div data-work-live="pack">
                                @if ($inventory->isEmpty())
                                    <div class="flex flex-col items-center justify-center py-12 text-center">
                                        <svg class="mb-3 h-8 w-8 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                        </svg>
                                        <p class="text-xs text-zinc-600">Pack is empty.</p>
                                        <p class="mt-1 text-xs text-zinc-700">Complete a city action to collect loot.</p>
                                    </div>
                                @else
                                    <ul class="grid grid-cols-4 gap-2" role="list" data-test="pack-grid">
                                        @foreach ($inventory as $inventoryItem)
                                            <li class="group relative">
                                                <button
                                                    type="button"
                                                    @click="selected = selected === {{ $inventoryItem->item->id }} ? null : {{ $inventoryItem->item->id }}"
                                                    :aria-pressed="(selected === {{ $inventoryItem->item->id }}).toString()"
                                                    :class="selected === {{ $inventoryItem->item->id }} ? 'ring-2 ring-emerald-400' : ''"
                                                    class="block w-full rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                                                    title="{{ $inventoryItem->item->name }}"
                                                    data-test="pack-item-{{ $inventoryItem->item->key }}"
                                                >
                                                    <x-zone.item-image :item="$inventoryItem->item" :quantity="$inventoryItem->quantity" class="aspect-square w-full" />
                                                    <span class="sr-only">{{ $inventoryItem->item->name }}, {{ $inventoryItem->quantity }} carried</span>
                                                </button>
                                                <span class="pointer-events-none absolute -top-7 left-1/2 z-10 hidden -translate-x-1/2 rounded-sm bg-black/90 px-1.5 py-0.5 font-mono text-[10px] whitespace-nowrap text-ez-ink pointer-fine:group-hover:block" aria-hidden="true">{{ $inventoryItem->item->name }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-3 text-center text-[11px] text-zinc-600" x-show="selected === null">Tap an item to read about it.</p>

                                    @foreach ($inventory as $inventoryItem)
                                        @php
                                            $item = $inventoryItem->item;
                                            $facts = array_values(array_filter([
                                                $item->hunger_restored ? 'Hunger -'.$item->hunger_restored : null,
                                                $item->fatigue_restored ? 'Fatigue -'.$item->fatigue_restored : null,
                                                $item->health_restored ? 'Health +'.$item->health_restored : null,
                                                ...$item->equipEffects(),
                                                $item->isWeapon() && ! $item->isWearable() ? 'Attack +'.$item->attack_bonus : null,
                                                $item->isSellable() ? 'Worth '.$item->base_price.' scrip' : null,
                                            ]));
                                        @endphp
                                        <div x-show="selected === {{ $item->id }}" x-cloak class="mt-3 rounded-lg border border-zinc-800 bg-zinc-900/60 p-3" data-test="pack-detail-{{ $item->key }}">
                                            <div class="flex items-start gap-3">
                                                <x-zone.item-image :item="$item" class="size-16" />
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-medium text-zinc-100">{{ $item->name }} <span class="font-mono text-zinc-500">×{{ $inventoryItem->quantity }}</span></p>
                                                    <p class="font-mono text-[10px] tracking-wider uppercase {{ $rarityText[$item->rarity->value] }}">{{ $item->rarity->label() }}@if ($item->is_quest_item) · Quest item @endif</p>
                                                    <p class="mt-1 text-xs text-zinc-400">{{ $item->description }}</p>
                                                </div>
                                            </div>
                                            @if ($facts !== [])
                                                <ul class="mt-2 flex flex-wrap gap-1 text-[11px]">
                                                    @foreach ($facts as $fact)
                                                        <li class="rounded-sm border border-zinc-700 px-1.5 py-0.5 text-zinc-300">{{ $fact }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @if ($item->isFood())
                                                    <form method="POST" action="{{ route('food.eat') }}" data-quick-action>
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <flux:button type="submit" size="sm">Eat one</flux:button>
                                                    </form>
                                                @elseif ($item->isMedical())
                                                    <form method="POST" action="{{ route('medical.use') }}" data-quick-action>
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <flux:button type="submit" size="sm">Use one</flux:button>
                                                    </form>
                                                @endif
                                                @if ($item->isWearable())
                                                    <form method="POST" action="{{ route('gear.equip') }}" data-quick-action>
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <flux:button type="submit" size="sm" variant="primary">Wear it</flux:button>
                                                    </form>
                                                @endif
                                                <flux:button type="button" size="sm" variant="ghost" @click="selected = null">Close</flux:button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        {{-- GEAR tab: what the survivor wears, and what it does --}}
                        <div x-show="tab === 'gear'" class="space-y-4 p-4" data-work-live="gear">
                            <div>
                                <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Worn gear</p>
                                <p class="mt-1 text-xs text-zinc-500">Wear one item per slot. Tools speed up their skill's work, and held weapons hit harder in fights. Worn items leave your pack until you take them off.</p>
                            </div>

                            @php $slots = collect($gear['slots'])->keyBy(fn (array $entry): string => $entry['slot']->value); @endphp
                            <div class="grid grid-cols-3 gap-2" data-test="gear-slots">
                                @foreach ([null, 'head', null, 'right_arm', 'body', 'left_arm', null, 'feet', null] as $slotKey)
                                    @if ($slotKey === null)
                                        <div aria-hidden="true"></div>
                                    @else
                                        @php $entry = $slots[$slotKey]; @endphp
                                        @if ($entry['item'])
                                            <form method="POST" action="{{ route('gear.unequip') }}" data-quick-action>
                                                @csrf
                                                <input type="hidden" name="slot" value="{{ $slotKey }}">
                                                <button type="submit" class="group flex w-full flex-col items-center gap-1 rounded-lg border border-emerald-700/50 bg-emerald-950/30 p-1.5 text-center transition hover:border-red-400/60" title="Take off {{ $entry['item']->name }}" data-test="gear-slot-{{ $slotKey }}">
                                                    <x-zone.item-image :item="$entry['item']" class="aspect-square w-full" />
                                                    <span class="w-full truncate text-[10px] text-zinc-200">{{ $entry['item']->name }}</span>
                                                    <span class="font-mono text-[9px] tracking-wider text-zinc-500 uppercase group-hover:text-red-300">{{ $entry['slot']->label() }} · take off</span>
                                                </button>
                                            </form>
                                        @else
                                            <div class="flex aspect-[3/4] flex-col items-center justify-center rounded-lg border border-dashed border-zinc-700 p-1.5 text-center" data-test="gear-slot-{{ $slotKey }}">
                                                <span class="font-mono text-[9px] tracking-wider text-zinc-600 uppercase">{{ $entry['slot']->label() }}</span>
                                                <span class="mt-1 text-[10px] text-zinc-700">Empty</span>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>

                            <div class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-3">
                                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600">What your gear does</p>
                                @if ($gear['effects'] === [])
                                    <p class="mt-1 text-xs text-zinc-500">Nothing yet. Put on boots, tools or a mask to work faster and stay safer.</p>
                                @else
                                    <ul class="mt-1 space-y-0.5 text-xs text-emerald-300" data-test="gear-effects">
                                        @foreach ($gear['effects'] as $effect)
                                            <li>{{ $effect }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600">Wearable gear in your pack</p>
                                @forelse ($gear['wearables'] as $wearable)
                                    <div class="flex items-center gap-3 rounded-lg border border-zinc-800 bg-zinc-900/50 p-2">
                                        <x-zone.item-image :item="$wearable['item']" :quantity="$wearable['owned']" class="size-12" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-zinc-200">{{ $wearable['item']->name }}</p>
                                            <p class="text-[11px] text-zinc-500">{{ implode(' · ', $wearable['item']->equipEffects()) ?: 'No bonus' }}</p>
                                        </div>
                                        <div class="flex shrink-0 flex-col gap-1">
                                            @foreach ($wearable['slots'] as $slot)
                                                <form method="POST" action="{{ route('gear.equip') }}" data-quick-action>
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $wearable['item']->id }}">
                                                    <input type="hidden" name="slot" value="{{ $slot->value }}">
                                                    <flux:button type="submit" size="xs" variant="primary" data-test="equip-{{ $wearable['item']->key }}-{{ $slot->value }}">
                                                        {{ count($wearable['slots']) > 1 ? $slot->label() : 'Wear' }}
                                                    </flux:button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <p class="rounded-lg border border-dashed border-zinc-800 px-3 py-4 text-center text-xs text-zinc-600">No wearable gear in your pack. Bazaars at trade posts sell boots, tools and masks.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- BAZAAR tab: buy and sell for scrip at a trade post --}}
                        <div x-show="tab === 'bazaar'" class="space-y-4 p-4" data-work-live="bazaar">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Bazaar</p>
                                    <p class="mt-1 text-xs text-zinc-500">Sell goods for scrip and buy supplies. Each trade post keeps its own stock, and other survivors usually pay more than the bazaar does.</p>
                                </div>
                                <x-zone.scrip :amount="$bazaar['scrip']" class="shrink-0 rounded-sm border border-amber-400/40 px-2 py-1 text-sm text-amber-200" data-test="bazaar-scrip" />
                            </div>

                            @if ($bazaar['restriction'])
                                <p class="rounded-lg border border-amber-800/40 bg-amber-900/20 px-3 py-2 text-xs text-amber-400/90" data-test="bazaar-restriction">{{ $bazaar['restriction'] }}</p>
                            @else
                                <p class="text-[11px] text-zinc-500" data-test="bazaar-barter">
                                    Your Barter skill: sales pay +{{ $bazaar['barter_sell_bonus_percent'] }}%, purchases cost -{{ $bazaar['barter_buy_discount_percent'] }}%. Selling a stack at once pays a little extra.
                                </p>

                                <div class="space-y-2">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600">Buy</p>
                                    @foreach ($bazaar['stock'] as $offer)
                                        <form
                                            method="POST"
                                            action="{{ route('bazaar.buy') }}"
                                            x-data="{ quantity: 1, price: {{ $offer['price'] }}, scrip: {{ $bazaar['scrip'] }} }"
                                            class="flex items-center gap-2.5 rounded-lg border border-zinc-800 bg-zinc-900/50 p-2"
                                            data-quick-action
                                        >
                                            @csrf
                                            <input type="hidden" name="item_id" value="{{ $offer['item']->id }}">
                                            <x-zone.item-image :item="$offer['item']" class="size-11" />
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-200">{{ $offer['item']->name }}</p>
                                                <p class="truncate text-[11px] text-zinc-500">{{ implode(' · ', $offer['item']->equipEffects()) ?: collect(['Hunger -'.$offer['item']->hunger_restored => $offer['item']->hunger_restored, 'Health +'.$offer['item']->health_restored => $offer['item']->health_restored])->filter()->keys()->implode(' · ') }}</p>
                                                <x-zone.scrip :amount="$offer['price']" class="text-[11px] text-amber-200" />
                                            </div>
                                            <input type="number" name="quantity" min="1" max="99" x-model.number="quantity" class="w-12 shrink-0 rounded-sm border border-zinc-700 bg-zinc-950 px-1.5 py-1 text-right font-mono text-xs text-zinc-200" aria-label="How many {{ $offer['item']->name }} to buy">
                                            <flux:button type="submit" size="xs" variant="primary" x-bind:disabled="quantity < 1 || quantity * price > scrip" data-test="buy-{{ $offer['item']->key }}">Buy</flux:button>
                                        </form>
                                    @endforeach
                                </div>

                                <div class="space-y-2">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600">Sell</p>
                                    @forelse ($bazaar['sellable'] as $goods)
                                        <form
                                            method="POST"
                                            action="{{ route('bazaar.sell') }}"
                                            x-data="{
                                                quantity: 1,
                                                get total() {
                                                    const shop = @js($shop);
                                                    const bulk = Math.min(shop.maxBulk, Math.max(0, this.quantity - 1) * shop.bulkTenths / 10);

                                                    return Math.floor({{ $goods['item']->base_price }} * this.quantity * shop.sellRatio / 100 * (100 + shop.barterBonus + bulk) / 100);
                                                },
                                            }"
                                            class="flex items-center gap-2.5 rounded-lg border border-zinc-800 bg-zinc-900/50 p-2"
                                            data-quick-action
                                        >
                                            @csrf
                                            <input type="hidden" name="item_id" value="{{ $goods['item']->id }}">
                                            <x-zone.item-image :item="$goods['item']" :quantity="$goods['owned']" class="size-11" />
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-200">{{ $goods['item']->name }}</p>
                                                <p class="text-[11px] text-zinc-500">{{ $goods['unit_price'] }} each · you get <span class="font-mono text-amber-200" x-text="total">{{ $goods['unit_price'] }}</span> scrip</p>
                                            </div>
                                            <input type="number" name="quantity" min="1" max="{{ $goods['owned'] }}" x-model.number="quantity" class="w-12 shrink-0 rounded-sm border border-zinc-700 bg-zinc-950 px-1.5 py-1 text-right font-mono text-xs text-zinc-200" aria-label="How many {{ $goods['item']->name }} to sell">
                                            <flux:button type="submit" size="xs" x-bind:disabled="quantity < 1 || quantity > {{ $goods['owned'] }}" data-test="sell-{{ $goods['item']->key }}">Sell</flux:button>
                                        </form>
                                    @empty
                                        <p class="rounded-lg border border-dashed border-zinc-800 px-3 py-4 text-center text-xs text-zinc-600">Nothing in your pack the bazaar will buy. Quest items are never for sale.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                        {{-- RADIO tab --}}
                        <div x-show="tab === 'radio'" class="h-full">
                            <livewire:radio-chat />
                        </div>

                        {{-- TRADE tab --}}
                        <div x-show="tab === 'trade'" class="h-full">
                            <livewire:trade-board />
                        </div>

                        {{-- CONTRACTS / JOBS tab --}}
                        <div x-show="tab === 'contracts'" class="space-y-4 p-4">
                            <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Jobs</p>

                            {{-- Active quests --}}
                            @if (count($jobs['active']) > 0)
                                <div class="space-y-2">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-emerald-600/70">Active</p>
                                    @foreach ($jobs['active'] as $entry)
                                        @php
                                            $isRepeatable = $entry['quest']->is_repeatable;
                                            $isStory = $entry['quest']->quest_type === 'story';
                                            $stepCount = $entry['quest']->steps->count();
                                        @endphp
                                        <div class="rounded-lg border border-emerald-800/40 bg-emerald-900/20 p-3">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-medium text-zinc-200">{{ $entry['quest']->name }}</p>
                                                <div class="flex shrink-0 items-center gap-1">
                                                    @if ($isStory)
                                                        <span class="rounded-full border border-amber-700/50 bg-amber-900/40 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-amber-400">Story</span>
                                                    @elseif ($isRepeatable)
                                                        <span class="rounded-full border border-sky-700/50 bg-sky-900/40 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-sky-400">Run #{{ $entry['userQuest']->completion_count + 1 }}</span>
                                                    @endif
                                                    <span class="rounded-full border border-emerald-700/50 bg-emerald-900/60 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-emerald-400">Active</span>
                                                </div>
                                            </div>
                                            @if ($entry['current_step'] !== null)
                                                <p class="mt-1.5 text-xs text-zinc-500">
                                                    @if ($stepCount > 2)
                                                        <span class="font-mono text-[10px] text-zinc-600">Step {{ $entry['userQuest']->current_step_index + 1 }}/{{ $stepCount }} ·</span>
                                                    @endif
                                                    Objective: <span class="text-zinc-400">{{ $entry['current_step']->action_label }}</span> in <span class="text-zinc-400">{{ $entry['current_step']->city?->city }}</span>
                                                </p>
                                                @if ($entry['action_progress'] !== null)
                                                    <p class="mt-1 text-xs text-zinc-600">
                                                        Task: <span class="text-sky-400/80">{{ $entry['action_progress']['action']?->label }} {{ $entry['action_progress']['progress'] }}/{{ $entry['action_progress']['count'] }}</span>
                                                    </p>
                                                @endif
                                                @if (count($entry['requirements']) > 0)
                                                    <p class="mt-1 text-xs text-zinc-600">
                                                        Requires: <span class="text-amber-500/80">{{ collect($entry['requirements'])->map(fn ($requirement) => $requirement['item']?->name.' ×'.$requirement['quantity'])->implode(', ') }}</span>
                                                    </p>
                                                @endif
                                            @endif
                                            @if (count($entry['userQuest']->notes ?? []) > 0)
                                                <div class="mt-2 space-y-1 border-t border-zinc-800 pt-2">
                                                    @foreach ($entry['userQuest']->notes as $note)
                                                        <p class="font-mono text-[10px] italic leading-relaxed text-zinc-500">"{{ $note }}"</p>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Available quests --}}
                            @if (count($jobs['available']) > 0)
                                <div class="space-y-2">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600">Available</p>
                                    @foreach ($jobs['available'] as $item)
                                        @php $quest = $item['quest']; @endphp
                                        <div class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-3">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-medium text-zinc-300">{{ $quest->name }}</p>
                                                <div class="flex shrink-0 items-center gap-1">
                                                    @if ($item['type'] === 'story')
                                                        <span class="rounded-full border border-amber-700/50 bg-amber-900/40 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-amber-500">Story</span>
                                                    @elseif ($item['type'] === 'repeatable_job')
                                                        <span class="rounded-full border border-sky-700/50 bg-sky-900/40 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-sky-400">Run #{{ $item['completion_count'] + 1 }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <p class="mt-0.5 text-xs text-zinc-600">{{ $quest->description }}</p>
                                            @if ($quest->rewardSkill || $quest->rewardItem)
                                                <p class="mt-1.5 text-xs text-zinc-700">
                                                    Reward:
                                                    @if ($quest->rewardSkill && $quest->reward_xp_amount)
                                                        <span class="text-emerald-600/70">+{{ number_format($quest->reward_xp_amount) }} {{ $quest->rewardSkill->display_name }} XP</span>
                                                    @endif
                                                    @if ($quest->rewardItem)
                                                        <span class="text-emerald-600/70">+{{ $quest->reward_item_quantity }}× {{ $quest->rewardItem->name }}</span>
                                                    @endif
                                                </p>
                                            @endif
                                            <form method="POST" action="{{ route('quest.accept', $quest) }}" class="mt-2">
                                                @csrf
                                                <flux:button type="submit" variant="primary" size="sm" class="w-full">
                                                    @if ($item['type'] === 'story')
                                                        Start quest
                                                    @elseif ($item['type'] === 'repeatable_job')
                                                        Start run #{{ $item['completion_count'] + 1 }}
                                                    @else
                                                        Accept job
                                                    @endif
                                                </flux:button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Completed quests --}}
                            @if (count($jobs['completed']) > 0)
                                <div class="space-y-2">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-700">Completed</p>
                                    @foreach ($jobs['completed'] as $userQuest)
                                        <div class="rounded-lg border border-zinc-800/50 bg-zinc-900/30 px-3 py-2 opacity-60">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-xs font-medium text-zinc-400">{{ $userQuest->quest->name }}</p>
                                                <div class="flex items-center gap-1">
                                                    @if ($userQuest->quest->quest_type === 'story')
                                                        <span class="shrink-0 rounded-full bg-amber-900/30 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-amber-700">Story</span>
                                                    @endif
                                                    <span class="shrink-0 rounded-full bg-zinc-800 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-zinc-500">Done</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Empty state --}}
                            @if (count($jobs['active']) === 0 && count($jobs['available']) === 0 && count($jobs['completed']) === 0)
                                <div class="flex flex-col items-center justify-center py-10 text-center">
                                    <p class="font-mono text-xs text-zinc-600">No jobs available right now.</p>
                                </div>
                            @endif
                        </div>

                        {{-- PREMIUM (cosmetics) tab --}}
                        <div x-show="tab === 'premium'" class="space-y-4 p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Cosmetics</p>
                                <span class="rounded border px-2 py-0.5 font-mono text-[10px] {{ $player->hasPremiumEntitlement() ? 'border-emerald-800/50 bg-emerald-900/40 text-emerald-400' : 'border-zinc-800 bg-zinc-900 text-zinc-600' }}">
                                    {{ $player->hasPremiumEntitlement() ? 'PREMIUM' : 'LOCKED' }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-600">Pure style upgrades. No stat boosts, no yield bonuses, no combat modifiers.</p>

                            @foreach ($cosmetics as $type => $options)
                                <div class="space-y-2">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-700">{{ Str::headline($type) }}</p>
                                    @foreach ($options as $cosmetic)
                                        <form method="POST" action="{{ route('cosmetics.store') }}" class="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2.5 transition-colors hover:border-zinc-700">
                                            @csrf
                                            <input type="hidden" name="premium_cosmetic_id" value="{{ $cosmetic->id }}">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-zinc-200">{{ $cosmetic->name }}</p>
                                                <p class="mt-0.5 text-xs text-zinc-600">{{ $cosmetic->gameplay_bonus }}</p>
                                            </div>
                                            <flux:button type="submit" size="sm" variant="ghost" :disabled="! $player->hasPremiumEntitlement()" class="ml-2 shrink-0">
                                                Equip
                                            </flux:button>
                                        </form>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        {{-- ADMIN tab (moderators + admins only) --}}
                        @if ($player->isModerator() || $player->isAdmin())
                            <div x-show="tab === 'admin'" class="space-y-5 p-4">
                                <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Admin Console</p>

                                <div class="space-y-3">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-700">Moderation</p>
                                    <p class="text-xs text-zinc-600">Apply a temporary mute for abusive radio traffic.</p>
                                    <form method="POST" action="{{ route('moderation.mutes.store') }}" class="space-y-3 rounded-lg border border-zinc-800 bg-zinc-900/50 p-3">
                                        @csrf
                                        <flux:input name="target_user_id" label="Target user ID" type="number" />
                                        <flux:input name="duration_minutes" label="Duration (minutes)" type="number" value="60" />
                                        <flux:input name="reason" label="Reason" type="text" />
                                        <flux:button type="submit" variant="primary" class="w-full">Apply mute</flux:button>
                                    </form>
                                </div>

                                @if ($player->isAdmin())
                                    <div class="space-y-3">
                                        <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-700">Content Management</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <a href="{{ route('admin.quests') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">Jobs / Quests</a>
                                            <a href="{{ route('admin.city-actions') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">City Actions</a>
                                            <a href="{{ route('admin.items') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">Items</a>
                                            <a href="{{ route('admin.enemies') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">Enemies</a>
                                            <a href="{{ route('admin.locations') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">Locations</a>
                                            <a href="{{ route('admin.travel-routes') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">Travel Routes</a>
                                            <a href="{{ route('admin.trouble-types') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">Trouble</a>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-700">Role Management</p>
                                        <p class="text-xs text-zinc-600">Admins alone can change another user role. Every change is audited.</p>
                                        <form method="POST" action="{{ route('admin.roles.update') }}" class="space-y-3 rounded-lg border border-zinc-800 bg-zinc-900/50 p-3">
                                            @csrf
                                            <flux:input name="target_user_id" label="Target user ID" type="number" />
                                            <flux:select name="role_key" label="Role">
                                                <option value="user">User</option>
                                                <option value="premium">Premium</option>
                                                <option value="moderator">Moderator</option>
                                                <option value="admin">Admin</option>
                                            </flux:select>
                                            <flux:button type="submit" variant="primary" class="w-full">Update role</flux:button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                    {{-- end tab content area --}}

                    {{-- Tab navigation bar --}}
                    <nav
                        role="tablist"
                        aria-label="PDA navigation"
                        class="relative z-10 flex shrink-0 overflow-x-auto border-t border-zinc-700 bg-zinc-900 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        @keydown.arrow-right.prevent="const tabs = [...$el.querySelectorAll('[role=tab]')]; const next = tabs[(tabs.indexOf(document.activeElement) + 1) % tabs.length]; next.click(); next.focus()"
                        @keydown.arrow-left.prevent="const tabs = [...$el.querySelectorAll('[role=tab]')]; const previous = tabs[(tabs.indexOf(document.activeElement) - 1 + tabs.length) % tabs.length]; previous.click(); previous.focus()"
                    >
                        <div class="flex min-w-max w-full">
                            @foreach ($tabs as $key => [$label, $name, $iconPath])
                                <button role="tab" type="button" @click="tab = '{{ $key }}'" :aria-selected="(tab === '{{ $key }}').toString()" :tabindex="tab === '{{ $key }}' ? 0 : -1" class="relative flex flex-1 flex-col items-center gap-0.5 px-1.5 py-2 sm:px-2 font-mono text-[10px] uppercase tracking-wide transition-colors min-h-11 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === '{{ $key }}' ? 'text-emerald-400' : 'text-zinc-500 hover:text-zinc-300'" aria-label="{{ $name }}" data-test="pda-tab-{{ $key }}">
                                    <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                                    <span>{{ $label }}</span>
                                    @if (array_key_exists($key, $alerts))
                                        <span x-show="alerts.{{ $key }} > 0" @if ($alerts[$key] === 0) x-cloak @endif class="absolute top-1 right-[22%] size-2 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.9)]" data-test="pda-tab-alert-{{ $key }}"></span>
                                        <span class="sr-only" x-show="alerts.{{ $key }} > 0" x-text="`, ${alerts.{{ $key }}} new`"></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </nav>

                </div>
                {{-- end screen --}}

            </div>
            {{-- end screen bezel --}}

            {{-- Device bottom controls (decorative) --}}
            <div class="mt-2.5 flex items-center justify-center gap-3 px-4" aria-hidden="true">
                <div class="h-2 w-14 rounded-full bg-black/30 shadow-inner"></div>
                <div class="flex h-5 w-5 items-center justify-center rounded-full border border-zinc-500/30 bg-black/30 shadow-inner">
                    <div class="h-1.5 w-1.5 rounded-full bg-zinc-700/60"></div>
                </div>
                <div class="h-2 w-14 rounded-full bg-black/30 shadow-inner"></div>
            </div>

        </div>
        {{-- end outer device body --}}

    </div>
    {{-- end PDA device --}}

</div>
{{-- end Alpine wrapper --}}
