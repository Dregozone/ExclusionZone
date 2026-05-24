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
        $currentCity = $user->location?->city;

        if ($currentCity === null || ! $currentCity->neighbors()->whereKey($destination->id)->exists()) {
            throw new AuthorizationException('That route is not available from your current city.');
        }

        DB::transaction(function () use ($user, $destination): void {
            $user->location()->update([
                'country_id' => $destination->country_id,
                'city_id' => $destination->id,
                'updated_at' => now(),
            ]);
        });
    }
}
