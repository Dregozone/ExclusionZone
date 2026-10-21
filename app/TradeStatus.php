<?php

namespace App;

enum TradeStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case Completed = 'completed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    /**
     * Pending requests and open trades still tie up both players.
     */
    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Open;
    }
}
