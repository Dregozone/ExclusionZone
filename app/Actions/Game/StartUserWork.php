<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityAction;
use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class StartUserWork
{
    public function __construct(
        public PerformCityAction $performCityAction,
        public TravelToCity $travelToCity,
        public CalculateWorkDuration $calculateWorkDuration,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function forCityAction(User $user, CityAction $action): UserWork
    {
        $user->loadMissing('location.city', 'skills.skill');

        $this->ensureNoActiveWork($user);

        $skillProgress = $this->performCityAction->authorize($user, $action);

        return $this->createWork($user, [
            'work_type' => 'city_action',
            'city_action_id' => $action->id,
            'origin_city_id' => $action->city_id,
            'destination_city_id' => null,
            'skill_key' => $action->skill_key,
            'duration_seconds' => $this->calculateWorkDuration->forCityAction($user, $action, $skillProgress),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function forTravel(User $user, City $destination): UserWork
    {
        $user->loadMissing('location.city');

        $this->ensureNoActiveWork($user);
        $this->travelToCity->authorize($user, $destination);

        return $this->createWork($user, [
            'work_type' => 'travel',
            'city_action_id' => null,
            'origin_city_id' => $user->location?->city_id,
            'destination_city_id' => $destination->id,
            'skill_key' => null,
            'duration_seconds' => $this->calculateWorkDuration->forTravel($user, $destination),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureNoActiveWork(User $user): void
    {
        if ($user->activeWork()->exists()) {
            throw new AuthorizationException('Finish or cancel your current work before starting something new.');
        }
    }

    /**
     * @param  array{work_type:string,city_action_id:?int,origin_city_id:?int,destination_city_id:?int,skill_key:?string,duration_seconds:int}  $attributes
     *
     * @throws AuthorizationException
     */
    private function createWork(User $user, array $attributes): UserWork
    {
        try {
            return DB::transaction(function () use ($user, $attributes): UserWork {
                if (UserWork::query()->whereBelongsTo($user)->lockForUpdate()->exists()) {
                    throw new AuthorizationException('Finish or cancel your current work before starting something new.');
                }

                return UserWork::query()->create([
                    'user_id' => $user->id,
                    ...$attributes,
                    'available_at' => now()->addSeconds($attributes['duration_seconds']),
                ]);
            });
        } catch (QueryException $exception) {
            if (UserWork::query()->whereBelongsTo($user)->exists()) {
                throw new AuthorizationException('Finish or cancel your current work before starting something new.', 0, $exception);
            }

            throw $exception;
        }
    }
}
