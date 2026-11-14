<?php

namespace App\Actions\Game;

use App\HarmCause;
use App\Models\City;
use App\Models\User;
use App\Models\UserWork;
use App\Weather;
use Illuminate\Support\Lottery;

/**
 * The Zone hurts survivors who work and travel in it: storms, radiation and accidents on dangerous work.
 */
class ExposeToHazards
{
    public function __construct(public TrackSurvivalNeeds $trackSurvivalNeeds) {}

    /**
     * Harm from a finished cycle of a city action: its own accident chance, the storm it was
     * worked in, and the radiation of its city.
     *
     * @return array{died: bool, injuries: string}
     */
    public function afterWorkCycle(User $user, UserWork $work): array
    {
        $action = $work->cityAction;

        return $this->expose($user, [
            [HarmCause::Accident, (int) $action?->hazard_chance_pct, (int) $action?->hazard_damage],
            ...$this->stormAndRadiation($user, $work->weather, $action?->city),
        ]);
    }

    /**
     * Harm from a finished travel leg: the storm it was walked in and the radiation where it arrived.
     *
     * @return array{died: bool, injuries: string}
     */
    public function afterTravelLeg(User $user, City $arrival, Weather $weather): array
    {
        return $this->expose($user, $this->stormAndRadiation($user, $weather, $arrival));
    }

    /**
     * @return list<array{0: HarmCause, 1: int, 2: int}>
     */
    private function stormAndRadiation(User $user, ?Weather $weather, ?City $city): array
    {
        return [
            [
                HarmCause::Storm,
                $weather === Weather::Storm ? (int) config('survival.environment.storm_harm_chance_pct') : 0,
                (int) config('survival.environment.storm_damage'),
            ],
            [HarmCause::Radiation, 100, $this->radiationDamageFor($user, $city)],
        ];
    }

    /**
     * The city's radiation damage, less what the survivor's gas mask, suit or other worn gear turns aside.
     */
    private function radiationDamageFor(User $user, ?City $city): int
    {
        $damage = (int) $city?->radiation_damage;

        return (int) round($damage * (100 - $user->equipmentBonusPercent('radiation_protection')) / 100);
    }

    /**
     * Roll each hazard in turn, stopping if one of them kills the survivor.
     *
     * @param  list<array{0: HarmCause, 1: int, 2: int}>  $hazards  each cause with its chance in percent and its damage
     * @return array{died: bool, injuries: string}
     */
    private function expose(User $user, array $hazards): array
    {
        $injuries = [];

        foreach ($hazards as [$cause, $chancePercent, $damage]) {
            if ($chancePercent <= 0 || $damage <= 0 || ($chancePercent < 100 && ! Lottery::odds($chancePercent, 100)->choose())) {
                continue;
            }

            if ($this->trackSurvivalNeeds->harm($user, $damage, $cause)) {
                return ['died' => true, 'injuries' => ''];
            }

            $injuries[] = "{$cause->injuryDescription()} (-{$damage} health).";
        }

        return ['died' => false, 'injuries' => implode(' ', $injuries)];
    }
}
