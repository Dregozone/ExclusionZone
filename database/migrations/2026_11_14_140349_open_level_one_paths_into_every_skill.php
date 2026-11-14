<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives a new survivor a way into every skill from level 1.
 *
 * Hunting, Crafting and Stealth had no level 1 action anywhere, so each gets one near the Kyiv start.
 * Engineering is opened by the first story quest instead: Viktor now asks for scrap metal from Kyiv's
 * level 1 district patrols rather than a circuit board, which needed Engineering 6. His reward of
 * 300 Engineering XP then takes the survivor to Engineering 3.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->seedActions();
            $this->openStaticWithScrapMetal();
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('city_actions')->whereIn('action_key', array_column($this->actions(), 'action_key'))->delete();

            $this->updateStaticSteps(
                requiredItemKey: 'circuit_boards',
                requiredItemQuantity: 1,
                briefing: 'Been picking up fragments on the old emergency bands for weeks. Military encoding, pre-war timestamps. I\'m getting somewhere but the hardware is completely shot. Find me a circuit board — anything I can use to rebuild the receiver — and I can clean this signal right up.',
                handOverLabel: 'Bring Viktor a circuit board',
            );
        });
    }

    private function openStaticWithScrapMetal(): void
    {
        $this->updateStaticSteps(
            requiredItemKey: 'scrap_metal',
            requiredItemQuantity: 2,
            briefing: 'Been picking up fragments on the old emergency bands for weeks. Military encoding, pre-war timestamps. I\'m getting somewhere but the hardware is completely shot. Bring me scrap metal — the patrols in the fortified districts drag in plenty — and I\'ll show you how to rebuild the receiver. Learn this and you\'ll never be stuck with dead kit again.',
            handOverLabel: 'Bring Viktor scrap metal to rebuild the receiver',
        );
    }

    private function updateStaticSteps(string $requiredItemKey, int $requiredItemQuantity, string $briefing, string $handOverLabel): void
    {
        $questId = DB::table('quests')->where('name', 'Static')->value('id');
        $itemId = DB::table('items')->where('key', $requiredItemKey)->value('id');

        if ($questId === null || $itemId === null) {
            return;
        }

        DB::table('quest_steps')->where('quest_id', $questId)->where('step_order', 0)->update([
            'interaction_text' => $briefing,
            'updated_at' => now(),
        ]);

        DB::table('quest_steps')->where('quest_id', $questId)->where('step_order', 1)->update([
            'action_label' => $handOverLabel,
            'required_item_id' => $itemId,
            'required_item_quantity' => $requiredItemQuantity,
            'updated_at' => now(),
        ]);
    }

    private function seedActions(): void
    {
        $cityIds = DB::table('cities')->whereIn('city', ['Kyiv', 'Warsaw'])->pluck('id', 'city');

        foreach ($this->actions() as $action) {
            $cityId = $cityIds[$action['city']] ?? null;
            $itemKeys = [$action['reward_profile']['item_key'], ...array_column($action['required_items'] ?? [], 'item_key')];

            if ($cityId === null
                || DB::table('city_actions')->where('action_key', $action['action_key'])->exists()
                || DB::table('items')->whereIn('key', $itemKeys)->count() !== count(array_unique($itemKeys))) {
                continue;
            }

            unset($action['city']);

            DB::table('city_actions')->insert([
                ...$action,
                'city_id' => $cityId,
                'reward_profile' => json_encode($action['reward_profile']),
                'required_items' => $action['required_items'] === null ? null : json_encode($action['required_items']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return list<array{city: string, action_key: string, label: string, description: string, skill_key: string, min_level: int, risk_level: string, base_duration_seconds: int, hunger_cost: float, fatigue_cost: float, reward_profile: array{xp: int, item_key: string, quantity: int, loot_tier: string}, required_items: list<array{item_key: string, quantity: int}>|null}>
     */
    private function actions(): array
    {
        return [
            [
                'city' => 'Warsaw',
                'action_key' => 'outskirts_snaring',
                'label' => 'Outskirts snaring',
                'description' => 'Set wire snares along the overgrown rail yards and check them before the dogs do.',
                'skill_key' => 'hunting',
                'min_level' => 1,
                'risk_level' => 'low',
                'base_duration_seconds' => 20,
                'hunger_cost' => 0.6,
                'fatigue_cost' => 0.6,
                'reward_profile' => ['xp' => 15, 'item_key' => 'winter_game', 'quantity' => 1, 'loot_tier' => 'medium'],
                'required_items' => null,
            ],
            [
                'city' => 'Warsaw',
                'action_key' => 'scrap_tinkering',
                'label' => 'Scrap tinkering',
                'description' => 'Hammer patrol scrap into clean metal parts at the garrison workbench.',
                'skill_key' => 'crafting',
                'min_level' => 1,
                'risk_level' => 'low',
                'base_duration_seconds' => 20,
                'hunger_cost' => 0.6,
                'fatigue_cost' => 0.6,
                'reward_profile' => ['xp' => 16, 'item_key' => 'metal_parts', 'quantity' => 1, 'loot_tier' => 'medium'],
                'required_items' => [['item_key' => 'scrap_metal', 'quantity' => 2]],
            ],
            [
                'city' => 'Kyiv',
                'action_key' => 'checkpoint_slipping',
                'label' => 'Checkpoint slipping',
                'description' => 'Slip past metro checkpoints after curfew and lift ration tokens from careless guards.',
                'skill_key' => 'stealth',
                'min_level' => 1,
                'risk_level' => 'low',
                'base_duration_seconds' => 20,
                'hunger_cost' => 0.6,
                'fatigue_cost' => 0.6,
                'reward_profile' => ['xp' => 15, 'item_key' => 'ration_tokens', 'quantity' => 1, 'loot_tier' => 'medium'],
                'required_items' => null,
            ],
        ];
    }
};
