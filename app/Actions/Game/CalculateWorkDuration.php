<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityAction;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Support\Facades\DB;

class CalculateWorkDuration
{
    private const MINIMUM_DURATION_SECONDS = 10;

    public function forCityAction(User $user, CityAction $action, ?UserSkill $skillProgress = null): int
    {
        $skillProgress ??= $user->skillFor($action->skill_key);

        $baseDuration = $action->base_duration_seconds;
        $skillLevel = $skillProgress?->level ?? 1;
        $reductionPercent = max(0, $skillLevel - 1);
        $scaledDuration = (int) ceil($baseDuration * ((100 - $reductionPercent) / 100));

        return max(self::MINIMUM_DURATION_SECONDS, $scaledDuration);
    }

    public function forTravel(User $user, City $destination): int
    {
        $originCityId = $user->location?->city_id;

        $duration = DB::table('city_connections')
            ->where('city_id', $originCityId)
            ->where('neighbor_city_id', $destination->id)
            ->value('duration_seconds');

        return max(self::MINIMUM_DURATION_SECONDS, (int) ($duration ?? self::MINIMUM_DURATION_SECONDS));
    }
}
