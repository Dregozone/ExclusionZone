<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityVisit;
use App\Models\TroubleEncounter;
use App\Models\TroubleType;
use App\Models\User;
use App\Models\UserWork;
use Illuminate\Support\Lottery;

class MeetTroubleOnArrival
{
    /**
     * Record the survivor's arrival and roll the city's trouble chance: the full chance on a first
     * visit, a fraction of it on a return. Running back from trouble never meets more of it.
     */
    public function __invoke(User $user, City $city, UserWork $leg): ?TroubleEncounter
    {
        $isFirstVisit = CityVisit::query()->firstOrCreate(['user_id' => $user->id, 'city_id' => $city->id])->wasRecentlyCreated;

        if (! config('trouble.enabled') || $leg->is_retreat || $user->isHeldByTrouble()) {
            return null;
        }

        $chancePercent = $this->chancePercent($city, $isFirstVisit);

        if ($chancePercent <= 0 || ! Lottery::odds($chancePercent, 100)->choose()) {
            return null;
        }

        $troubleType = TroubleType::query()->inRandomOrder()->first();

        if ($troubleType === null) {
            return null;
        }

        return TroubleEncounter::query()->create([
            'user_id' => $user->id,
            'city_id' => $city->id,
            'trouble_type_id' => $troubleType->id,
            'origin_city_id' => $leg->origin_city_id,
            'queued_city_ids' => $leg->queuedCityIds(),
        ]);
    }

    public function chancePercent(City $city, bool $isFirstVisit): int
    {
        $chancePercent = (int) $city->trouble_chance_pct;

        return $isFirstVisit
            ? $chancePercent
            : (int) round($chancePercent * (int) config('trouble.return_visit_chance_percent') / 100);
    }
}
