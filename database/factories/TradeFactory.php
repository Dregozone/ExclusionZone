<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Trade;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'initiator_id' => User::factory(),
            'recipient_id' => User::factory(),
            'city_id' => fn (): int => City::query()->where('has_trade_post', true)->value('id'),
            'status' => TradeStatus::Pending,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TradeStatus::Open,
        ]);
    }
}
