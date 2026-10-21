<?php

namespace Database\Factories;

use App\Models\RadioMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadioMessage>
 */
class RadioMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'body' => $this->faker->sentence(),
        ];
    }
}
