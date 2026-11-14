<?php

namespace Database\Factories;

use App\FeedbackType;
use App\Models\FeedbackReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackReport>
 */
class FeedbackReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => FeedbackType::Bug,
            'title' => $this->faker->sentence(4),
            'details' => $this->faker->paragraph(),
        ];
    }

    public function feedback(): static
    {
        return $this->state(fn (): array => ['type' => FeedbackType::Feedback]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => ['resolved_at' => now()]);
    }
}
