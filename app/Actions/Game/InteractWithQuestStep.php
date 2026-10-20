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

        if ($step->required_city_action_id !== null && $userQuest->step_action_progress < $step->required_action_count) {
            $remaining = $step->required_action_count - $userQuest->step_action_progress;
            $step->loadMissing('requiredCityAction');

            throw new AuthorizationException('You need to complete '.($step->requiredCityAction?->label ?? 'the required action').' '.$remaining.' more '.($remaining === 1 ? 'time' : 'times').' to proceed.');
        }

        $requirements = $this->resolveRequirements($userQuest, $step, $stepIndex);

        foreach ($requirements as $requirement) {
            $inventoryItem = $user->inventoryItems->first(fn ($i) => $i->item_id === $requirement['required_item_id']);

            if ($inventoryItem === null || $inventoryItem->quantity < $requirement['required_item_quantity']) {
                $item = Item::find($requirement['required_item_id']);
                throw new AuthorizationException('You need '.$requirement['required_item_quantity'].'x '.($item?->name ?? 'the required item').' to proceed.');
            }
        }

        return DB::transaction(function () use ($user, $step, $userQuest, $stepIndex, $steps, $requirements): array {
            if ($step->consumes_item) {
                foreach ($requirements as $requirement) {
                    $inventoryItem = $user->inventoryItems()->where('item_id', $requirement['required_item_id'])->lockForUpdate()->first();

                    if ($inventoryItem->quantity <= $requirement['required_item_quantity']) {
                        $inventoryItem->delete();
                    } else {
                        $inventoryItem->decrement('quantity', $requirement['required_item_quantity']);
                    }
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
                        'step_action_progress' => 0,
                        'active_requirements' => null,
                    ]);
                } else {
                    $userQuest->update([
                        'notes' => $notes,
                        'status' => 'completed',
                        'completed_at' => now(),
                        'step_action_progress' => 0,
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
                'step_action_progress' => 0,
            ]);

            return [
                'completed' => false,
                'message' => $step->interaction_text,
            ];
        });
    }

    /**
     * Resolve every item a step needs, preferring active_requirements for repeatable jobs.
     *
     * A requirement variant may be a single requirement or a list of them, and the step's
     * required_items are always needed on top. Quantities of the same item are summed.
     *
     * @return array<int, array{required_item_id: int, required_item_quantity: int}>
     */
    public function resolveRequirements(UserQuest $userQuest, QuestStep $step, int $stepIndex): array
    {
        $requirements = [];

        if ($userQuest->active_requirements !== null && isset($userQuest->active_requirements[$stepIndex])) {
            $variant = $userQuest->active_requirements[$stepIndex];
            $requirements = array_is_list($variant) ? $variant : [$variant];
        } elseif ($step->required_item_id !== null) {
            $requirements[] = [
                'required_item_id' => $step->required_item_id,
                'required_item_quantity' => $step->required_item_quantity,
            ];
        }

        $quantitiesByItem = [];

        foreach ([...$requirements, ...($step->required_items ?? [])] as $requirement) {
            $itemId = (int) $requirement['required_item_id'];
            $quantitiesByItem[$itemId] = ($quantitiesByItem[$itemId] ?? 0) + (int) $requirement['required_item_quantity'];
        }

        return collect($quantitiesByItem)
            ->map(fn (int $quantity, int $itemId): array => [
                'required_item_id' => $itemId,
                'required_item_quantity' => $quantity,
            ])
            ->values()
            ->all();
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
