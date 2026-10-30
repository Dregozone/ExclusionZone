<?php

namespace App\Actions\Game;

use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CancelUserWork
{
    public function __construct(public AdvanceWorkLoop $advanceWorkLoop) {}

    /**
     * Cycles that have already finished are paid out; only the unfinished one is forfeited.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user): UserWork
    {
        ($this->advanceWorkLoop)($user);

        return DB::transaction(function () use ($user): UserWork {
            $work = UserWork::query()
                ->with(['cityAction', 'originCity', 'destinationCity'])
                ->whereBelongsTo($user)
                ->lockForUpdate()
                ->first();

            if ($work === null) {
                throw new AuthorizationException('There is no active work to cancel.');
            }

            $work->delete();

            return $work;
        });
    }
}
