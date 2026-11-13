<?php

namespace App\Actions\Game;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class EatFood
{
    public function __construct(
        public TrackSurvivalNeeds $trackSurvivalNeeds,
    ) {}

    /**
     * Eat one of the item from the survivor's pack. Eating is allowed while work or travel runs.
     *
     * @return array{hunger_relieved: float, fatigue_relieved: float, health_restored: int}
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Item $item): array
    {
        if (! $item->isFood()) {
            throw new AuthorizationException("{$item->name} is not something you can eat.");
        }

        $this->trackSurvivalNeeds->rest($user);

        if ($user->hunger <= 0 && $user->fatigue <= 0 && $user->health >= (int) config('survival.maximum')) {
            throw new AuthorizationException('You are not hungry, tired or hurt. Save it for later.');
        }

        return DB::transaction(function () use ($user, $item): array {
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

            $hungerBefore = $user->hunger;
            $fatigueBefore = $user->fatigue;
            $healthBefore = $user->health;

            $this->trackSurvivalNeeds->relieve($user, $item->hunger_restored, $item->fatigue_restored, $item->health_restored);

            return [
                'hunger_relieved' => round($hungerBefore - $user->hunger, 1),
                'fatigue_relieved' => round($fatigueBefore - $user->fatigue, 1),
                'health_restored' => $user->health - $healthBefore,
            ];
        });
    }
}
