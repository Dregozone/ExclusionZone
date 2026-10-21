<?php

namespace App\Actions\Game;

use App\Events\RadioMessageSent;
use App\Models\RadioMessage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Throwable;

class SendRadioMessage
{
    /**
     * Post a message to the global radio channel and push it to everyone listening.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, string $body): RadioMessage
    {
        $denialReason = $user->denialReasonForTask('chat_send');

        if ($denialReason !== null) {
            throw new AuthorizationException($denialReason);
        }

        $mutedUntil = $user->activeMutes()->max('ends_at');

        if ($mutedUntil !== null) {
            throw new AuthorizationException('You are muted on the radio until '.Carbon::parse($mutedUntil)->format('H:i').'.');
        }

        $radioMessage = $user->radioMessages()->create(['body' => trim($body)]);

        try {
            broadcast(new RadioMessageSent($radioMessage))->toOthers();
        } catch (Throwable $exception) {
            report($exception);
        }

        return $radioMessage;
    }
}
