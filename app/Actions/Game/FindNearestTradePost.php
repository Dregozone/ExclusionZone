<?php

namespace App\Actions\Game;

use App\Models\City;

class FindNearestTradePost
{
    private const EARTH_RADIUS_KM = 6371;

    /**
     * The closest trade post on the same continent as the given city, as the crow flies.
     *
     * @return array{city: City, distance_km: ?int}|null
     */
    public function __invoke(City $city): ?array
    {
        $continent = $city->country?->continent;

        if ($continent === null) {
            return null;
        }

        return City::query()
            ->with('country:id,country,continent')
            ->where('has_trade_post', true)
            ->whereHas('country', fn ($query) => $query->where('continent', $continent))
            ->get()
            ->map(fn (City $tradePost): array => [
                'city' => $tradePost,
                'distance_km' => $this->distanceInKilometres($city, $tradePost),
            ])
            ->sortBy(fn (array $tradePost): int => $tradePost['distance_km'] ?? PHP_INT_MAX)
            ->first();
    }

    private function distanceInKilometres(City $from, City $to): ?int
    {
        if ($from->is($to)) {
            return 0;
        }

        if ($from->lat === null || $from->lng === null || $to->lat === null || $to->lng === null) {
            return null;
        }

        $latitudeDelta = deg2rad($to->lat - $from->lat);
        $longitudeDelta = deg2rad($to->lng - $from->lng);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($from->lat)) * cos(deg2rad($to->lat)) * sin($longitudeDelta / 2) ** 2;

        return (int) round(self::EARTH_RADIUS_KM * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine)));
    }
}
