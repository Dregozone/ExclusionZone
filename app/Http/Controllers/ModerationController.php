<?php

namespace App\Http\Controllers;

use App\Actions\Game\ApplyUserMute;
use App\Http\Requests\MuteUserRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;

class ModerationController extends Controller
{
    public function store(MuteUserRequest $request, ApplyUserMute $applyUserMute): RedirectResponse
    {
        try {
            $applyUserMute(
                $request->user(),
                User::query()->findOrFail($request->integer('target_user_id')),
                $request->integer('duration_minutes'),
                $request->string('reason')->toString() ?: null,
            );
        } catch (AuthorizationException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        return to_route('dashboard')->with('status', 'Mute applied to the selected survivor.');
    }
}
