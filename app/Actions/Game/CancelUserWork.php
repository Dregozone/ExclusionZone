<?php

namespace App\Actions\Game;

use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CancelUserWork
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $user): UserWork
    {
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
