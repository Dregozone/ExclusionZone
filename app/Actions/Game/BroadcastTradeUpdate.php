<?php

namespace App\Actions\Game;

use App\Events\TradeUpdated;
use App\Models\Trade;
use Throwable;

class BroadcastTradeUpdate
{
    /**
     * Push a trade change to both traders; a broadcasting outage must never undo the change itself.
     * The trade is touched so a change to the items on the table still raises a PDA alert.
     */
    public function __invoke(Trade $trade): void
    {
        $trade->touch();

        try {
            broadcast(new TradeUpdated($trade))->toOthers();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
