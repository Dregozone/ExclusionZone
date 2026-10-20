<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Weather;
use Illuminate\Support\Lottery;

class RollCityWeather
{
    /**
     * One in four wet rolls turns into a storm.
     */
    private const STORM_SHARE_OF_WET_ROLLS = 4;

    public function __invoke(City $city): Weather
    {
        $weather = $this->roll($city->rain_chance_pct);

        $city->update(['weather' => $weather]);

        return $weather;
    }

    private function roll(int $rainChancePercent): Weather
    {
        if (! Lottery::odds($rainChancePercent, 100)->choose()) {
            return Weather::Clear;
        }

        return Lottery::odds(1, self::STORM_SHARE_OF_WET_ROLLS)->choose()
            ? Weather::Storm
            : Weather::Rain;
    }
}
