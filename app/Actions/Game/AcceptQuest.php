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
            if ($quest->is_repeatable && $existing->status === 'repeatable') {
                $existing->update([
                    'status' => 'active',
                    'current_step_index' => 0,
                    'notes' => null,
                    'active_requirements' => $this->generateActiveRequirements($quest),
                ]);

                return $existing->fresh();
            }

            throw new AuthorizationException('You have already accepted that quest.');
        }

        return $user->userQuests()->create([
            'quest_id' => $quest->id,
            'current_step_index' => 0,
            'status' => 'active',
            'active_requirements' => $quest->is_repeatable ? $this->generateActiveRequirements($quest) : null,
        ]);
    }

    /**
     * Randomly select one requirement variant per step that has variants defined.
     *
     * @return array<int, array{required_item_id: int, required_item_quantity: int}>
     */
    private function generateActiveRequirements(Quest $quest): array
    {
        $quest->loadMissing('steps');

        $requirements = [];

        foreach ($quest->steps as $step) {
            if (! empty($step->requirement_variants)) {
                $variant = $step->requirement_variants[array_rand($step->requirement_variants)];
                $requirements[$step->step_order] = $variant;
            }
        }

        return $requirements;
    }
}
