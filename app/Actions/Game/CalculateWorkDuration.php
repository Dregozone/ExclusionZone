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

    public function __construct(
        public TrackSurvivalNeeds $trackSurvivalNeeds = new TrackSurvivalNeeds,
    ) {}

    public function forCityAction(User $user, CityAction $action, ?UserSkill $skillProgress = null): int
    {
        $skillProgress ??= $user->skillFor($action->skill_key);

        $baseDuration = $action->base_duration_seconds;
        $skillLevel = $skillProgress?->level ?? 1;
        $reductionPercent = max(0, $skillLevel - 1);
        $scaledDuration = (int) ceil($baseDuration * ((100 - $reductionPercent) / 100));

        return $this->applyTimerIncreases($user, max(self::MINIMUM_DURATION_SECONDS, $scaledDuration));
    }

    public function forTravel(User $user, City $destination): int
    {
        $originCityId = $user->location?->city_id;

        $duration = DB::table('city_connections')
            ->where('city_id', $originCityId)
            ->where('neighbor_city_id', $destination->id)
            ->value('duration_seconds');

        return $this->applyTimerIncreases(
            $user,
            max(self::MINIMUM_DURATION_SECONDS, (int) ($duration ?? self::MINIMUM_DURATION_SECONDS)),
        );
    }

    public function forBrainScorcher(User $user): int
    {
        return $this->applyTimerIncreases($user, FeedBrainScorcher::BASE_DURATION_SECONDS);
    }

    public function currentWeather(User $user): Weather
    {
        return $user->location?->city?->weather ?? Weather::Clear;
    }

    /**
     * Bad weather and the survivor's hunger and fatigue each add a percentage to the timer.
     */
    private function applyTimerIncreases(User $user, int $duration): int
    {
        $increasePercent = $this->currentWeather($user)->timerIncreasePercent()
            + $this->trackSurvivalNeeds->timerIncreasePercent($user);

        return min(self::MAXIMUM_DURATION_SECONDS, (int) ceil($duration * (100 + $increasePercent) / 100));
    }
}
