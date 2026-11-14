<?php

namespace App\Actions\Game;

use App\Livewire\RadioChat;
use App\Models\RadioMessage;
use App\Models\Trade;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * What is new on the survivor's PDA since they last looked: radio messages from other survivors and
 * trades that changed, such as a request, an accepted request or a new offer on the table. The PDA
 * shows these as a red bell until the tab is opened.
 */
class TrackPdaAlerts
{
    /**
     * The PDA tabs that raise alerts.
     */
    public const TABS = ['radio', 'trade'];

    /**
     * @return array{radio: int, trade: int}
     */
    public function counts(User $user): array
    {
        return [
            'radio' => $user->canPerformTask('chat_send') ? $this->unreadRadioMessages($user) : 0,
            'trade' => $user->canPerformTask('trade_create') ? $this->changedTrades($user) : 0,
        ];
    }

    /**
     * The survivor has looked at the tab, so everything on it so far is seen.
     */
    public function markSeen(User $user, string $tab): void
    {
        match ($tab) {
            'radio' => $user->forceFill(['radio_seen_message_id' => RadioMessage::query()->max('id')])->save(),
            'trade' => $user->forceFill(['trades_seen_at' => now()])->save(),
            default => null,
        };
    }

    /**
     * Messages from other survivors newer than the last one seen, counted no further back than the radio shows.
     */
    private function unreadRadioMessages(User $user): int
    {
        return RadioMessage::query()
            ->where('user_id', '!=', $user->id)
            ->when($user->radio_seen_message_id !== null, fn (Builder $query) => $query->where('id', '>', $user->radio_seen_message_id))
            ->latest('id')
            ->limit(RadioChat::RECENT_MESSAGE_LIMIT)
            ->get(['id'])
            ->count();
    }

    /**
     * Trades the survivor is part of that changed since they last looked, leaving out requests they sent themselves.
     */
    private function changedTrades(User $user): int
    {
        return Trade::query()
            ->involving($user)
            ->when($user->trades_seen_at !== null, fn (Builder $query) => $query->where('updated_at', '>', $user->trades_seen_at))
            ->whereNot(fn (Builder $query) => $query
                ->where('status', TradeStatus::Pending)
                ->where('initiator_id', $user->id))
            ->when($user->trades_seen_at === null, fn (Builder $query) => $query->whereIn('status', [TradeStatus::Pending, TradeStatus::Open]))
            ->count();
    }
}
