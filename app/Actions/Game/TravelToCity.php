<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TravelToCity
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $user, City $destination): void
    {
        $this->authorize($user, $destination);

        DB::transaction(function () use ($user, $destination): void {
            $user->location()->update([
                'country_id' => $destination->country_id,
                'city_id' => $destination->id,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(User $user, City $destination): void
    {
        $currentCity = $user->location?->city;

        if ($currentCity === null || ! $currentCity->neighbors()->whereKey($destination->id)->exists()) {
            throw new AuthorizationException('That route is not available from your current city.');
        }
    }

    /**
     * Check that the player can follow a route from their current city, one connected stop after another.
     *
     * @param  list<int>  $route  every city to visit in order, ending at the final destination
     *
     * @throws AuthorizationException
     */
    public function authorizeRoute(User $user, array $route): void
    {
        $currentCityId = $user->location?->city_id;
        $stops = [$currentCityId, ...$route];

        if ($currentCityId === null || $route === [] || count(array_unique($stops)) !== count($stops)) {
            throw new AuthorizationException('That route is not available from your current city.');
        }

        if (City::query()->whereKey($route)->count() !== count($route)) {
            throw new AuthorizationException('That route is not available from your current city.');
        }

        $connections = DB::table('city_connections')
            ->whereIn('city_id', array_slice($stops, 0, -1))
            ->get(['city_id', 'neighbor_city_id'])
            ->map(fn (object $connection): string => $connection->city_id.'-'.$connection->neighbor_city_id)
            ->flip();

        foreach (array_slice($stops, 1) as $index => $cityId) {
            if (! $connections->has($stops[$index].'-'.$cityId)) {
                throw new AuthorizationException('That route is not available from your current city.');
            }
        }
    }
}
