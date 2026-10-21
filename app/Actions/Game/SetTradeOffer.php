<?php

namespace App\Actions\Game;

use App\Models\Item;
use App\Models\Trade;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SetTradeOffer
{
    public function __construct(private BroadcastTradeUpdate $broadcastTradeUpdate) {}

    /**
     * Put a quantity of an item on the table, or take it off with a quantity of zero.
     *
     * Any change to the table withdraws both commitments, so nobody completes a deal they have not seen.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Trade $trade, Item $item, int $quantity): Trade
    {
        if (! $trade->involves($user) || $trade->status !== TradeStatus::Open) {
            throw new AuthorizationException('That trade is no longer open.');
        }

        if ($item->is_quest_item) {
            throw new AuthorizationException($item->name.' is a quest item and cannot be traded.');
        }

        if ($quantity < 0) {
            throw new AuthorizationException('Offer a quantity of zero or more.');
        }

        $ownedQuantity = (int) $user->inventoryItems()->where('item_id', $item->id)->value('quantity');

        if ($quantity > $ownedQuantity) {
            throw new AuthorizationException('You only carry '.$ownedQuantity.'x '.$item->name.'.');
        }

        DB::transaction(function () use ($user, $trade, $item, $quantity): void {
            if ($quantity === 0) {
                $trade->items()->where('user_id', $user->id)->where('item_id', $item->id)->delete();
            } else {
                $trade->items()->updateOrCreate(
                    ['user_id' => $user->id, 'item_id' => $item->id],
                    ['quantity' => $quantity],
                );
            }

            $trade->update([
                'initiator_committed_at' => null,
                'recipient_committed_at' => null,
            ]);
        });

        ($this->broadcastTradeUpdate)($trade);

        return $trade;
    }
}
