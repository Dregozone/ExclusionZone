<?php

namespace App\Actions\Game;

use App\Models\Trade;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Auth\Access\AuthorizationException;

class CancelTrade
{
    public function __construct(private BroadcastTradeUpdate $broadcastTradeUpdate) {}

    /**
     * Either trader can walk away; a recipient turning down a request declines it.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Trade $trade): Trade
    {
        if (! $trade->involves($user) || ! $trade->status->isActive()) {
            throw new AuthorizationException('That trade is no longer active.');
        }

        $isDeclining = $trade->status === TradeStatus::Pending && ! $trade->isInitiator($user);

        $trade->update([
            'status' => $isDeclining ? TradeStatus::Declined : TradeStatus::Cancelled,
            'initiator_committed_at' => null,
            'recipient_committed_at' => null,
        ]);

        ($this->broadcastTradeUpdate)($trade);

        return $trade;
    }
}
