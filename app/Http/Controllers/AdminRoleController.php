<?php

namespace App\Http\Controllers;

use App\Actions\Game\ChangeUserRole;
use App\Http\Requests\ChangeUserRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;

class AdminRoleController extends Controller
{
    public function update(ChangeUserRoleRequest $request, ChangeUserRole $changeUserRole): RedirectResponse
    {
        try {
            $changeUserRole(
                $request->user(),
                User::query()->findOrFail($request->integer('target_user_id')),
                Role::query()->where('key', $request->string('role_key'))->firstOrFail(),
            );
        } catch (AuthorizationException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        return to_route('dashboard')->with('status', 'Role updated and audit logged.');
    }
}
