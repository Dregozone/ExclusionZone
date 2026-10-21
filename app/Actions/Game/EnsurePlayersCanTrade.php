<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class EnsurePlayersCanTrade
{
    /**
     * Both players need trade access and must stand in the same city with a trade post.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $player, User $partner): City
    {
        $denialReason = $player->denialReasonForTask('trade_create');

        if ($denialReason !== null) {
            throw new AuthorizationException($denialReason);
        }

        if ($partner->denialReasonForTask('trade_create') !== null) {
            throw new AuthorizationException($partner->name.' is not allowed to trade.');
        }

        $city = $player->location()->with('city')->first()?->city;

        if ($city === null || ! $city->has_trade_post) {
            throw new AuthorizationException('You need to be at a trade post to trade.');
        }

        if ($partner->location()->value('city_id') !== $city->id) {
            throw new AuthorizationException($partner->name.' is not at the '.$city->city.' trade post.');
        }

        return $city;
    }
}
