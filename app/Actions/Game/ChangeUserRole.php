<?php

namespace App\Actions\Game;

use App\Models\Role;
use App\Models\RoleChangeAudit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ChangeUserRole
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $actor, User $target, Role $role): void
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException('Only admins may change another user role.');
        }

        if ($actor->is($target)) {
            throw new AuthorizationException('Admins may only change another user role.');
        }

        DB::transaction(function () use ($actor, $target, $role): void {
            $previousRole = $target->role_id;

            $target->forceFill([
                'role_id' => $role->id,
            ])->save();

            RoleChangeAudit::query()->create([
                'actor_user_id' => $actor->id,
                'target_user_id' => $target->id,
                'old_role_id' => $previousRole,
                'new_role_id' => $role->id,
                'created_at' => now(),
            ]);
        });
    }
}
