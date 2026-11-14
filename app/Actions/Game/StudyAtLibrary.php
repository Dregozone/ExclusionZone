<?php

namespace App\Actions\Game;

use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * The library in Gdansk sells lessons in any skill. Survivors pay with goods, each worth its barter
 * value, and the lesson takes time. It costs more than learning by doing, but it gets a skill started.
 */
class StudyAtLibrary
{
    public const CITY_NAME = 'Gdansk';

    /**
     * Experience a lesson teaches for each point of barter value paid. A Common item (worth 1) buys 5 XP.
     */
    public const XP_PER_BARTER_VALUE = 5;

    public const SECONDS_PER_XP = 6;

    /**
     * The most experience a single lesson can teach. Bigger payments need more than one lesson.
     */
    public const MAX_XP_PER_LESSON = 500;

    /**
     * @return array{xp: int, levels_gained: int, level: int, skill: Skill, paid: Item, quantity: int}
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Skill $skill, Item $item, int $quantity): array
    {
        $this->authorize($user, $skill, $item, $quantity);

        return DB::transaction(function () use ($user, $skill, $item, $quantity): array {
            $this->consumePayment($user, $item, $quantity);

            $xp = $this->experienceFor($item, $quantity);
            $skillProgress = $this->skillProgressFor($user, $skill);
            $levelsGained = $skillProgress->gainExperience($xp);

            return [
                'xp' => $xp,
                'levels_gained' => $levelsGained,
                'level' => $skillProgress->level,
                'skill' => $skill,
                'paid' => $item,
                'quantity' => $quantity,
            ];
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(User $user, Skill $skill, Item $item, int $quantity): void
    {
        $user->loadMissing('location.city');

        if (! $this->isAvailableIn($user->location?->city?->city)) {
            throw new AuthorizationException('The library is only found in '.self::CITY_NAME.'.');
        }

        if ($quantity < 1) {
            throw new AuthorizationException('Pay with at least one item.');
        }

        if ($item->is_quest_item) {
            throw new AuthorizationException('The librarian will not take '.$item->name.'. Keep it for the job it is meant for.');
        }

        if ($this->ownedQuantity($user, $item) < $quantity) {
            throw new AuthorizationException('You need '.$this->describePayment($item, $quantity).' to pay for that lesson.');
        }

        if ($this->experienceFor($item, $quantity) > self::MAX_XP_PER_LESSON) {
            throw new AuthorizationException('One lesson teaches at most '.self::MAX_XP_PER_LESSON.' XP. Pay with fewer items.');
        }

        if ($skill->trashed()) {
            throw new AuthorizationException('Nobody teaches '.$skill->display_name.' any more.');
        }
    }

    public function isAvailableIn(?string $cityName): bool
    {
        return $cityName === self::CITY_NAME;
    }

    public function experienceFor(Item $item, int $quantity): int
    {
        return $item->barterValue() * self::XP_PER_BARTER_VALUE * max(0, $quantity);
    }

    /**
     * How many of an item fit in one lesson before it would teach more than the most a lesson can.
     */
    public function maxQuantityPerLesson(Item $item): int
    {
        return intdiv(self::MAX_XP_PER_LESSON, max(1, $item->barterValue() * self::XP_PER_BARTER_VALUE));
    }

    public function describePayment(Item $item, int $quantity): string
    {
        return $quantity.'x '.$item->name;
    }

    public function ownedQuantity(User $user, Item $item): int
    {
        return (int) $user->inventoryItems()->where('item_id', $item->id)->value('quantity');
    }

    private function skillProgressFor(User $user, Skill $skill): UserSkill
    {
        return $user->skills()->where('skill_id', $skill->id)->lockForUpdate()->first()
            ?? $user->skills()->create([
                'skill_id' => $skill->id,
                'level' => 1,
                'xp' => 0,
            ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function consumePayment(User $user, Item $item, int $quantity): void
    {
        $inventoryItem = $user->inventoryItems()
            ->where('item_id', $item->id)
            ->lockForUpdate()
            ->first();

        if ($inventoryItem === null || $inventoryItem->quantity < $quantity) {
            throw new AuthorizationException('You need '.$this->describePayment($item, $quantity).' to pay for that lesson.');
        }

        if ($inventoryItem->quantity <= $quantity) {
            $inventoryItem->delete();
        } else {
            $inventoryItem->decrement('quantity', $quantity);
        }
    }
}
