<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Quest;
use App\Models\QuestStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestStep>
 */
class QuestStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quest_id' => Quest::factory(),
            'step_order' => 0,
            'city_id' => City::query()->inRandomOrder()->value('id'),
            'person_of_interest' => $this->faker->name(),
            'action_label' => 'Speak to '.$this->faker->firstName(),
            'interaction_text' => $this->faker->paragraph(),
            'required_item_id' => null,
            'required_item_quantity' => 1,
            'consumes_item' => false,
            'requirement_variants' => null,
        ];
    }
}
