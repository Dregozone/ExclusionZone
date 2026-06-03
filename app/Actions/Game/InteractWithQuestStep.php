<?php

namespace App\Actions\Game;

use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class InteractWithQuestStep
{
    /**
     * @return array{completed: bool, message: string}
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, QuestStep $step): array
    {
        $user->loadMissing(['location', 'inventoryItems.item', 'skills.skill']);
        $step->loadMissing(['quest.steps', 'requiredItem', 'city']);

        if ($user->location?->city_id !== $step->city_id) {
            throw new AuthorizationException('You must be in '.$step->city->city.' to interact with this.');
        }

        $userQuest = $user->userQuests()
            ->where('quest_id', $step->quest_id)
            ->where('status', 'active')
            ->first();

        if ($userQuest === null) {
            throw new AuthorizationException('You do not have this quest active.');
        }

        $steps = $step->quest->steps;
        $stepIndex = $steps->search(fn (QuestStep $s) => $s->id === $step->id);

        if ($stepIndex === false || $stepIndex !== $userQuest->current_step_index) {
            throw new AuthorizationException('That is not your current quest objective.');
        }

        if ($step->required_item_id !== null) {
            $inventoryItem = $user->inventoryItems->first(fn ($i) => $i->item_id === $step->required_item_id);

            if ($inventoryItem === null || $inventoryItem->quantity < $step->required_item_quantity) {
                throw new AuthorizationException('You need '.$step->required_item_quantity.'x '.$step->requiredItem->name.' to proceed.');
            }
        }

        return DB::transaction(function () use ($user, $step, $userQuest, $stepIndex, $steps): array {
            if ($step->consumes_item && $step->required_item_id !== null) {
                $inventoryItem = $user->inventoryItems()->where('item_id', $step->required_item_id)->lockForUpdate()->first();

                if ($inventoryItem->quantity <= $step->required_item_quantity) {
                    $inventoryItem->delete();
                } else {
                    $inventoryItem->decrement('quantity', $step->required_item_quantity);
                }
            }

            $notes = $userQuest->notes ?? [];
            $notes[] = $step->interaction_text;

            $isLastStep = $stepIndex === $steps->count() - 1;

            if ($isLastStep) {
                $userQuest->update([
                    'notes' => $notes,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $rewardSummary = $this->grantReward($user, $step->quest);

                return [
                    'completed' => true,
                    'message' => 'Quest complete: '.$step->quest->name.'.'.$rewardSummary,
                ];
            }

            $userQuest->update([
                'notes' => $notes,
                'current_step_index' => $stepIndex + 1,
            ]);

            return [
                'completed' => false,
                'message' => $step->interaction_text,
            ];
        });
    }

    private function grantReward(User $user, Quest $quest): string
    {
        $summary = '';

        if ($quest->reward_skill_id !== null && $quest->reward_xp_amount !== null) {
            $userSkill = $user->skills()->where('skill_id', $quest->reward_skill_id)->first();

            if ($userSkill !== null) {
                $userSkill->xp += $quest->reward_xp_amount;

                while ($userSkill->xp >= ($userSkill->level * 100)) {
                    $userSkill->xp -= $userSkill->level * 100;
                    $userSkill->level++;
                }

                $userSkill->save();

                $skillName = $quest->rewardSkill?->display_name ?? 'Unknown';
                $summary .= ' +'.$quest->reward_xp_amount.' '.$skillName.' XP';
            }
        }

        if ($quest->reward_item_id !== null) {
            $inventoryItem = $user->inventoryItems()->firstOrCreate(
                ['item_id' => $quest->reward_item_id],
                ['quantity' => 0],
            );

            $inventoryItem->increment('quantity', $quest->reward_item_quantity);

            $itemName = $quest->rewardItem?->name ?? 'Unknown';
            $summary .= ' +'.$quest->reward_item_quantity.'x '.$itemName;
        }

        return $summary;
    }
}
