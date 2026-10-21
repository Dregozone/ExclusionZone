<?php

namespace App\Actions\Game;

use App\Models\Trade;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Auth\Access\AuthorizationException;

class AcceptTrade
{
    public function __construct(
        private EnsurePlayersCanTrade $ensurePlayersCanTrade,
        private BroadcastTradeUpdate $broadcastTradeUpdate,
    ) {}

    /**
     * Open the trade screen for both players.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $recipient, Trade $trade): Trade
    {
        if ($trade->recipient_id !== $recipient->id || $trade->status !== TradeStatus::Pending) {
            throw new AuthorizationException('That trade request is no longer waiting for you.');
        }

        ($this->ensurePlayersCanTrade)($recipient, $trade->initiator);

        if (Trade::query()->where('status', TradeStatus::Open)->involving($recipient)->exists()) {
            throw new AuthorizationException('Finish your open trade before accepting another.');
        }

        if (Trade::query()->where('status', TradeStatus::Open)->involving($trade->initiator)->exists()) {
            throw new AuthorizationException($trade->initiator->name.' is busy with another trade.');
        }

        $trade->update(['status' => TradeStatus::Open]);

        ($this->broadcastTradeUpdate)($trade);

        return $trade;
    }
}
