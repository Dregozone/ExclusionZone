<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'key' => Str::slug($name, '_'),
            'name' => ucwords($name),
            'description' => $this->faker->sentence(),
        ];
    }

    public function questItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_quest_item' => true,
        ]);
    }
}
