<?php

namespace App\Livewire;

use App\Actions\Game\AcceptTrade;
use App\Actions\Game\CancelTrade;
use App\Actions\Game\CommitTrade;
use App\Actions\Game\FindNearestTradePost;
use App\Actions\Game\RequestTrade;
use App\Actions\Game\SetTradeOffer;
use App\Models\City;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\TradeStatus;
use Flux\Flux;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TradeBoard extends Component
{
    public const NEARBY_PLAYER_LIMIT = 25;

    /**
     * Quantity typed next to each pack item, keyed by item id.
     *
     * @var array<int|string, int|string|null>
     */
    public array $offerQuantities = [];

    /**
     * Trade changes arrive on the player's own private channel.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return $this->canTrade
            ? ['echo-private:App.Models.User.'.$this->player()->id.',TradeUpdated' => 'refreshTrades']
            : [];
    }

    #[Computed]
    public function canTrade(): bool
    {
        return $this->player()->canPerformTask('trade_create');
    }

    #[Computed]
    public function city(): ?City
    {
        return $this->player()->location()->with('city.country:id,country,continent')->first()?->city;
    }

    #[Computed]
    public function isAtTradePost(): bool
    {
        return $this->city?->has_trade_post ?? false;
    }

    /**
     * @return array{city: City, distance_km: ?int}|null
     */
    #[Computed]
    public function nearestTradePost(): ?array
    {
        return $this->city === null ? null : app(FindNearestTradePost::class)($this->city);
    }

    /**
     * Other traders standing at this trade post, most recently active first.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function playersHere(): Collection
    {
        if (! $this->isAtTradePost) {
            return collect();
        }

        return User::query()
            ->whereKeyNot($this->player()->id)
            ->whereHas('location', fn ($query) => $query->where('city_id', $this->city->id))
            ->orderByDesc('last_active_at')
            ->orderBy('name')
            ->limit(self::NEARBY_PLAYER_LIMIT)
            ->get(['id', 'name']);
    }

    #[Computed]
    public function openTrade(): ?Trade
    {
        return Trade::query()
            ->where('status', TradeStatus::Open)
            ->involving($this->player())
            ->with(['initiator:id,name', 'recipient:id,name', 'city:id,city', 'items.item'])
            ->latest('id')
            ->first();
    }

    #[Computed]
    public function outgoingRequest(): ?Trade
    {
        return Trade::query()
            ->where('status', TradeStatus::Pending)
            ->where('initiator_id', $this->player()->id)
            ->with('recipient:id,name')
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, Trade>
     */
    #[Computed]
    public function incomingRequests(): Collection
    {
        return Trade::query()
            ->where('status', TradeStatus::Pending)
            ->where('recipient_id', $this->player()->id)
            ->with('initiator:id,name')
            ->latest('id')
            ->get();
    }

    /**
     * The player's pack, with what is already on the table for the open trade.
     *
     * @return Collection<int, InventoryItem>
     */
    #[Computed]
    public function pack(): Collection
    {
        return $this->player()->inventoryItems()
            ->with('item')
            ->get()
            ->filter(fn (InventoryItem $inventoryItem): bool => $inventoryItem->item !== null)
            ->sortBy(fn (InventoryItem $inventoryItem): string => $inventoryItem->item->name)
            ->values();
    }

    /**
     * @return array{mine: Collection<int, TradeItem>, theirs: Collection<int, TradeItem>}
     */
    #[Computed]
    public function table(): array
    {
        $items = $this->openTrade?->items ?? collect();

        return [
            'mine' => $items->where('user_id', $this->player()->id)->values(),
            'theirs' => $items->where('user_id', '!==', $this->player()->id)->values(),
        ];
    }

    public function requestTrade(int $userId, RequestTrade $requestTrade): void
    {
        $this->attempt(function () use ($userId, $requestTrade): void {
            $recipient = User::query()->findOrFail($userId);
            $requestTrade($this->player(), $recipient);
            $this->toast('success', 'Trade request sent to '.$recipient->name.'.');
        });
    }

    public function accept(int $tradeId, AcceptTrade $acceptTrade): void
    {
        $this->attempt(fn () => $acceptTrade($this->player(), $this->tradeFor($tradeId)));
    }

    public function cancel(int $tradeId, CancelTrade $cancelTrade): void
    {
        $this->attempt(function () use ($tradeId, $cancelTrade): void {
            $trade = $cancelTrade($this->player(), $this->tradeFor($tradeId));
            $this->toast('success', $trade->status === TradeStatus::Declined ? 'Trade request declined.' : 'Trade cancelled. No items changed hands.');
        });
    }

    public function offer(int $itemId, SetTradeOffer $setTradeOffer): void
    {
        $quantity = $this->offerQuantities[$itemId] ?? null;

        if (! is_numeric($quantity) || (int) $quantity < 1) {
            $this->toast('danger', 'Enter how many to offer.');

            return;
        }

        $this->setOffer($itemId, (int) $quantity, $setTradeOffer);
        unset($this->offerQuantities[$itemId]);
    }

    public function removeOffer(int $itemId, SetTradeOffer $setTradeOffer): void
    {
        $this->setOffer($itemId, 0, $setTradeOffer);
    }

    public function commit(CommitTrade $commitTrade): void
    {
        $this->attempt(function () use ($commitTrade): void {
            if ($this->openTrade === null) {
                throw new AuthorizationException('That trade is no longer open.');
            }

            $trade = $commitTrade($this->player(), $this->openTrade);
            $this->toast('success', $trade->status === TradeStatus::Completed
                ? 'Trade complete. The items are in your pack.'
                : 'Offer committed. Waiting for the other trader to commit.');
        });
    }

    public function refreshTrades(): void
    {
        unset($this->openTrade, $this->outgoingRequest, $this->incomingRequests, $this->pack, $this->table, $this->playersHere);
    }

    private function setOffer(int $itemId, int $quantity, SetTradeOffer $setTradeOffer): void
    {
        $this->attempt(function () use ($itemId, $quantity, $setTradeOffer): void {
            if ($this->openTrade === null) {
                throw new AuthorizationException('That trade is no longer open.');
            }

            $setTradeOffer($this->player(), $this->openTrade, Item::query()->findOrFail($itemId), $quantity);
        });
    }

    /**
     * Run a trade action, turning a refusal into a toast.
     */
    private function attempt(callable $action): void
    {
        try {
            $action();
        } catch (AuthorizationException $exception) {
            $this->toast('danger', $exception->getMessage());
        }

        $this->refreshTrades();
    }

    private function toast(string $variant, string $text): void
    {
        Flux::toast(variant: $variant, text: $text, duration: 10000);
    }

    /**
     * Only trades the player is part of can be looked up, so other trades stay hidden.
     */
    private function tradeFor(int $tradeId): Trade
    {
        return Trade::query()->involving($this->player())->findOrFail($tradeId);
    }

    private function player(): User
    {
        /** @var User */
        return auth()->user();
    }
}
