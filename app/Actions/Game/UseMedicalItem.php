<?php

namespace App\Actions\Game;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UseMedicalItem
{
    public function __construct(
        public TrackSurvivalNeeds $trackSurvivalNeeds,
    ) {}

    /**
     * Use one of the medical item from the survivor's pack. Patching up is allowed while work or travel runs.
     *
     * @return int The health restored.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Item $item): int
    {
        if (! $item->isMedical()) {
            throw new AuthorizationException("{$item->name} will not heal you.");
        }

        $this->trackSurvivalNeeds->rest($user);

        if ($user->health >= (int) config('survival.maximum')) {
            throw new AuthorizationException('You are not hurt. Save it for later.');
        }

        return DB::transaction(function () use ($user, $item): int {
            $inventoryItem = $user->inventoryItems()
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($inventoryItem === null || $inventoryItem->quantity < 1) {
                throw new AuthorizationException("You are not carrying any {$item->name}.");
            }

            if ((int) $inventoryItem->quantity <= 1) {
                $inventoryItem->delete();
            } else {
                $inventoryItem->decrement('quantity');
            }

            $healthBefore = $user->health;

            $this->trackSurvivalNeeds->relieve($user, 0, 0, $item->health_restored);

            return $user->health - $healthBefore;
        });
    }
}
