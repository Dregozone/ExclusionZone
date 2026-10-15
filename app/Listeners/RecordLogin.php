<?php

namespace App\Listeners;

use App\Models\Login;
use Illuminate\Auth\Events\Login as LoginEvent;

class RecordLogin
{
    public function handle(LoginEvent $event): void
    {
        Login::query()->create([
            'user_id' => $event->user->getAuthIdentifier(),
            'created_at' => now(),
        ]);
    }
}
