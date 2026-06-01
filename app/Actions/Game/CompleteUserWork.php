<?php

namespace App\Actions\Game;

use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CompleteUserWork
{
    public function __construct(
        public PerformCityAction $performCityAction,
        public TravelToCity $travelToCity,
    ) {}

    /**
     * @return array{type:string,status:string}
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $work = UserWork::query()
                ->with(['cityAction.skill', 'originCity', 'destinationCity'])
                ->whereBelongsTo($user)
                ->lockForUpdate()
                ->first();

            if ($work === null) {
                throw new AuthorizationException('There is no active work to complete.');
            }

            if ($work->available_at->isFuture()) {
                throw new AuthorizationException('That work is still in progress. Wait for the timer to finish or cancel it.');
            }

            $result = $work->isCityAction()
                ? $this->completeCityAction($user, $work)
                : $this->completeTravel($user, $work);

            $work->delete();

            return $result;
        });
    }

    /**
     * @return array{type:string,status:string}
     */
    private function completeCityAction(User $user, UserWork $work): array
    {
        $action = $work->cityAction;

        if ($action === null) {
            throw new AuthorizationException('That action is no longer available.');
        }

        $reward = ($this->performCityAction)($user, $action);

        $summary = "Action completed: +{$reward['xp']} XP";

        if ($reward['item_name'] !== null) {
            $summary .= ", +{$reward['quantity']} {$reward['item_name']}";
        }

        if ($reward['levels_gained'] > 0) {
            $summary .= ", {$reward['levels_gained']} level gained";
        }

        return [
            'type' => 'city_action',
            'status' => $summary.'.',
        ];
    }

    /**
     * @return array{type:string,status:string}
     */
    private function completeTravel(User $user, UserWork $work): array
    {
        $destination = $work->destinationCity;

        if ($destination === null) {
            throw new AuthorizationException('That route is no longer available.');
        }

        ($this->travelToCity)($user, $destination);

        return [
            'type' => 'travel',
            'status' => 'You arrived in '.$destination->city.' and refreshed your options.',
        ];
    }
}
