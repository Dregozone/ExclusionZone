<?php

namespace App\Listeners;

use App\Actions\Game\FindNearestTradePost;
use App\Events\SurvivorDied;
use App\Models\City;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Death is not final yet: a stranger drags the fallen survivor to the nearest trade post. Their work or
 * travel stops where it is, scavengers pick through their pack before help arrives, and they wake with a
 * little health back, needing time to recover before they can work again. The dashboard tells them what happened.
 */
class RescueFallenSurvivor
{
    public function __construct(public FindNearestTradePost $findNearestTradePost) {}

    public function handle(SurvivorDied $event): void
    {
        $user = $event->user->load('location.city');
        $fellIn = $user->location?->city;

        DB::transaction(function () use ($user, $fellIn, $event): void {
            $user->activeWork()->delete();

            $itemsLost = $this->losePartOfPack($user);
            $wokeIn = $this->carryToSafety($user, $fellIn);
            $recoveryMinutes = (int) config('survival.death.recovery_minutes');

            $user->forceFill([
                'health' => (int) config('survival.health.revive_health'),
                'rested_at' => now(),
                'recovering_until' => $recoveryMinutes > 0 ? now()->addMinutes($recoveryMinutes) : null,
                'last_work_report' => null,
                'death_report' => $this->deathReport($event, $fellIn, $wokeIn, $itemsLost, $recoveryMinutes),
            ])->save();
        });
    }

    /**
     * Scavengers take a share of every stack, rounded down. Quest items are left alone.
     *
     * @return list<string> what was taken, such as "2x Scrap Metal"
     */
    private function losePartOfPack(User $user): array
    {
        $percentLost = (int) config('survival.death.pack_lost_percent');

        if ($percentLost <= 0) {
            return [];
        }

        return $user->inventoryItems()
            ->with('item:id,name,is_quest_item')
            ->lockForUpdate()
            ->get()
            ->reject(fn (InventoryItem $inventoryItem): bool => (bool) $inventoryItem->item?->is_quest_item)
            ->map(function (InventoryItem $inventoryItem) use ($percentLost): ?string {
                $lost = intdiv($inventoryItem->quantity * min(100, $percentLost), 100);

                if ($lost === 0) {
                    return null;
                }

                $lost >= $inventoryItem->quantity
                    ? $inventoryItem->delete()
                    : $inventoryItem->decrement('quantity', $lost);

                return "{$lost}x {$inventoryItem->item?->name}";
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Move the survivor to the nearest trade post on their continent, or leave them where they fell if there is none.
     */
    private function carryToSafety(User $user, ?City $fellIn): ?City
    {
        $safeCity = $fellIn === null ? null : ($this->findNearestTradePost)($fellIn)['city'] ?? null;

        if ($safeCity === null) {
            return $fellIn;
        }

        $user->location()->update([
            'country_id' => $safeCity->country_id,
            'city_id' => $safeCity->id,
            'updated_at' => now(),
        ]);

        $user->unsetRelation('location');

        return $safeCity;
    }

    /**
     * @param  list<string>  $itemsLost
     */
    private function deathReport(SurvivorDied $event, ?City $fellIn, ?City $wokeIn, array $itemsLost, int $recoveryMinutes): string
    {
        $fellName = $fellIn?->city ?? 'the Zone';

        $rescue = $wokeIn === null || $wokeIn->is($fellIn)
            ? "A passing scavenger found you face down outside {$fellName}, dragged you somewhere warm and stayed until you came round."
            : "A passing scavenger found you face down outside {$fellName} and carried you all the way to the trade post in {$wokeIn->city}, where you came round.";

        $pack = $itemsLost === []
            ? 'Your pack was left untouched.'
            : 'Before help came, someone went through your pack: you lost '.implode(', ', $itemsLost).'.';

        $recovery = $recoveryMinutes > 0
            ? "You will need {$recoveryMinutes} ".str('minute')->plural($recoveryMinutes).' to get back on your feet before you can work or travel again.'
            : '';

        return collect([
            "{$event->cause->collapseDescription()}, and the Zone very nearly kept you.",
            $rescue,
            $pack,
            'Whatever you were doing is lost.',
            $recovery,
            'Next time, nobody may come. Be more careful out there.',
        ])->filter()->implode(' ');
    }
}
