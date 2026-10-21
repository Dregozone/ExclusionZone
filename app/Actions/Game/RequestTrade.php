<?php

namespace App\Actions\Game;

use App\Models\Trade;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Auth\Access\AuthorizationException;

class RequestTrade
{
    public function __construct(
        private EnsurePlayersCanTrade $ensurePlayersCanTrade,
        private BroadcastTradeUpdate $broadcastTradeUpdate,
    ) {}

    /**
     * Ask another player at the same trade post to open a trade.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $initiator, User $recipient): Trade
    {
        if ($initiator->is($recipient)) {
            throw new AuthorizationException('You cannot trade with yourself.');
        }

        $city = ($this->ensurePlayersCanTrade)($initiator, $recipient);

        if (Trade::query()->where('status', TradeStatus::Open)->involving($initiator)->exists()) {
            throw new AuthorizationException('Finish your open trade before starting another.');
        }

        if (Trade::query()->where('status', TradeStatus::Pending)->where('initiator_id', $initiator->id)->exists()) {
            throw new AuthorizationException('You already have a trade request out. Cancel it before sending another.');
        }

        if (Trade::query()->where('status', TradeStatus::Pending)->where('initiator_id', $recipient->id)->where('recipient_id', $initiator->id)->exists()) {
            throw new AuthorizationException($recipient->name.' has already asked to trade with you. Accept their request instead.');
        }

        if (Trade::query()->where('status', TradeStatus::Open)->involving($recipient)->exists()) {
            throw new AuthorizationException($recipient->name.' is busy with another trade.');
        }

        $trade = Trade::query()->create([
            'initiator_id' => $initiator->id,
            'recipient_id' => $recipient->id,
            'city_id' => $city->id,
        ]);

        ($this->broadcastTradeUpdate)($trade);

        return $trade;
    }
}
