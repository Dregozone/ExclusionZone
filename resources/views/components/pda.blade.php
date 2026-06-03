@props([
    'neighbors',
    'skills',
    'inventory',
    'cosmetics',
    'player',
    'city',
    'country',
    'mapData',
    'currentCityId',
    'canPerformCityActions',
    'cityActionRestriction',
    'jobs',
])

<div
    x-data="{
        open: sessionStorage.getItem('pda_open') === 'true',
        tab: sessionStorage.getItem('pda_tab') || 'map',
        tabNames: {
            map: 'World Map',
            travel: 'Travel Routes',
            skills: 'Skill Tracker',
            pack: 'Inventory',
            radio: 'Radio Comms',
            trade: 'Trade Board',
            contracts: 'Contracts',
            premium: 'Cosmetics',
            admin: 'Admin Console',
        },
        init() {
            this.$watch('open', v => sessionStorage.setItem('pda_open', v));
            this.$watch('tab', v => sessionStorage.setItem('pda_tab', v));
        },
    }"
    @keydown.escape.window="open = false"
>
    {{-- Floating toggle button --}}
    <button
        @click="open = !open"
        type="button"
        :aria-expanded="open.toString()"
        aria-controls="pda-device"
        class="fixed bottom-6 right-6 z-30 flex items-center gap-2.5 rounded-xl border px-4 py-3 font-mono text-sm tracking-widest shadow-lg transition-all select-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
        :class="open
            ? 'bg-zinc-700 border-emerald-500/60 text-emerald-400 shadow-emerald-900/20'
            : 'bg-zinc-800 border-zinc-600 text-zinc-300 hover:border-emerald-500/40 hover:text-emerald-400'"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3v.008h-3V9.75Z" />
        </svg>
        <span x-text="open ? '[ CLOSE PDA ]' : '[ OPEN PDA ]'"></span>
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
        class="fixed inset-0 z-40 bg-black/30 backdrop-blur-[1px]"
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
        class="fixed inset-x-2 top-2 bottom-2 z-50 flex flex-col sm:inset-auto sm:left-1/2 sm:top-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:h-[85dvh] sm:w-auto sm:aspect-[11/20] {{-- sm:max-w-2xl --}}"
    >
        {{-- Outer device body (metallic grey casing) --}}
        <div class="relative flex min-h-0 flex-1 flex-col overflow-hidden rounded-[2rem] bg-linear-to-b from-zinc-300 via-zinc-400 to-zinc-600 p-3.5 pb-4 pt-2.5 shadow-[0_25px_80px_rgba(0,0,0,0.9),inset_0_1px_2px_rgba(255,255,255,0.5),inset_0_-2px_4px_rgba(0,0,0,0.3)] ring-1 ring-white/10">

            {{-- Corner screws (decorative) --}}
            <div class="absolute top-2.5 left-2.5 h-3 w-3 rounded-full bg-linear-to-br from-zinc-500 to-zinc-700 shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>
            <div class="absolute top-2.5 right-2.5 h-3 w-3 rounded-full bg-linear-to-br from-zinc-500 to-zinc-700 shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>
            <div class="absolute bottom-3 left-2.5 h-3 w-3 rounded-full bg-linear-to-br from-zinc-500 to-zinc-700 shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>
            <div class="absolute bottom-3 right-2.5 h-3 w-3 rounded-full bg-linear-to-br from-zinc-500 to-zinc-700 shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_0.5px_0_rgba(255,255,255,0.2)]" aria-hidden="true"></div>

            {{-- Device header strip --}}
            <div class="mb-2 flex items-center justify-between px-4" aria-hidden="true">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.85)]"></div>
                    <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-zinc-600">EX-PDA · MK VII</span>
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
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-[1.1rem] bg-zinc-950">

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

                        {{-- MAP tab (x-if so the SVG initialises with correct container width) --}}
                        <template x-if="tab === 'map'">
                            <div class="p-3">
                                <x-geo-map
                                    :map-data="$mapData"
                                    :current-city-id="$currentCityId"
                                    :compact="true"
                                    :dark="true"
                                />
                            </div>
                        </template>

                        {{-- TRAVEL tab --}}
                        <div x-show="tab === 'travel'" class="space-y-3 p-4">
                            <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Travel Routes</p>
                            <p class="text-xs text-zinc-500">One move is one turn. Each destination refreshes the city action list.</p>

                            @if (! $canPerformCityActions && $cityActionRestriction)
                                <p class="rounded-lg border border-amber-800/40 bg-amber-900/20 px-3 py-2 text-xs text-amber-400/80">
                                    {{ $cityActionRestriction }}
                                </p>
                            @endif

                            <div class="space-y-2">
                                @forelse ($neighbors as $neighbor)
                                    <form method="POST" action="{{ route('travel.store') }}" class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-3 transition-colors hover:border-zinc-700">
                                        @csrf
                                        <input type="hidden" name="city_id" value="{{ $neighbor->id }}">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-zinc-200">{{ $neighbor->city }}</p>
                                                <p class="mt-0.5 text-xs text-zinc-500">{{ $neighbor->country?->country }} · {{ $neighbor->baseline_loot_tier }} loot</p>
                                            </div>
                                            <flux:button type="submit" size="sm" variant="primary" :disabled="! $canPerformCityActions" data-test="travel-{{ Str::slug($neighbor->city) }}">
                                                {{ $canPerformCityActions ? 'Travel' : 'N/A' }}
                                            </flux:button>
                                        </div>
                                    </form>
                                @empty
                                    <p class="rounded-lg border border-dashed border-zinc-800 px-3 py-6 text-center text-xs text-zinc-600">
                                        No linked travel routes from this city.
                                    </p>
                                @endforelse
                            </div>
                        </div>

                        {{-- SKILLS tab --}}
                        <div x-show="tab === 'skills'" class="space-y-2 p-4">
                            <p class="mb-3 font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Skill Tracker</p>
                            @foreach ($skills as $skill)
                                <div class="rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-zinc-200">{{ $skill->skill?->display_name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-zinc-600">{{ $skill->skill?->description }}</p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="font-mono text-sm font-semibold text-emerald-400">LV{{ $skill->level }}</p>
                                            <p class="font-mono text-[10px] text-zinc-600">{{ $skill->xp }} XP</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- PACK (inventory) tab --}}
                        <div x-show="tab === 'pack'" class="space-y-2 p-4">
                            <p class="mb-3 font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Inventory</p>
                            @forelse ($inventory as $inventoryItem)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-zinc-200">{{ $inventoryItem->item?->name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-zinc-600">{{ $inventoryItem->item?->description }}</p>
                                    </div>
                                    <span class="ml-3 shrink-0 font-mono text-sm font-semibold text-emerald-400">×{{ $inventoryItem->quantity }}</span>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-12 text-center">
                                    <svg class="mb-3 h-8 w-8 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                    </svg>
                                    <p class="text-xs text-zinc-600">Pack is empty.</p>
                                    <p class="mt-1 text-xs text-zinc-700">Complete a city action to collect loot.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- RADIO tab (placeholder) --}}
                        <div x-show="tab === 'radio'" class="flex min-h-[300px] flex-col items-center justify-center p-6 text-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-zinc-800 bg-zinc-900">
                                <svg class="h-8 w-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                                </svg>
                            </div>
                            <p class="mb-1 font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Radio Comms</p>
                            <p class="mb-4 font-mono text-xs text-emerald-500/50">EXCLUSION NET · STANDBY</p>
                            <div class="w-full space-y-1 rounded-lg border border-zinc-800 bg-zinc-900/50 px-4 py-3">
                                <p class="font-mono text-xs text-zinc-500">Channel 7 · No transmissions detected.</p>
                                <p class="font-mono text-xs text-zinc-700">System pending activation.</p>
                            </div>
                        </div>

                        {{-- TRADE tab (placeholder) --}}
                        <div x-show="tab === 'trade'" class="flex min-h-[300px] flex-col items-center justify-center p-6 text-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-zinc-800 bg-zinc-900">
                                <svg class="h-8 w-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                </svg>
                            </div>
                            <p class="mb-1 font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Trade Board</p>
                            <p class="mb-4 font-mono text-xs text-amber-500/50">MARKET OFFLINE</p>
                            <div class="w-full space-y-1 rounded-lg border border-zinc-800 bg-zinc-900/50 px-4 py-3">
                                <p class="font-mono text-xs text-zinc-500">Market connection unavailable.</p>
                                <p class="font-mono text-xs text-zinc-700">Locate a trade post to access the exchange.</p>
                            </div>
                        </div>

                        {{-- CONTRACTS / JOBS tab --}}
                        <div x-show="tab === 'contracts'" class="space-y-4 p-4">
                            <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Jobs</p>

                            {{-- Active quests --}}
                            @if (count($jobs['active']) > 0)
                                <div class="space-y-2">
                                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-emerald-600/70">Active</p>
                                    @foreach ($jobs['active'] as $entry)
                                        <div class="rounded-lg border border-emerald-800/40 bg-emerald-900/20 p-3">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-medium text-zinc-200">{{ $entry['quest']->name }}</p>
                                                <span class="shrink-0 rounded-full border border-emerald-700/50 bg-emerald-900/60 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-emerald-400">Active</span>
                                            </div>
                                            @if ($entry['current_step'] !== null)
                                                <p class="mt-1.5 text-xs text-zinc-500">
                                                    Objective: <span class="text-zinc-400">{{ $entry['current_step']->action_label }}</span> in <span class="text-zinc-400">{{ $entry['current_step']->city?->city }}</span>
                                                </p>
                                                @if ($entry['current_step']->required_item_id !== null)
                                                    <p class="mt-1 text-xs text-zinc-600">
                                                        Requires: <span class="text-amber-500/80">{{ $entry['current_step']->requiredItem?->name }} ×{{ $entry['current_step']->required_item_quantity }}</span>
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
                                    @foreach ($jobs['available'] as $quest)
                                        <div class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-3">
                                            <p class="text-sm font-medium text-zinc-300">{{ $quest->name }}</p>
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
                                                <flux:button type="submit" variant="primary" size="sm" class="w-full">Accept job</flux:button>
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
                                                <span class="shrink-0 rounded-full bg-zinc-800 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-zinc-500">Done</span>
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
                                            <a href="{{ route('admin.locations') }}" class="flex items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-zinc-200">Locations</a>
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
                        class="flex shrink-0 overflow-x-auto border-t border-zinc-800/80 bg-zinc-900/50"
                    >
                        <div class="flex min-w-max w-full">

                            <button role="tab" type="button" @click="tab = 'map'" :aria-selected="(tab === 'map').toString()" :tabindex="tab === 'map' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'map' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Map">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" /></svg>
                                <span>Map</span>
                            </button>

                            <button role="tab" type="button" @click="tab = 'travel'" :aria-selected="(tab === 'travel').toString()" :tabindex="tab === 'travel' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'travel' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Travel routes">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9l3 3m0 0-3 3m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                <span>Travel</span>
                            </button>

                            <button role="tab" type="button" @click="tab = 'skills'" :aria-selected="(tab === 'skills').toString()" :tabindex="tab === 'skills' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'skills' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Skill tracker">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
                                <span>Skills</span>
                            </button>

                            <button role="tab" type="button" @click="tab = 'pack'" :aria-selected="(tab === 'pack').toString()" :tabindex="tab === 'pack' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'pack' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Inventory">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                                <span>Pack</span>
                            </button>

                            <button role="tab" type="button" @click="tab = 'radio'" :aria-selected="(tab === 'radio').toString()" :tabindex="tab === 'radio' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'radio' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Radio comms">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" /></svg>
                                <span>Radio</span>
                            </button>

                            <button role="tab" type="button" @click="tab = 'trade'" :aria-selected="(tab === 'trade').toString()" :tabindex="tab === 'trade' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'trade' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Trade board">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                <span>Trade</span>
                            </button>

                            <button role="tab" type="button" @click="tab = 'contracts'" :aria-selected="(tab === 'contracts').toString()" :tabindex="tab === 'contracts' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'contracts' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Contracts">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                                <span>Jobs</span>
                            </button>

                            <button role="tab" type="button" @click="tab = 'premium'" :aria-selected="(tab === 'premium').toString()" :tabindex="tab === 'premium' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'premium' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Cosmetics">
                                <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                                <span>Premium</span>
                            </button>

                            @if ($player->isModerator() || $player->isAdmin())
                                <button role="tab" type="button" @click="tab = 'admin'" :aria-selected="(tab === 'admin').toString()" :tabindex="tab === 'admin' ? 0 : -1" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2 font-mono text-[9px] uppercase tracking-wide transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-emerald-500" :class="tab === 'admin' ? 'text-emerald-400' : 'text-zinc-600 hover:text-zinc-400'" aria-label="Admin console">
                                    <svg class="h-[14px] w-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                    <span>Admin</span>
                                </button>
                            @endif

                        </div>
                    </nav>

                </div>
                {{-- end screen --}}

            </div>
            {{-- end screen bezel --}}

            {{-- Device bottom controls (decorative) --}}
            <div class="mt-2.5 flex items-center justify-center gap-3 px-4" aria-hidden="true">
                <div class="h-2 w-14 rounded-full bg-zinc-600/60 shadow-inner"></div>
                <div class="flex h-5 w-5 items-center justify-center rounded-full border border-zinc-500/30 bg-zinc-600/60 shadow-inner">
                    <div class="h-1.5 w-1.5 rounded-full bg-zinc-700/60"></div>
                </div>
                <div class="h-2 w-14 rounded-full bg-zinc-600/60 shadow-inner"></div>
            </div>

        </div>
        {{-- end outer device body --}}

    </div>
    {{-- end PDA device --}}

</div>
{{-- end Alpine wrapper --}}
