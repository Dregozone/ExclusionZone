<?php

namespace App\Events;

use App\Models\RadioMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RadioMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RadioMessage $radioMessage) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('radio'),
        ];
    }

    /**
     * Listeners re-read the channel themselves, so only the message id goes over the wire.
     *
     * @return array{id:int}
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->radioMessage->id];
    }
}
