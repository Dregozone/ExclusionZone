<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TradeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trade $trade) {}

    /**
     * Both traders hear about every change on their own private channel.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->trade->initiator_id),
            new PrivateChannel('App.Models.User.'.$this->trade->recipient_id),
        ];
    }

    /**
     * Listeners re-read the trade themselves, so only its id and status go over the wire.
     *
     * @return array{id:int, status:string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->trade->id,
            'status' => $this->trade->status->value,
        ];
    }
}
