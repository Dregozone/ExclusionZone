<?php

namespace Database\Factories;

use App\Models\Quest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quest>
 */
class QuestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'reward_item_id' => null,
            'reward_item_quantity' => 1,
            'reward_skill_id' => null,
            'reward_xp_amount' => null,
            'is_active' => true,
        ];
    }
}
