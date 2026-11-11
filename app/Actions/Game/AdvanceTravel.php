<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AdvanceTravel
{
    public function __construct(
        public TravelToCity $travelToCity,
        public CalculateWorkDuration $calculateWorkDuration,
        public TrackSurvivalNeeds $trackSurvivalNeeds,
    ) {}

    /**
     * Move the player along every leg of their route whose timer has run out, setting off on
     * the next queued leg straight away. A leg that can't be followed by the next one ends the route.
     *
     * @return array{legs:int,last_status:?string,route_ended:bool}
     */
    public function __invoke(User $user): array
    {
        $legs = 0;
        $lastStatus = null;

        while (true) {
            $leg = DB::transaction(fn (): ?array => $this->settleNextLeg($user));

            if ($leg === null) {
                return ['legs' => $legs, 'last_status' => $lastStatus, 'route_ended' => false];
            }

            $legs++;
            $lastStatus = $leg['status'];

            if ($leg['route_ended']) {
                return ['legs' => $legs, 'last_status' => $lastStatus, 'route_ended' => true];
            }
        }
    }

    /**
     * @return array{status:string,route_ended:bool}|null
     */
    private function settleNextLeg(User $user): ?array
    {
        $work = UserWork::query()
            ->with('destinationCity')
            ->whereBelongsTo($user)
            ->where('work_type', 'travel')
            ->where('available_at', '<=', now())
            ->lockForUpdate()
            ->first();

        if ($work === null) {
            return null;
        }

        $user->load('location.city');
        $arrival = $work->destinationCity;

        try {
            if ($arrival === null) {
                throw new AuthorizationException('That route is no longer available.');
            }

            ($this->travelToCity)($user, $arrival);
        } catch (AuthorizationException $exception) {
            return $this->endRoute($user, $work, $exception->getMessage().' You stayed in '.($user->location?->city?->city ?? 'your current city').'.');
        }

        $collapsed = $this->trackSurvivalNeeds->exert(
            $user,
            (int) config('survival.travel.hunger_per_leg'),
            (int) config('survival.travel.fatigue_per_leg'),
        );

        if ($collapsed) {
            return ['status' => "You reached {$arrival->city}, then collapsed.", 'route_ended' => true];
        }

        $queuedCityIds = $work->queuedCityIds();

        if ($queuedCityIds === []) {
            $work->delete();

            return ['status' => "You arrived in {$arrival->city} and refreshed your options.", 'route_ended' => true];
        }

        $user->load('location.city');
        $nextStop = City::query()->find($queuedCityIds[0]);
        $stopReason = $this->reasonRouteCannotContinue($user, $nextStop);

        if ($stopReason !== null) {
            return $this->endRoute($user, $work, "You arrived in {$arrival->city} but could not carry on along your route. {$stopReason}");
        }

        $duration = $this->calculateWorkDuration->forTravel($user, $nextStop);

        $work->fill([
            'origin_city_id' => $arrival->id,
            'destination_city_id' => $nextStop->id,
            'queued_city_ids' => array_slice($queuedCityIds, 1),
            'duration_seconds' => $duration,
            'weather' => $this->calculateWorkDuration->currentWeather($user),
            'available_at' => $work->available_at->addSeconds($duration),
        ])->save();

        return ['status' => "You arrived in {$arrival->city} and set off for {$nextStop->city}.", 'route_ended' => false];
    }

    private function reasonRouteCannotContinue(User $user, ?City $nextStop): ?string
    {
        if ($nextStop === null) {
            return 'The next stop on your route is no longer available.';
        }

        $denialReason = $user->denialReasonForTask('city_action_perform');

        if ($denialReason !== null) {
            return $denialReason;
        }

        try {
            $this->travelToCity->authorize($user, $nextStop);
        } catch (AuthorizationException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    /**
     * End the route where the player stands now, and leave a report they will see even if they were away.
     *
     * @return array{status:string,route_ended:bool}
     */
    private function endRoute(User $user, UserWork $work, string $status): array
    {
        $user->forceFill(['last_work_report' => $status])->save();

        $work->delete();

        return ['status' => $status, 'route_ended' => true];
    }
}
