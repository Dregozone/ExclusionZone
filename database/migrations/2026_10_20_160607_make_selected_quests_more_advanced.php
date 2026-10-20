<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reshapes seeded quests that already exist in the database so they need more than
 * two steps, more than one item, several item types, or repeated city actions.
 *
 * Fresh installs get the same shape from StoryQuestSeeder and RecurringJobsSeeder,
 * so every change here is skipped when the quest has not been seeded yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->insertStep('Passage North', 1, 'harbor_fishing', 3, [
                'city' => 'Gdansk',
                'person_of_interest' => 'Captain Halvorsen',
                'action_label' => 'Help Halvorsen\'s crew stock the galley',
                'interaction_text' => 'Good haul. That\'s eighteen bellies fed for the crossing — the crew will remember it. Now the ammo: three crates, and a couple of sealed rations for the long nights on the water. Bring them and we sail at first tide.',
            ]);
            $this->updateStep('Passage North', 'Pay Halvorsen with ammo for the crossing', [
                'required_item_quantity' => 3,
                'required_items' => $this->itemList(['sealed_rations' => 2]),
            ]);

            $this->insertStep('Ingrid\'s Price', 1, 'copenhagen_harbour_freight', 2, [
                'city' => 'Copenhagen',
                'person_of_interest' => 'Ingrid Larssen',
                'action_label' => 'Sweep the freight terminals around the warehouse',
                'interaction_text' => 'The terminals are clear and my people have eyes on the warehouse now. Whoever skimmed our routes hasn\'t come back for the cache — yet. Recover it and bring it here before they do.',
            ]);

            $this->updateStep('The Swedish Archive', 'Power the NORDVAULT terminal with fuel cells', [
                'required_item_quantity' => 3,
            ]);

            $this->updateStep('The Tower', 'Bypass the tower\'s security junction', [
                'required_items' => $this->itemList(['circuit_boards' => 1]),
            ]);

            $this->insertStep('Zone Salvage Contract', 1, 'radiation_sampling', 2, [
                'city' => 'Pripyat',
                'person_of_interest' => 'Trader Pavel',
                'action_label' => 'Chart the hot zone for Pavel',
                'interaction_text' => 'Readings check out. Now I know where the good material is sitting — and so do you. Bring me what the buyer wants and we\'re square.',
            ]);

            $this->updateStep('Supply Run', 'Deliver the supplies to Masha', [
                'requirement_variants' => $this->variantList([
                    ['sealed_rations' => 2],
                    ['clean_water' => 3],
                    ['ration_tokens' => 3],
                    ['scrap_metal' => 3],
                    ['sealed_rations' => 1, 'clean_water' => 2],
                    ['scrap_metal' => 2, 'ration_tokens' => 2],
                ]),
            ]);

            $this->updateStep('Tech Salvage Contract', 'Deliver tech components to Kenji', [
                'requirement_variants' => $this->variantList([
                    ['drone_parts' => 2],
                    ['circuit_boards' => 2],
                    ['server_cores' => 1],
                    ['secure_data' => 1],
                    ['drone_parts' => 1, 'circuit_boards' => 1],
                ]),
            ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->removeStep('Passage North', 'Help Halvorsen\'s crew stock the galley');
            $this->updateStep('Passage North', 'Pay Halvorsen with ammo for the crossing', [
                'required_item_quantity' => 1,
                'required_items' => null,
            ]);

            $this->removeStep('Ingrid\'s Price', 'Sweep the freight terminals around the warehouse');

            $this->updateStep('The Swedish Archive', 'Power the NORDVAULT terminal with fuel cells', [
                'required_item_quantity' => 1,
            ]);

            $this->updateStep('The Tower', 'Bypass the tower\'s security junction', [
                'required_items' => null,
            ]);

            $this->removeStep('Zone Salvage Contract', 'Chart the hot zone for Pavel');

            $this->updateStep('Supply Run', 'Deliver the supplies to Masha', [
                'requirement_variants' => $this->variantList([
                    ['sealed_rations' => 2],
                    ['clean_water' => 3],
                    ['ration_tokens' => 3],
                    ['scrap_metal' => 3],
                ]),
            ]);

            $this->updateStep('Tech Salvage Contract', 'Deliver tech components to Kenji', [
                'requirement_variants' => $this->variantList([
                    ['drone_parts' => 2],
                    ['circuit_boards' => 2],
                    ['server_cores' => 1],
                    ['secure_data' => 1],
                ]),
            ]);
        });
    }

    /**
     * Insert a city-action step at the given position, shifting later steps and any
     * players already past that point so they stay on the step they were on.
     *
     * @param  array{city: string, person_of_interest: string, action_label: string, interaction_text: string}  $attributes
     */
    private function insertStep(string $questName, int $stepOrder, string $actionKey, int $actionCount, array $attributes): void
    {
        $questId = DB::table('quests')->where('name', $questName)->value('id');
        $cityId = DB::table('cities')->where('city', $attributes['city'])->value('id');
        $cityActionId = DB::table('city_actions')->where('action_key', $actionKey)->value('id');

        if ($questId === null || $cityId === null || $cityActionId === null) {
            return;
        }

        $alreadyInserted = DB::table('quest_steps')
            ->where('quest_id', $questId)
            ->where('action_label', $attributes['action_label'])
            ->exists();

        if ($alreadyInserted) {
            return;
        }

        DB::table('quest_steps')
            ->where('quest_id', $questId)
            ->where('step_order', '>=', $stepOrder)
            ->increment('step_order');

        DB::table('quest_steps')->insert([
            'quest_id' => $questId,
            'step_order' => $stepOrder,
            'city_id' => $cityId,
            'person_of_interest' => $attributes['person_of_interest'],
            'action_label' => $attributes['action_label'],
            'interaction_text' => $attributes['interaction_text'],
            'required_item_id' => null,
            'required_item_quantity' => 1,
            'consumes_item' => false,
            'required_city_action_id' => $cityActionId,
            'required_action_count' => $actionCount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->shiftUserQuests($questId, $stepOrder, 1);
    }

    private function removeStep(string $questName, string $actionLabel): void
    {
        $questId = DB::table('quests')->where('name', $questName)->value('id');

        if ($questId === null) {
            return;
        }

        $stepOrder = DB::table('quest_steps')
            ->where('quest_id', $questId)
            ->where('action_label', $actionLabel)
            ->value('step_order');

        if ($stepOrder === null) {
            return;
        }

        DB::table('quest_steps')
            ->where('quest_id', $questId)
            ->where('action_label', $actionLabel)
            ->delete();

        DB::table('quest_steps')
            ->where('quest_id', $questId)
            ->where('step_order', '>', $stepOrder)
            ->decrement('step_order');

        $this->shiftUserQuests($questId, $stepOrder + 1, -1);
    }

    /**
     * Move active players at or beyond a step index by the given offset, re-keying
     * their rolled job requirements to match the new step positions.
     */
    private function shiftUserQuests(int $questId, int $fromStepIndex, int $offset): void
    {
        $userQuests = DB::table('user_quests')
            ->where('quest_id', $questId)
            ->where('status', 'active')
            ->get(['id', 'current_step_index', 'active_requirements']);

        foreach ($userQuests as $userQuest) {
            $changes = ['step_action_progress' => 0];

            if ($userQuest->current_step_index >= $fromStepIndex) {
                $changes['current_step_index'] = $userQuest->current_step_index + $offset;
            }

            if ($userQuest->active_requirements !== null) {
                $requirements = [];

                foreach (json_decode($userQuest->active_requirements, true) as $stepIndex => $requirement) {
                    $requirements[$stepIndex >= $fromStepIndex ? $stepIndex + $offset : $stepIndex] = $requirement;
                }

                $changes['active_requirements'] = json_encode($requirements);
            }

            DB::table('user_quests')->where('id', $userQuest->id)->update($changes);
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function updateStep(string $questName, string $actionLabel, array $changes): void
    {
        $questId = DB::table('quests')->where('name', $questName)->value('id');

        if ($questId === null || in_array(false, $changes, true)) {
            return;
        }

        foreach (['required_items', 'requirement_variants'] as $jsonColumn) {
            if (array_key_exists($jsonColumn, $changes) && $changes[$jsonColumn] !== null) {
                $changes[$jsonColumn] = json_encode($changes[$jsonColumn]);
            }
        }

        DB::table('quest_steps')
            ->where('quest_id', $questId)
            ->where('action_label', $actionLabel)
            ->update([...$changes, 'updated_at' => now()]);
    }

    /**
     * Build a requirement list from item keys, or false when any item is missing.
     *
     * @param  array<string, int>  $quantitiesByItemKey
     * @return array<int, array{required_item_id: int, required_item_quantity: int}>|false
     */
    private function itemList(array $quantitiesByItemKey): array|false
    {
        $itemIds = DB::table('items')->whereIn('key', array_keys($quantitiesByItemKey))->pluck('id', 'key');

        if ($itemIds->count() !== count($quantitiesByItemKey)) {
            return false;
        }

        $requirements = [];

        foreach ($quantitiesByItemKey as $itemKey => $quantity) {
            $requirements[] = ['required_item_id' => $itemIds[$itemKey], 'required_item_quantity' => $quantity];
        }

        return $requirements;
    }

    /**
     * Build requirement variants: a single-item variant stays a single requirement,
     * a multi-item variant becomes a list. Returns false when any item is missing.
     *
     * @param  array<int, array<string, int>>  $variants
     * @return array<int, mixed>|false
     */
    private function variantList(array $variants): array|false
    {
        $built = [];

        foreach ($variants as $variant) {
            $requirements = $this->itemList($variant);

            if ($requirements === false) {
                return false;
            }

            $built[] = count($requirements) === 1 ? $requirements[0] : $requirements;
        }

        return $built;
    }
};
