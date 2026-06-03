<?php

namespace App\Actions\Game;

use App\Models\Quest;
use App\Models\User;
use App\Models\UserQuest;
use Illuminate\Auth\Access\AuthorizationException;

class AcceptQuest
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Quest $quest): UserQuest
    {
        if (! $quest->is_active) {
            throw new AuthorizationException('That quest is not currently available.');
        }

        $existing = $user->userQuests()->where('quest_id', $quest->id)->first();

        if ($existing !== null) {
            throw new AuthorizationException('You have already accepted that quest.');
        }

        return $user->userQuests()->create([
            'quest_id' => $quest->id,
            'current_step_index' => 0,
            'status' => 'active',
        ]);
    }
}
