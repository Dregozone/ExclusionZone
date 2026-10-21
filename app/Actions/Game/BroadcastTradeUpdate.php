<?php

namespace App\Actions\Game;

use App\Events\TradeUpdated;
use App\Models\Trade;
use Throwable;

class BroadcastTradeUpdate
{
    /**
     * Push a trade change to both traders; a broadcasting outage must never undo the change itself.
     */
    public function __invoke(Trade $trade): void
    {
        try {
            broadcast(new TradeUpdated($trade))->toOthers();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
