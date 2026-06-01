<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityAction;
use App\Models\User;
use App\Models\UserSkill;

class CalculateWorkDuration
{
    private const MINIMUM_DURATION_SECONDS = 10;

    /**
     * @var array<string, int>
     */
    private const CITY_ACTION_DURATIONS = [
        'low' => 10,
        'medium' => 20,
        'high' => 30,
        'extreme' => 40,
    ];

    public function forCityAction(User $user, CityAction $action, ?UserSkill $skillProgress = null): int
    {
        $skillProgress ??= $user->skillFor($action->skill_key);

        $baseDuration = self::CITY_ACTION_DURATIONS[$action->risk_level] ?? self::MINIMUM_DURATION_SECONDS;
        $skillLevel = $skillProgress?->level ?? 1;
        $reductionPercent = max(0, $skillLevel - 1);
        $scaledDuration = (int) ceil($baseDuration * ((100 - $reductionPercent) / 100));

        return max(self::MINIMUM_DURATION_SECONDS, $scaledDuration);
    }

    public function forTravel(User $user, City $destination): int
    {
        return self::MINIMUM_DURATION_SECONDS;
    }
}
