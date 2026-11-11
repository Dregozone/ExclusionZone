<?php

namespace App\Actions\Game;

use App\HarmCause;
use App\Models\City;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Skill;
use App\Models\TroubleEncounter;
use App\Models\TroubleType;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Lottery;

class DealWithTrouble
{
    public function __construct(
        public TrackSurvivalNeeds $trackSurvivalNeeds,
        public StartUserWork $startUserWork,
    ) {}

    /**
     * Fight the trouble in one go. Winning clears it and pays combat XP and loot; losing leaves it in
     * place. Either way the survivor takes damage, and losing hurts more.
     *
     * @return array{won: bool, died: bool, status: string}
     *
     * @throws AuthorizationException
     */
    public function attack(User $user): array
    {
        $this->authorizeTask($user, 'combat_initiate');

        $fight = DB::transaction(function () use ($user): array {
            $encounter = $this->lockedEncounterFor($user);
            $troubleType = $encounter->troubleType;
            $skillProgress = $this->skillProgressFor($user, $troubleType);
            $won = Lottery::odds($this->winChancePercent($user, $troubleType), 100)->choose();
            $damage = random_int($troubleType->damage_min, max($troubleType->damage_min, $troubleType->damage_max));
            $levelsGained = 0;
            $lootName = null;

            if ($won) {
                $damage = (int) ceil($damage * (int) config('trouble.fight.damage_taken_on_win_percent') / 100);
                $levelsGained = $skillProgress->gainExperience($troubleType->xp_reward);
                $lootName = $this->giveLoot($user, $troubleType);
                $encounter->delete();
            }

            return compact('encounter', 'troubleType', 'won', 'damage', 'levelsGained', 'lootName');
        });

        $died = $this->trackSurvivalNeeds->harm($user, $fight['damage'], HarmCause::Trouble);
        $name = $fight['troubleType']->name;

        if (! $fight['won']) {
            $status = $died
                ? "You attacked the {$name} and lost."
                : "You attacked the {$name} and were beaten back (-{$fight['damage']} health). They still block the way.";

            return ['won' => false, 'died' => $died, 'status' => $status];
        }

        $rewards = collect([
            "+{$fight['troubleType']->xp_reward} {$fight['troubleType']->skill?->display_name} XP",
            $fight['lootName'],
            $fight['levelsGained'] > 0 ? "{$fight['levelsGained']} level gained" : null,
            $fight['damage'] > 0 ? "-{$fight['damage']} health" : null,
        ])->filter()->implode(', ');

        $status = "You drove off the {$name} ({$rewards}).";

        if (! $died) {
            $status .= $this->carryOnAlongRoute($user, $fight['encounter']);
        }

        return ['won' => true, 'died' => $died, 'status' => $status];
    }

    /**
     * Hand over goods worth at least what the trouble demands, chosen by the survivor, to be let through.
     *
     * @param  array<int, int>  $offeredQuantitiesByItemId
     *
     * @throws AuthorizationException
     */
    public function buyWayIn(User $user, array $offeredQuantitiesByItemId): string
    {
        $this->authorizeTask($user, 'city_action_perform');

        $offer = array_filter(array_map('intval', $offeredQuantitiesByItemId), fn (int $quantity): bool => $quantity > 0);

        if ($offer === []) {
            throw new AuthorizationException('Choose what to hand over first.');
        }

        [$encounter, $handedOver] = DB::transaction(function () use ($user, $offer): array {
            $encounter = $this->lockedEncounterFor($user);
            $inventoryItems = InventoryItem::query()
                ->with('item')
                ->whereBelongsTo($user)
                ->whereIn('item_id', array_keys($offer))
                ->lockForUpdate()
                ->get()
                ->keyBy('item_id');

            $offeredValue = 0;

            foreach ($offer as $itemId => $quantity) {
                $inventoryItem = $inventoryItems->get($itemId);

                if ($inventoryItem?->item === null || $inventoryItem->item->is_quest_item || $inventoryItem->quantity < $quantity) {
                    throw new AuthorizationException('You do not carry everything you offered.');
                }

                $offeredValue += $inventoryItem->item->barterValue() * $quantity;
            }

            $demandValue = $encounter->troubleType->demand_value;

            if ($offeredValue < $demandValue) {
                throw new AuthorizationException("The {$encounter->troubleType->name} want goods worth {$demandValue}. Your offer is only worth {$offeredValue}.");
            }

            foreach ($offer as $itemId => $quantity) {
                $inventoryItem = $inventoryItems->get($itemId);

                if ($inventoryItem->quantity === $quantity) {
                    $inventoryItem->delete();
                } else {
                    $inventoryItem->decrement('quantity', $quantity);
                }
            }

            $encounter->delete();

            $handedOver = collect($offer)
                ->map(fn (int $quantity, int $itemId): string => "{$quantity}x {$inventoryItems->get($itemId)->item->name}")
                ->implode(', ');

            return [$encounter, $handedOver];
        });

        return "You handed over {$handedOver}, and the {$encounter->troubleType->name} let you through.".$this->carryOnAlongRoute($user, $encounter);
    }

