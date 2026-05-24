<?php

namespace App\Actions\Game;

use App\Models\User;
use App\Models\UserMute;
use Illuminate\Auth\Access\AuthorizationException;

class ApplyUserMute
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $actor, User $target, int $durationMinutes, ?string $reason = null): void
    {
        if (! $actor->isModerator()) {
            throw new AuthorizationException('Only moderators or admins may mute users.');
        }

        UserMute::query()->create([
            'moderator_user_id' => $actor->id,
            'target_user_id' => $target->id,
            'reason' => $reason,
            'starts_at' => now(),
            'ends_at' => now()->addMinutes($durationMinutes),
        ]);
    }
}
