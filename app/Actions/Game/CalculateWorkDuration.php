<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityAction;
use App\Models\User;
use App\Models\UserSkill;
use App\Weather;
use Illuminate\Support\Facades\DB;

class CalculateWorkDuration
{
    private const MINIMUM_DURATION_SECONDS = 10;

    /**
     * Upper bound of the unsigned smallint user_works.duration_seconds column.
     */
    private const MAXIMUM_DURATION_SECONDS = 65535;

    public function forCityAction(User $user, CityAction $action, ?UserSkill $skillProgress = null): int
    {
        $skillProgress ??= $user->skillFor($action->skill_key);

        $baseDuration = $action->base_duration_seconds;
        $skillLevel = $skillProgress?->level ?? 1;
        $reductionPercent = max(0, $skillLevel - 1);
        $scaledDuration = (int) ceil($baseDuration * ((100 - $reductionPercent) / 100));

        return $this->applyWeather(max(self::MINIMUM_DURATION_SECONDS, $scaledDuration), $this->currentWeather($user));
    }

    public function forTravel(User $user, City $destination): int
    {
        $originCityId = $user->location?->city_id;

        $duration = DB::table('city_connections')
            ->where('city_id', $originCityId)
            ->where('neighbor_city_id', $destination->id)
            ->value('duration_seconds');

        return $this->applyWeather(
            max(self::MINIMUM_DURATION_SECONDS, (int) ($duration ?? self::MINIMUM_DURATION_SECONDS)),
            $this->currentWeather($user),
        );
    }

    public function forBrainScorcher(User $user): int
    {
        return $this->applyWeather(FeedBrainScorcher::BASE_DURATION_SECONDS, $this->currentWeather($user));
    }

    public function currentWeather(User $user): Weather
    {
        return $user->location?->city?->weather ?? Weather::Clear;
    }

    private function applyWeather(int $duration, Weather $weather): int
    {
        $weatheredDuration = (int) ceil($duration * (100 + $weather->timerIncreasePercent()) / 100);

        return min(self::MAXIMUM_DURATION_SECONDS, $weatheredDuration);
    }
}
