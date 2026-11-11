<?php

namespace App\Actions\Game;

use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CancelUserWork
{
    public function __construct(
        public AdvanceWorkLoop $advanceWorkLoop,
        public AdvanceTravel $advanceTravel,
        public TrackSurvivalNeeds $trackSurvivalNeeds,
    ) {}

    /**
     * Cycles that have already finished are paid out and finished travel legs are arrived at;
     * only the unfinished cycle or leg is forfeited. Running from trouble cannot be canceled.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user): UserWork
    {
        ($this->advanceWorkLoop)($user);
        ($this->advanceTravel)($user);

        return DB::transaction(function () use ($user): UserWork {
            $work = UserWork::query()
                ->with(['cityAction', 'originCity', 'destinationCity'])
                ->whereBelongsTo($user)
                ->lockForUpdate()
                ->first();

            if ($work === null) {
                throw new AuthorizationException('There is no active work to cancel.');
            }

            if ($work->is_retreat) {
                throw new AuthorizationException('There is no turning back now. Keep running until you reach '.($work->destinationCity?->city ?? 'safety').'.');
            }

            $work->delete();

            $this->trackSurvivalNeeds->startResting($user);

            return $work;
        });
    }
}
