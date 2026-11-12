<?php

namespace App\Actions\Game;

use App\FightOutcome;
use App\HarmCause;
use App\Models\CityAction;
use App\Models\Enemy;
use App\Models\Item;
use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Lottery;

/**
 * Fights run on the work loop, one round per cycle. The survivor's combat skill, health and best
 * carried weapon decide how hard they hit and how much of each blow they turn aside. Nothing a
 * survivor can buy changes the odds.
 */
class FightEnemy
{
    public function __construct(
        public PerformCityAction $performCityAction,
        public TrackSurvivalNeeds $trackSurvivalNeeds,
    ) {}

    /**
     * A random enemy waiting in the action's city that tests the action's skill, or null when the action is not a fight.
     */
    public function chooseEnemy(CityAction $action): ?Enemy
    {
        if (! in_array($action->skill_key, Enemy::COMBAT_SKILLS, true)) {
            return null;
        }

        return Enemy::query()
            ->where('city_id', $action->city_id)
            ->where('skill_key', $action->skill_key)
            ->inRandomOrder()
            ->first();
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(User $user): void
    {
        if (! $user->canPerformTask('combat_initiate')) {
            throw new AuthorizationException('You are not allowed to start fights.');
        }

        if ($user->health <= $this->fleeAtHealth()) {
            throw new AuthorizationException('You are too hurt to fight. Eat something and heal up first.');
        }
    }

    /**
     * Fight one round with the work loop's enemy. Winning grants the enemy's XP and loot and lines up the
     * next enemy. Returns null when no enemy is left to fight, so the cycle pays out as ordinary work.
     *
     * @return array{outcome: FightOutcome, summary: string, xp: int}|null
     *
     * @throws AuthorizationException
     */
    public function round(User $user, UserWork $work): ?array
    {
        $enemy = $work->enemy ?? $this->engage($work, $this->chooseEnemy($work->cityAction));

        if ($enemy === null) {
            return null;
        }

        $skillProgress = $this->performCityAction->authorize($user, $work->cityAction);
        $weapon = $this->bestWeaponCarriedBy($user);
        $damageDealt = $this->survivorDamage($user, $skillProgress->level, $weapon);
        $enemyHealth = max(0, ($work->enemy_health ?? $enemy->health) - $damageDealt);
        $strike = $weapon === null ? "you hit for {$damageDealt}" : "you hit for {$damageDealt} with your {$weapon->name}";

        if ($enemyHealth === 0) {
            $levelsGained = $this->performCityAction->applyExperience($skillProgress, $enemy->xp_reward);
            $user->load('skills.skill');
            $spoils = collect(["+{$enemy->xp_reward} XP", ...$this->grantLoot($user, $enemy)]);

            if ($levelsGained > 0) {
                $spoils->push("{$levelsGained} level gained");
            }

            $this->engage($work, $this->chooseEnemy($work->cityAction));

            return [
                'outcome' => FightOutcome::Won,
                'summary' => "You brought down the {$enemy->name}: {$strike} ({$spoils->implode(', ')}).",
                'xp' => $enemy->xp_reward,
            ];
        }

        $work->enemy_health = $enemyHealth;
        $damageTaken = $this->enemyDamage($enemy, $skillProgress->level);

        if ($this->trackSurvivalNeeds->harm($user, $damageTaken, HarmCause::Combat)) {
            return [
                'outcome' => FightOutcome::Died,
                'summary' => "The {$enemy->name} struck you down.",
                'xp' => 0,
            ];
        }

        if ($user->health <= $this->fleeAtHealth()) {
            return [
                'outcome' => FightOutcome::Fled,
                'summary' => "The {$enemy->name} hit you for {$damageTaken} and you fled with {$user->health} health left. No reward for that fight.",
                'xp' => 0,
            ];
        }

        return [
            'outcome' => FightOutcome::Ongoing,
            'summary' => "Fighting the {$enemy->name}: {$strike}, it hit you for {$damageTaken}. It has {$enemyHealth}/{$enemy->health} health left.",
            'xp' => 0,
        ];
    }

    /**
     * The carried item with the highest attack bonus, if any.
     */
    public function bestWeaponCarriedBy(User $user): ?Item
    {
        return Item::query()
            ->where('attack_bonus', '>', 0)
            ->whereIn('id', $user->inventoryItems()->where('quantity', '>', 0)->select('item_id'))
            ->orderByDesc('attack_bonus')
            ->first();
    }

    /**
     * Point the work loop at a fresh enemy at full health, or at none.
     */
    private function engage(UserWork $work, ?Enemy $enemy): ?Enemy
    {
        $work->enemy()->associate($enemy);
        $work->enemy_health = $enemy?->health;

        return $enemy;
    }

    /**
     * A base roll plus skill and weapon, landing softer the more hurt the survivor is.
     */
    private function survivorDamage(User $user, int $skillLevel, ?Item $weapon): int
    {
        $roll = random_int((int) config('combat.survivor.base_damage_min'), (int) config('combat.survivor.base_damage_max'))
            + $skillLevel * (int) config('combat.survivor.damage_per_skill_level')
            + ($weapon?->attack_bonus ?? 0);

        $strengthPercent = 50 + 50 * $user->health / max(1, (int) config('survival.maximum'));

        return max(1, (int) round($roll * $strengthPercent / 100));
    }

    private function enemyDamage(Enemy $enemy, int $skillLevel): int
    {
        $blockedPercent = min(
            (int) config('combat.survivor.maximum_blocked_percent'),
            $skillLevel * (int) config('combat.survivor.blocked_percent_per_skill_level'),
        );

        return (int) round(random_int($enemy->min_damage, max($enemy->min_damage, $enemy->max_damage)) * (100 - $blockedPercent) / 100);
    }

    /**
     * Roll each entry of the enemy's loot table and put what drops in the survivor's pack.
     *
     * @return list<string>
     */
    private function grantLoot(User $user, Enemy $enemy): array
    {
        $drops = collect($enemy->lootTable())
            ->filter(fn (array $entry): bool => Lottery::odds((int) $entry['chance_percent'], 100)->choose());

        if ($drops->isEmpty()) {
            return [];
        }

        $itemsByKey = Item::query()->whereIn('key', $drops->pluck('item_key'))->get()->keyBy('key');

        return $drops
            ->filter(fn (array $entry): bool => $itemsByKey->has($entry['item_key']))
            ->map(function (array $entry) use ($user, $itemsByKey): string {
                $item = $itemsByKey[$entry['item_key']];
                $quantity = max(1, (int) $entry['quantity']);

                $user->inventoryItems()->firstOrCreate(['item_id' => $item->id], ['quantity' => 0])->increment('quantity', $quantity);

                return "+{$quantity} {$item->name}";
            })
            ->values()
            ->all();
    }

    private function fleeAtHealth(): int
    {
        return (int) config('combat.flee_at_health');
    }
}
