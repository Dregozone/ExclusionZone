<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Enemy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Enemy>
 */
class EnemyFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'city_id' => fn (): int => City::query()->where('city', 'Kyiv')->value('id'),
            'key' => Str::slug($name, '_'),
            'name' => ucwords($name),
            'description' => $this->faker->sentence(),
            'skill_key' => 'combat_melee',
            'health' => 20,
            'min_damage' => 3,
            'max_damage' => 3,
            'xp_reward' => 10,
            'loot' => [],
        ];
    }
}
