<?php

namespace App\Actions\Game;

use App\ItemRarity;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Lottery;

/**
 * The Brain Scorcher in Pripyat burns an offering of items and may hand back something
 * worse, the same, something better, a Scorched jackpot or nothing at all.
 */
class FeedBrainScorcher
{
    public const CITY_NAME = 'Pripyat';

    public const BASE_DURATION_SECONDS = 600;

    /**
     * Offering more copies than this no longer improves the odds.
     */
    public const MAX_EFFECTIVE_QUANTITY = 16;

    /**
     * Outcomes in the order they are rolled; whatever is left over burns the offering for nothing.
     */
    public const OUTCOMES = ['jackpot', 'upgrade', 'same', 'downgrade', 'nothing'];

    /**
     * @return array{outcome: string, reward: ?Item, offered: Item, quantity: int}
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Item $item, int $quantity): array
    {
        $this->authorize($user, $item, $quantity);

        return DB::transaction(function () use ($user, $item, $quantity): array {
            $this->consumeOffering($user, $item, $quantity);

            $outcome = $this->rollOutcome($this->oddsFor($item->rarity, $quantity));
            $reward = $this->rewardFor($outcome, $item);

            if ($reward === null) {
                $outcome = 'nothing';
            } else {
                if ($outcome === 'upgrade' && $reward->rarity->isScorcherExclusive()) {
                    $outcome = 'jackpot';
                }

                $inventoryItem = $user->inventoryItems()->firstOrCreate(
                    ['item_id' => $reward->id],
                    ['quantity' => 0],
                );

                $inventoryItem->increment('quantity');
            }

            return [
                'outcome' => $outcome,
                'reward' => $reward,
                'offered' => $item,
                'quantity' => $quantity,
            ];
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(User $user, Item $item, int $quantity): void
    {
        $user->loadMissing('location.city');

        if (! $this->isAvailableIn($user->location?->city?->city)) {
            throw new AuthorizationException('The Brain Scorcher is only found in '.self::CITY_NAME.'.');
        }

        if ($quantity < 1) {
            throw new AuthorizationException('Offer at least one item to the Brain Scorcher.');
        }

        if ($this->ownedQuantity($user, $item) < $quantity) {
            throw new AuthorizationException('You need '.$this->describeOffering($item, $quantity).' to make that offering.');
        }
    }

    public function isAvailableIn(?string $cityName): bool
    {
        return $cityName === self::CITY_NAME;
    }

    /**
     * Percent chance of each outcome. Rarer items and bigger offerings run hotter,
     * which raises the chance of a better item or a Scorched jackpot.
     *
     * @return array{jackpot: int, upgrade: int, same: int, downgrade: int, nothing: int}
     */
    public function oddsFor(ItemRarity $rarity, int $quantity): array
    {
        $effectiveQuantity = max(1, min(self::MAX_EFFECTIVE_QUANTITY, $quantity));
        $heat = ($rarity->rank() * 5) + ((int) floor(log($effectiveQuantity, 2)) * 5);

        $odds = [
            'jackpot' => 1 + intdiv($heat, 5),
            'upgrade' => 10 + intdiv($heat, 2),
            'same' => 20,
            'downgrade' => $rarity->lower() === null ? 0 : 20,
        ];

        return [...$odds, 'nothing' => 100 - array_sum($odds)];
    }

    public function describeOffering(Item $item, int $quantity): string
    {
        return $quantity.'x '.$item->name;
    }

    public function ownedQuantity(User $user, Item $item): int
    {
        return (int) $user->inventoryItems()->where('item_id', $item->id)->value('quantity');
    }

    /**
     * @param  array{jackpot: int, upgrade: int, same: int, downgrade: int, nothing: int}  $odds
     */
    private function rollOutcome(array $odds): string
    {
        $remainingChance = 100;

        foreach (self::OUTCOMES as $outcome) {
            if ($outcome === 'nothing') {
                break;
            }

            if ($odds[$outcome] > 0 && Lottery::odds($odds[$outcome], $remainingChance)->choose()) {
                return $outcome;
            }

            $remainingChance -= $odds[$outcome];
        }

        return 'nothing';
    }

    private function rewardFor(string $outcome, Item $offered): ?Item
    {
        return match ($outcome) {
            'jackpot' => $this->randomItemOf(ItemRarity::Scorched),
            'upgrade' => $this->randomItemOf($offered->rarity->higher()),
            'same' => $offered,
            'downgrade' => $offered->rarity->lower() === null ? null : $this->randomItemOf($offered->rarity->lower()),
            default => null,
        };
    }

    private function randomItemOf(ItemRarity $rarity): ?Item
    {
        return Item::query()->where('rarity', $rarity)->inRandomOrder()->first();
    }

    /**
     * @throws AuthorizationException
     */
    private function consumeOffering(User $user, Item $item, int $quantity): void
    {
        $inventoryItem = $user->inventoryItems()
            ->where('item_id', $item->id)
            ->lockForUpdate()
            ->first();

        if ($inventoryItem === null || $inventoryItem->quantity < $quantity) {
            throw new AuthorizationException('You need '.$this->describeOffering($item, $quantity).' to make that offering.');
        }

        if ($inventoryItem->quantity <= $quantity) {
            $inventoryItem->delete();
        } else {
            $inventoryItem->decrement('quantity', $quantity);
        }
    }
}
