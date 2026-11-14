<?php

namespace App\Actions\Game;

use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every trade post has a bazaar that buys goods for scrip and sells a fixed stock. Barter skill gets
 * better prices both ways, and selling a stack at once adds a small bulk bonus. The rates are set in
 * config/shop.php so the bazaar never pays more for an item than it charges.
 */
class TradeAtBazaar
{
    /**
     * The items every bazaar sells, in the order they are listed.
     *
     * @return Collection<int, Item>
     */
    public function stock(): Collection
    {
        $keys = config('shop.stock', []);

        return Item::query()
            ->whereIn('key', $keys)
            ->where('base_price', '>', 0)
            ->get()
            ->sortBy(fn (Item $item): int => (int) array_search($item->key, $keys, true))
            ->values();
    }

    /**
     * Scrip the bazaar pays for this many of the item.
     */
    public function sellPrice(User $user, Item $item, int $quantity): int
    {
        if (! $item->isSellable() || $quantity < 1) {
            return 0;
        }

        $bonusPercent = $this->barterSellBonusPercent($user) + $this->bulkBonusPercent($quantity);

        return (int) floor($item->base_price * $quantity * (int) config('shop.sell_ratio_percent') / 100 * (100 + $bonusPercent) / 100);
    }

    /**
     * Scrip the bazaar charges for one of the item.
     */
    public function buyPrice(User $user, Item $item): int
    {
        $price = $item->base_price * (int) config('shop.buy_markup_percent') / 100 * (100 - $this->barterBuyDiscountPercent($user)) / 100;

        return max(1, (int) ceil($price));
    }

    /**
     * How much more each sale pays thanks to the survivor's Barter level.
     */
    public function barterSellBonusPercent(User $user): int
    {
        return min(
            (int) config('shop.maximum_barter_sell_bonus_percent'),
            ($this->barterLevel($user) - 1) * (int) config('shop.barter_sell_bonus_percent_per_level'),
        );
    }

    /**
     * How much less each purchase costs thanks to the survivor's Barter level.
     */
    public function barterBuyDiscountPercent(User $user): int
    {
        return min(
            (int) config('shop.maximum_barter_buy_discount_percent'),
            ($this->barterLevel($user) - 1) * (int) config('shop.barter_buy_discount_percent_per_level'),
        );
    }

    /**
     * A small extra for every item sold together after the first.
     */
    public function bulkBonusPercent(int $quantity): float
    {
        return min(
            (float) config('shop.maximum_bulk_bonus_percent'),
            max(0, $quantity - 1) * (int) config('shop.bulk_bonus_tenths_percent_per_item') / 10,
        );
    }

    /**
     * Sell items from the pack for scrip.
     *
     * @return array{scrip: int, barter_xp: int}
     *
     * @throws AuthorizationException
     */
    public function sell(User $user, Item $item, int $quantity): array
    {
        $this->authorize($user);

        if (! $item->isSellable()) {
            throw new AuthorizationException("The bazaar won't take {$item->name}.");
        }

        if ($quantity < 1) {
            throw new AuthorizationException('Choose how many to sell.');
        }

        return DB::transaction(function () use ($user, $item, $quantity): array {
            $inventoryItem = $user->inventoryItems()
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($inventoryItem === null || $inventoryItem->quantity < $quantity) {
                throw new AuthorizationException("You are not carrying {$quantity} {$item->name}.");
            }

            $scrip = $this->sellPrice($user, $item, $quantity);

            if ((int) $inventoryItem->quantity <= $quantity) {
                $inventoryItem->delete();
            } else {
                $inventoryItem->decrement('quantity', $quantity);
            }

            $user->increment('scrip', $scrip);

            return ['scrip' => $scrip, 'barter_xp' => $this->trainBarter($user, $scrip)];
        });
    }

    /**
     * Buy items from the bazaar's stock with scrip.
     *
     * @return array{scrip: int, barter_xp: int}
     *
     * @throws AuthorizationException
     */
    public function buy(User $user, Item $item, int $quantity): array
    {
        $this->authorize($user);

        if (! $this->stock()->contains('id', $item->id)) {
            throw new AuthorizationException("The bazaar doesn't sell {$item->name}.");
        }

        if ($quantity < 1) {
            throw new AuthorizationException('Choose how many to buy.');
        }

        return DB::transaction(function () use ($user, $item, $quantity): array {
            $cost = $this->buyPrice($user, $item) * $quantity;
            $scripHeld = (int) User::query()->whereKey($user->id)->lockForUpdate()->value('scrip');

            if ($scripHeld < $cost) {
                throw new AuthorizationException("You need {$cost} scrip for that and have {$scripHeld}.");
            }

            $user->forceFill(['scrip' => $scripHeld - $cost])->save();
            $user->inventoryItems()->firstOrCreate(['item_id' => $item->id], ['quantity' => 0])->increment('quantity', $quantity);

            return ['scrip' => $cost, 'barter_xp' => $this->trainBarter($user, $cost)];
        });
    }

    /**
     * Why the survivor can't use a bazaar right now, or null when they can.
     */
    public function restriction(User $user): ?string
    {
        $user->loadMissing('location.city', 'activeWork');

        return match (true) {
            ! ($user->location?->city?->has_trade_post ?? false) => 'There is no bazaar here. Find a trade post.',
            $user->activeWork?->isTravel() === true => 'You are on the road. Wait until you arrive.',
            $user->isHeldByTrouble() => 'Trouble is blocking your way. Deal with it before trading.',
            default => $user->denialReasonForTask('trade_create'),
        };
    }

    /**
     * @throws AuthorizationException
     */
    private function authorize(User $user): void
    {
        $restriction = $this->restriction($user);

        if ($restriction !== null) {
            throw new AuthorizationException($restriction);
        }
    }

    private function trainBarter(User $user, int $scrip): int
    {
        $xp = (int) ceil($scrip / max(1, (int) config('shop.scrip_per_barter_xp')));

        if ($xp > 0) {
            $this->barterSkill($user)?->gainExperience($xp);
        }

        return $xp;
    }

    private function barterLevel(User $user): int
    {
        return $this->barterSkill($user)?->level ?? 1;
    }

    private function barterSkill(User $user): ?UserSkill
    {
        $user->loadMissing('skills.skill');

        $skillProgress = $user->skillFor('barter');

        if ($skillProgress !== null) {
            return $skillProgress;
        }

        $skill = Skill::query()->where('key', 'barter')->first();

        if ($skill === null) {
            return null;
        }

        $skillProgress = $user->skills()->create(['skill_id' => $skill->id, 'level' => 1, 'xp' => 0]);
        $user->unsetRelation('skills');

        return $skillProgress;
    }
}
