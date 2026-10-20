<?php

namespace App\Actions\Game;

use App\Models\CityAction;
use App\Models\User;
use App\Models\UserQuest;

class RecordQuestActionProgress
{
    /**
     * Count a completed city action towards every active quest whose current step requires it.
     *
     * @return array<int, string> One progress line per quest that advanced, e.g. "Passage North: Harbor fishing 2/3".
     */
    public function __invoke(User $user, CityAction $action): array
    {
        $userQuests = $user->userQuests()
            ->where('status', 'active')
            ->with('quest.steps')
            ->get();

        $progressLines = [];

        foreach ($userQuests as $userQuest) {
            /** @var UserQuest $userQuest */
            $step = $userQuest->quest?->steps->get($userQuest->current_step_index);

            if ($step === null || $step->required_city_action_id !== $action->id) {
                continue;
            }

            if ($userQuest->step_action_progress >= $step->required_action_count) {
                continue;
            }

            $userQuest->increment('step_action_progress');

            $progressLines[] = $userQuest->quest->name.': '.$action->label.' '.$userQuest->step_action_progress.'/'.$step->required_action_count;
        }

        return $progressLines;
    }
}
