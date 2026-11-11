<?php

namespace App\Events;

use App\HarmCause;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A survivor's health reached 0. Listeners decide what death means; for now they are rescued.
 */
class SurvivorDied
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public HarmCause $cause,
    ) {}
}
