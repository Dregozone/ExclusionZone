<?php

namespace App\Actions\Game;

use App\EquipSlot;
use App\Models\Item;
use App\Models\User;
use App\Models\UserEquipment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Put gear on and take it off. A worn item leaves the pack, so it can't be traded, sold, burned or
 * lost to scavengers, and goes back into the pack when it is taken off or swapped out.
 */
class EquipItem
{
    /**
     * Wear one of the item from the pack. With no slot given, the item goes in the first free slot it
     * fits, or swaps out whatever is in the first slot it fits.
     *
     * @return array{slot: EquipSlot, replaced: ?Item}
     *
     * @throws AuthorizationException
     */
    public function equip(User $user, Item $item, ?EquipSlot $slot = null): array
    {
        $fittingSlots = EquipSlot::fitting($item);

        if ($fittingSlots === []) {
            throw new AuthorizationException("{$item->name} is not something you can wear.");
        }

        if ($slot !== null && ! in_array($slot, $fittingSlots, true)) {
            throw new AuthorizationException("{$item->name} does not fit your {$slot->label()} slot.");
        }

        $result = DB::transaction(function () use ($user, $item, $slot, $fittingSlots): array {
            $inventoryItem = $user->inventoryItems()
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($inventoryItem === null || $inventoryItem->quantity < 1) {
                throw new AuthorizationException("You are not carrying any {$item->name}.");
            }

            $worn = $user->equipment()->with('item')->lockForUpdate()->get()->keyBy(fn (UserEquipment $equipment): string => $equipment->slot->value);
            $slot ??= collect($fittingSlots)->first(fn (EquipSlot $fittingSlot): bool => ! $worn->has($fittingSlot->value)) ?? $fittingSlots[0];
            $replaced = $worn->get($slot->value);

            if ((int) $inventoryItem->quantity <= 1) {
                $inventoryItem->delete();
            } else {
                $inventoryItem->decrement('quantity');
            }

            if ($replaced !== null) {
                $this->returnToPack($user, $replaced->item_id);
                $replaced->delete();
            }

            $user->equipment()->create(['slot' => $slot, 'item_id' => $item->id]);

            return ['slot' => $slot, 'replaced' => $replaced?->item];
        });

        $user->unsetRelation('equipment')->unsetRelation('inventoryItems');

        return $result;
    }

    /**
     * Take off whatever is worn in the slot and put it back in the pack.
     *
     * @throws AuthorizationException
     */
    public function unequip(User $user, EquipSlot $slot): Item
    {
        $item = DB::transaction(function () use ($user, $slot): Item {
            $equipment = $user->equipment()->with('item')->where('slot', $slot)->lockForUpdate()->first();

            if ($equipment === null) {
                throw new AuthorizationException("You are not wearing anything in your {$slot->label()} slot.");
            }

            $this->returnToPack($user, $equipment->item_id);
            $equipment->delete();

            return $equipment->item;
        });

        $user->unsetRelation('equipment')->unsetRelation('inventoryItems');

        return $item;
    }

    private function returnToPack(User $user, int $itemId): void
    {
        $user->inventoryItems()->firstOrCreate(['item_id' => $itemId], ['quantity' => 0])->increment('quantity');
    }
}
