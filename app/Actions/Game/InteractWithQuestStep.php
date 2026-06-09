<?php

namespace App\Actions\Game;

use App\Models\Item;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\User;
use App\Models\UserQuest;
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

        $requirement = $this->resolveRequirement($userQuest, $step, $stepIndex);

        if ($requirement !== null) {
            $inventoryItem = $user->inventoryItems->first(fn ($i) => $i->item_id === $requirement['required_item_id']);

            if ($inventoryItem === null || $inventoryItem->quantity < $requirement['required_item_quantity']) {
                $item = Item::find($requirement['required_item_id']);
                throw new AuthorizationException('You need '.$requirement['required_item_quantity'].'x '.($item?->name ?? 'the required item').' to proceed.');
            }
        }

        return DB::transaction(function () use ($user, $step, $userQuest, $stepIndex, $steps, $requirement): array {
            if ($step->consumes_item && $requirement !== null) {
                $inventoryItem = $user->inventoryItems()->where('item_id', $requirement['required_item_id'])->lockForUpdate()->first();

                if ($inventoryItem->quantity <= $requirement['required_item_quantity']) {
                    $inventoryItem->delete();
                } else {
                    $inventoryItem->decrement('quantity', $requirement['required_item_quantity']);
                }
            }

            $notes = $userQuest->notes ?? [];
            $notes[] = $step->interaction_text;

            $isLastStep = $stepIndex === $steps->count() - 1;

            if ($isLastStep) {
                if ($step->quest->is_repeatable) {
                    $userQuest->update([
                        'notes' => $notes,
                        'status' => 'repeatable',
                        'completed_at' => now(),
                        'completion_count' => $userQuest->completion_count + 1,
                        'current_step_index' => 0,
                        'active_requirements' => null,
                    ]);
                } else {
                    $userQuest->update([
                        'notes' => $notes,
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                }

                $rewardSummary = $this->grantReward($user, $step->quest);

                return [
                    'completed' => true,
                    'message' => 'Job complete: '.$step->quest->name.'.'.$rewardSummary,
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

    /**
     * Resolve the effective item requirement for a step, preferring active_requirements for repeatable jobs.
     *
     * @return array{required_item_id: int, required_item_quantity: int}|null
     */
    public function resolveRequirement(UserQuest $userQuest, QuestStep $step, int $stepIndex): ?array
    {
        if ($userQuest->active_requirements !== null && isset($userQuest->active_requirements[$stepIndex])) {
            return $userQuest->active_requirements[$stepIndex];
        }

        if ($step->required_item_id !== null) {
            return [
                'required_item_id' => $step->required_item_id,
                'required_item_quantity' => $step->required_item_quantity,
            ];
        }

        return null;
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
