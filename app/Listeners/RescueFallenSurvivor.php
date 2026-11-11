<?php

namespace App\Listeners;

use App\Events\SurvivorDied;

/**
 * Death is not final yet: a stranger drags the fallen survivor to safety. Their work or travel
 * stops where it is, they wake with a little health back, and the dashboard tells them what happened.
 */
class RescueFallenSurvivor
{
    public function handle(SurvivorDied $event): void
    {
        $user = $event->user;
        $city = $user->location?->city?->city ?? 'the Zone';

        $user->activeWork()->delete();

        $user->forceFill([
            'health' => (int) config('survival.health.revive_health'),
            'rested_at' => now(),
            'last_work_report' => null,
            'death_report' => "{$event->cause->collapseDescription()}, and the Zone very nearly kept you. "
                ."A passing scavenger found you face down outside {$city}, dragged you somewhere warm and stayed until you came round. "
                .'Whatever you were doing is lost, and they left before you could thank them. Next time, nobody may come. Be more careful out there.',
        ])->save();
    }
}
