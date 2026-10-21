<?php

namespace App\Actions\Game;

use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CommitTrade
{
    public function __construct(
        private EnsurePlayersCanTrade $ensurePlayersCanTrade,
        private BroadcastTradeUpdate $broadcastTradeUpdate,
    ) {}

    /**
     * Agree to what is on the table; once both traders commit, the items change hands.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Trade $trade): Trade
    {
        $trade = DB::transaction(function () use ($user, $trade): Trade {
            /** @var Trade $trade */
            $trade = Trade::query()->with('items.item', 'items.user')->lockForUpdate()->findOrFail($trade->id);

            if (! $trade->involves($user) || $trade->status !== TradeStatus::Open) {
                throw new AuthorizationException('That trade is no longer open.');
            }

            if ($trade->items->isEmpty()) {
                throw new AuthorizationException('Put at least one item on the table before committing.');
            }

            $trade->forceFill([
                ($trade->isInitiator($user) ? 'initiator_committed_at' : 'recipient_committed_at') => now(),
            ]);

            if ($trade->bothCommitted()) {
                $this->completeTrade($user, $trade);
            }

            $trade->save();

            return $trade;
        });

        ($this->broadcastTradeUpdate)($trade);

        return $trade;
    }

    /**
     * @throws AuthorizationException
     */
    private function completeTrade(User $user, Trade $trade): void
    {
        ($this->ensurePlayersCanTrade)($user, User::query()->findOrFail($trade->partnerIdOf($user)));

        $trade->items->each(function (TradeItem $tradeItem) use ($trade): void {
            $this->takeFromGiver($tradeItem);

            $receiverId = $tradeItem->user_id === $trade->initiator_id ? $trade->recipient_id : $trade->initiator_id;
            $inventoryItem = User::query()->findOrFail($receiverId)->inventoryItems()->firstOrCreate(
                ['item_id' => $tradeItem->item_id],
                ['quantity' => 0],
            );
            $inventoryItem->increment('quantity', $tradeItem->quantity);
        });

        $trade->forceFill([
            'status' => TradeStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function takeFromGiver(TradeItem $tradeItem): void
    {
        if ($tradeItem->item->is_quest_item) {
            throw new AuthorizationException($tradeItem->item->name.' is a quest item and cannot be traded.');
        }

        $inventoryItem = $tradeItem->user->inventoryItems()
            ->where('item_id', $tradeItem->item_id)
            ->lockForUpdate()
            ->first();

        if ($inventoryItem === null || $inventoryItem->quantity < $tradeItem->quantity) {
            throw new AuthorizationException($tradeItem->user->name.' no longer carries '.$tradeItem->quantity.'x '.$tradeItem->item->name.'. Update the offer and commit again.');
        }

        if ($inventoryItem->quantity === $tradeItem->quantity) {
            $inventoryItem->delete();
        } else {
            $inventoryItem->decrement('quantity', $tradeItem->quantity);
        }
    }
}
