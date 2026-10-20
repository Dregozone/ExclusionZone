<?php

namespace App\Console\Commands;

use App\Actions\Game\RollCityWeather;
use App\Models\City;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('weather:roll')]
#[Description('Roll new weather for every city from its rain chance')]
class RollWeather extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RollCityWeather $rollCityWeather): int
    {
        $cityCount = 0;

        City::query()->each(function (City $city) use ($rollCityWeather, &$cityCount): void {
            $rollCityWeather($city);
            $cityCount++;
        });

        $this->info("Weather rolled for {$cityCount} cities.");

        return self::SUCCESS;
    }
}