    /**
     * Turn back to the city the survivor came from. The trip runs on the normal travel timer
     * and cannot be canceled.
     *
     * @throws AuthorizationException
     */
    public function runAway(User $user): City
    {
        $this->authorizeTask($user, 'city_action_perform');

        return DB::transaction(function () use ($user): City {
            $encounter = $this->lockedEncounterFor($user);
            $origin = $encounter->originCity;

            if ($origin === null || $origin->trashed()) {
                throw new AuthorizationException('There is nowhere to run back to. You will have to fight or pay.');
            }

            $this->startUserWork->forRetreat($user, $origin);

            $encounter->delete();
            $user->unsetRelation('troubleEncounter');

            return $origin;
        });
    }

    /**
     * The chance of winning a fight, from the survivor's combat skill against the trouble and their health.
     */
    public function winChancePercent(User $user, TroubleType $troubleType): int
    {
        $skillLevel = $user->skillFor($troubleType->skill_key)?->level ?? 1;
        $healthAboveHalf = (int) $user->health - intdiv((int) config('survival.maximum'), 2);

        $chance = (int) config('trouble.fight.base_win_chance_percent')
            + ($skillLevel - $troubleType->fighter_level) * (int) config('trouble.fight.win_chance_per_level_percent')
            + intdiv($healthAboveHalf, max(1, (int) config('trouble.fight.health_points_per_win_chance_percent')));

        return max(
            (int) config('trouble.fight.minimum_win_chance_percent'),
            min((int) config('trouble.fight.maximum_win_chance_percent'), $chance),
        );
    }

    /**
     * Everything the survivor could hand over to buy their way in. Quest items stay with them.
     *
     * @return list<array{item_id: int, name: string, rarity: string, owned: int, value: int}>
     */
    public function barterGoodsFor(User $user): array
    {
        return $user->inventoryItems()
            ->with('item')
            ->where('quantity', '>', 0)
            ->get()
            ->filter(fn (InventoryItem $inventoryItem): bool => $inventoryItem->item !== null && ! $inventoryItem->item->is_quest_item)
            ->sortBy(fn (InventoryItem $inventoryItem): string => $inventoryItem->item->name)
            ->map(fn (InventoryItem $inventoryItem): array => [
                'item_id' => $inventoryItem->item->id,
                'name' => $inventoryItem->item->name,
                'rarity' => $inventoryItem->item->rarity->label(),
                'owned' => $inventoryItem->quantity,
                'value' => $inventoryItem->item->barterValue(),
            ])
            ->values()
            ->all();
    }

    /**
     * @throws AuthorizationException
     */
    private function lockedEncounterFor(User $user): TroubleEncounter
    {
        $encounter = TroubleEncounter::query()
            ->with(['troubleType.skill', 'troubleType.lootItem', 'originCity'])
            ->whereBelongsTo($user)
            ->lockForUpdate()
            ->first();

        if ($encounter === null) {
            throw new AuthorizationException('There is no trouble to deal with.');
        }

        return $encounter;
    }

    private function skillProgressFor(User $user, TroubleType $troubleType): UserSkill
    {
        $user->loadMissing('skills.skill');

        return $user->skillFor($troubleType->skill_key) ?? $user->skills()->create([
            'skill_id' => Skill::query()->where('key', $troubleType->skill_key)->firstOrFail()->id,
            'level' => 1,
            'xp' => 0,
        ]);
    }

    /**
     * @return string|null What was looted, such as "+2 Metal Parts".
     */
    private function giveLoot(User $user, TroubleType $troubleType): ?string
    {
        $lootItem = $troubleType->lootItem;

        if (! $lootItem instanceof Item || $lootItem->trashed() || $troubleType->loot_quantity <= 0) {
            return null;
        }

        $user->inventoryItems()->firstOrCreate(['item_id' => $lootItem->id], ['quantity' => 0])
            ->increment('quantity', $troubleType->loot_quantity);

        return "+{$troubleType->loot_quantity} {$lootItem->name}";
    }

    /**
     * Set off on whatever was left of the survivor's route once the way is clear.
     */
    private function carryOnAlongRoute(User $user, TroubleEncounter $encounter): string
    {
        $queuedCityIds = $encounter->queuedCityIds();

        if ($queuedCityIds === []) {
            return '';
        }

        $user->unsetRelation('troubleEncounter');
        $destination = City::query()->find(end($queuedCityIds));

        try {
            if ($destination === null) {
                throw new AuthorizationException('The rest of your route is no longer available.');
            }

            $work = $this->startUserWork->forTravel($user, $destination, array_slice($queuedCityIds, 0, -1));
        } catch (AuthorizationException $exception) {
            return " You could not carry on along your route. {$exception->getMessage()}";
        }

        return " You set off for {$work->destinationCity?->city} along the rest of your route.";
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeTask(User $user, string $taskKey): void
    {
        $message = $user->denialReasonForTask($taskKey);

        if ($message !== null) {
            throw new AuthorizationException($message);
        }
    }
}
