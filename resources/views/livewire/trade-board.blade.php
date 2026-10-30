<div class="flex h-full min-h-[300px] flex-col">
    <div class="flex shrink-0 items-center gap-2 border-b border-zinc-800/80 px-4 py-2">
        <div @class([
            'h-1.5 w-1.5 rounded-full',
            'bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.85)]' => $this->isAtTradePost,
            'bg-amber-500/60' => ! $this->isAtTradePost,
        ]) aria-hidden="true"></div>
        <p class="truncate font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-500">
            {{ $this->isAtTradePost ? 'Trade Post · '.$this->city->city : 'Market Offline' }}
        </p>
    </div>

    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4" aria-live="polite">
        @if (! $this->canTrade)
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <p class="font-mono text-xs text-zinc-500">{{ auth()->user()->denialReasonForTask('trade_create') }}</p>
            </div>
        @elseif ($this->openTrade)
            @php
                $trade = $this->openTrade;
                $partner = $trade->isInitiator(auth()->user()) ? $trade->recipient : $trade->initiator;
                $iHaveCommitted = $trade->hasCommitted(auth()->user());
                $partnerHasCommitted = $trade->hasCommitted($partner);
            @endphp

            <div class="flex items-center gap-2">
                <flux:avatar :name="$partner->name" color="auto" size="xs" circle class="shrink-0" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-zinc-200">Trading with {{ $partner->name }}</p>
                    <p class="font-mono text-[10px] uppercase tracking-wide text-zinc-600">{{ $trade->city?->city }} trade post</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                @foreach ([
                    ['label' => 'Your offer', 'items' => $this->table['mine'], 'committed' => $iHaveCommitted, 'removable' => true],
                    ['label' => 'Their offer', 'items' => $this->table['theirs'], 'committed' => $partnerHasCommitted, 'removable' => false],
                ] as $side)
                    <div @class([
                        'flex min-h-[110px] flex-col rounded-lg border bg-zinc-900/50 p-2',
                        'border-emerald-600/60' => $side['committed'],
                        'border-zinc-800' => ! $side['committed'],
                    ])>
                        <div class="mb-1.5 flex items-center justify-between gap-1">
                            <p class="truncate font-mono text-[10px] uppercase tracking-wide text-zinc-500">{{ $side['label'] }}</p>
                            <span @class([
                                'shrink-0 rounded px-1 font-mono text-[9px] uppercase',
                                'bg-emerald-900/50 text-emerald-400' => $side['committed'],
                                'bg-zinc-800 text-zinc-500' => ! $side['committed'],
                            ])>{{ $side['committed'] ? 'Committed' : 'Deciding' }}</span>
                        </div>
                        <ul class="space-y-1">
                            @forelse ($side['items'] as $tradeItem)
                                <li wire:key="trade-item-{{ $tradeItem->id }}" class="flex items-center justify-between gap-1 text-xs">
                                    <span class="truncate text-zinc-300">{{ $tradeItem->item?->name }}</span>
                                    <span class="flex shrink-0 items-center gap-1">
                                        <span class="font-mono text-emerald-400">×{{ $tradeItem->quantity }}</span>
                                        @if ($side['removable'])
                                            <button
                                                type="button"
                                                wire:click="removeOffer({{ $tradeItem->item_id }})"
                                                class="rounded text-zinc-600 transition-colors hover:text-red-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-500"
                                                aria-label="Take {{ $tradeItem->item?->name }} off the table"
                                            >
                                                <flux:icon.x-mark variant="micro" class="size-3.5" />
                                            </button>
                                        @endif
                                    </span>
                                </li>
                            @empty
                                <li class="py-3 text-center font-mono text-[10px] text-zinc-700">Nothing offered yet.</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2">
                <flux:button
                    wire:click="commit"
                    size="sm"
                    variant="primary"
                    class="flex-1"
                    :disabled="$iHaveCommitted"
                    data-test="trade-commit"
                >
                    {{ $iHaveCommitted ? 'Waiting for '.$partner->name : 'Commit trade' }}
                </flux:button>
                <flux:button
                    wire:click="cancel({{ $trade->id }})"
                    size="sm"
                    variant="ghost"
                    data-test="trade-cancel"
                >
                    Cancel
                </flux:button>
            </div>
            <p class="font-mono text-[10px] text-zinc-600">Changing either offer withdraws both commitments. The trade completes once you both commit.</p>

            <div class="space-y-2">
                <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Your pack</p>
                @forelse ($this->pack as $inventoryItem)
                    <div wire:key="trade-pack-{{ $inventoryItem->item_id }}" class="flex items-center justify-between gap-2 rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm text-zinc-200">{{ $inventoryItem->item->name }}</p>
                            <p class="font-mono text-[10px] text-zinc-600">×{{ $inventoryItem->quantity }} carried</p>
                        </div>
                        @if ($inventoryItem->item->is_quest_item)
                            <span class="shrink-0 rounded border border-amber-800/50 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wide text-amber-500/80">Quest item</span>
                        @else
                            <form wire:submit="offer({{ $inventoryItem->item_id }})" class="flex shrink-0 items-center gap-1">
                                <label for="offer-{{ $inventoryItem->item_id }}" class="sr-only">Quantity of {{ $inventoryItem->item->name }} to offer</label>
                                <input
                                    id="offer-{{ $inventoryItem->item_id }}"
                                    type="number"
                                    min="1"
                                    max="{{ $inventoryItem->quantity }}"
                                    wire:model="offerQuantities.{{ $inventoryItem->item_id }}"
                                    placeholder="1"
                                    class="w-14 rounded border border-zinc-800 bg-zinc-950 px-2 py-1 font-mono text-xs text-zinc-200 placeholder:text-zinc-700 focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/60"
                                />
                                <button
                                    type="submit"
                                    class="rounded border border-zinc-700 px-2 py-1 font-mono text-[10px] uppercase tracking-wide text-zinc-300 transition-colors hover:border-emerald-500/50 hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-500"
                                >
                                    Offer
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="rounded-lg border border-dashed border-zinc-800 px-3 py-4 text-center text-xs text-zinc-600">Your pack is empty.</p>
                @endforelse
            </div>
        @else
            @if ($this->incomingRequests->isNotEmpty() || $this->outgoingRequest)
                <div class="space-y-2">
                    <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Trade requests</p>

                    @foreach ($this->incomingRequests as $request)
                        <div wire:key="trade-request-{{ $request->id }}" class="flex items-center justify-between gap-2 rounded-lg border border-emerald-900/60 bg-emerald-950/20 px-3 py-2">
                            <p class="min-w-0 truncate text-sm text-zinc-200">{{ $request->initiator->name }} wants to trade</p>
                            <div class="flex shrink-0 gap-1">
                                <flux:button wire:click="accept({{ $request->id }})" size="xs" variant="primary" :disabled="! $this->isAtTradePost" data-test="trade-accept-{{ $request->id }}">Accept</flux:button>
                                <flux:button wire:click="cancel({{ $request->id }})" size="xs" variant="ghost" data-test="trade-decline-{{ $request->id }}">Reject</flux:button>
                            </div>
                        </div>
                    @endforeach

                    @if ($this->outgoingRequest)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2">
                            <p class="min-w-0 truncate text-sm text-zinc-400">Waiting for {{ $this->outgoingRequest->recipient->name }}…</p>
                            <flux:button wire:click="cancel({{ $this->outgoingRequest->id }})" size="xs" variant="ghost">Withdraw</flux:button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($this->isAtTradePost)
                <div class="space-y-2">
                    <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-600">Traders at this post</p>
                    @forelse ($this->playersHere as $trader)
                        <div wire:key="trader-{{ $trader->id }}" class="flex items-center justify-between gap-2 rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <flux:avatar :name="$trader->name" color="auto" size="xs" circle class="shrink-0" />
                                <p class="truncate text-sm text-zinc-200">{{ $trader->name }}</p>
                            </div>
                            <flux:button
                                wire:click="requestTrade({{ $trader->id }})"
                                size="xs"
                                variant="primary"
                                :disabled="$this->outgoingRequest !== null"
                                data-test="trade-request-{{ $trader->id }}"
                            >
                                Trade
                            </flux:button>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-zinc-800 px-3 py-6 text-center text-xs text-zinc-600">No other traders here right now.</p>
                    @endforelse
                </div>
            @else
                <div class="flex flex-col items-center pt-4 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-zinc-800 bg-zinc-900">
                        <svg class="h-8 w-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                    </div>
                    <p class="mb-4 font-mono text-xs text-amber-500/50">NO TRADE POST IN {{ strtoupper($this->city?->city ?? 'this area') }}</p>
                    <div class="w-full space-y-1 rounded-lg border border-zinc-800 bg-zinc-900/50 px-4 py-3" data-test="nearest-trade-post">
                        @if ($this->nearestTradePost)
                            <p class="font-mono text-xs text-zinc-500">Nearest trade post on {{ $this->city->country->continent }}:</p>
                            <p class="text-sm font-medium text-emerald-400">
                                {{ $this->nearestTradePost['city']->city }}, {{ $this->nearestTradePost['city']->country?->country }}
                            </p>
                            @if ($this->nearestTradePost['distance_km'] !== null)
                                <p class="font-mono text-[10px] text-zinc-600">~{{ number_format($this->nearestTradePost['distance_km']) }} km away</p>
                            @endif
                        @else
                            <p class="font-mono text-xs text-zinc-500">No trade post operates on this continent yet.</p>
                        @endif
                        <p class="font-mono text-xs text-zinc-700">Both traders must stand at the same trade post.</p>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
