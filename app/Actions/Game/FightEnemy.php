<?php

namespace App\Actions\Game;

use App\FightOdds;
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
 * Fights run on the work loop, one round per cycle. Each side's blow lands on a roll against its hit
 * chance: the survivor's rises with combat skill and falls with the enemy's defence, and the enemy's
 * is its accuracy, lowered by the survivor's skill and best carried armour. A landed blow hits for a
 * roll up to the striker's maximum, the survivor's raised by skill and their best carried weapon.
 * Nothing a survivor can buy changes the odds.
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
     * Fight one round with the work loop's enemy. Winning grants the enemy's XP and loot, which is the whole
     * reward for the fight, and lines up the next enemy. Returns null when no enemy is left to fight, so the
     * cycle pays out as ordinary work.
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
        $damageDealt = $this->lands($this->survivorHitChancePercent($skillProgress->level, $enemy))
            ? $this->survivorDamage($user, $skillProgress->level, $weapon)
            : 0;
        $enemyHealth = max(0, ($work->enemy_health ?? $enemy->health) - $damageDealt);
        $strike = match (true) {
            $damageDealt === 0 => 'you missed',
            $weapon === null => "you hit for {$damageDealt}",
            default => "you hit for {$damageDealt} with your {$weapon->name}",
        };

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
        $damageTaken = $this->lands($this->enemyHitChancePercent($enemy, $skillProgress->level, $this->bestArmourCarriedBy($user)))
            ? $this->enemyDamage($enemy, $skillProgress->level)
            : 0;
        $retort = $damageTaken === 0 ? 'it missed you' : "it hit you for {$damageTaken}";

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
                'summary' => "Fighting the {$enemy->name}: {$strike}, {$retort}, and you fled with {$user->health} health left. No reward for that fight.",
                'xp' => 0,
            ];
        }

        return [
            'outcome' => FightOutcome::Ongoing,
            'summary' => "Fighting the {$enemy->name}: {$strike}, {$retort}. It has {$enemyHealth}/{$enemy->health} health left.",
            'xp' => 0,
        ];
    }

    /**
     * How a fight with the enemy is likely to go for the survivor as they are now: each side's chance to
     * land a blow, the enemy's biggest hit, and how many rounds and how much health the fight should take
     * from the average blow each side lands.
     *
     * @return array{survivor_hit_chance_percent: int, enemy_hit_chance_percent: int, enemy_max_hit: int, expected_rounds: int, expected_health_lost: int, odds: FightOdds}
     */
    public function odds(User $user, Enemy $enemy): array
    {
        $skillLevel = $user->skillFor($enemy->skill_key)?->level ?? 1;
        $weapon = $this->bestWeaponCarriedBy($user);
        $survivorHitChance = $this->survivorHitChancePercent($skillLevel, $enemy);
        $enemyHitChance = $this->enemyHitChancePercent($enemy, $skillLevel, $this->bestArmourCarriedBy($user));
        $enemyMaxHit = max($enemy->min_damage, $enemy->max_damage);

        $averageBaseRoll = ((int) config('combat.survivor.base_damage_min') + (int) config('combat.survivor.base_damage_max')) / 2;
        $averageDamageDealt = max(1, $this->survivorBlow($user, $averageBaseRoll, $skillLevel, $weapon)) * $survivorHitChance / 100;
        $averageDamageTaken = $this->blowAfterBlocking(($enemy->min_damage + $enemyMaxHit) / 2, $skillLevel) * $enemyHitChance / 100;

        $expectedRounds = (int) ceil($enemy->health / max(0.01, $averageDamageDealt));
        $expectedHealthLost = (int) round($averageDamageTaken * ($expectedRounds - 1));
        $healthToSpare = max(1, $user->health - $this->fleeAtHealth());

        return [
            'survivor_hit_chance_percent' => $survivorHitChance,
            'enemy_hit_chance_percent' => $enemyHitChance,
            'enemy_max_hit' => $enemyMaxHit,
            'expected_rounds' => $expectedRounds,
            'expected_health_lost' => $expectedHealthLost,
            'odds' => FightOdds::forHealthCostPercent($expectedHealthLost / $healthToSpare * 100),
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
     * The carried item with the highest defence bonus, if any.
     */
    public function bestArmourCarriedBy(User $user): ?Item
    {
        return Item::query()
            ->where('defence_bonus', '>', 0)
            ->whereIn('id', $user->inventoryItems()->where('quantity', '>', 0)->select('item_id'))
            ->orderByDesc('defence_bonus')
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
     * The survivor's chance to land a blow: the base chance plus skill, less the enemy's defence.
     */
    private function survivorHitChancePercent(int $skillLevel, Enemy $enemy): int
    {
        return $this->withinHitChanceLimits(
            (int) config('combat.survivor.base_hit_chance_percent')
            + $skillLevel * (int) config('combat.survivor.hit_chance_percent_per_skill_level')
            - $enemy->defence,
        );
    }

    /**
     * The enemy's chance to land a blow: its accuracy, less the survivor's skill and armour.
     */
    private function enemyHitChancePercent(Enemy $enemy, int $skillLevel, ?Item $armour): int
    {
        return $this->withinHitChanceLimits(
            $enemy->accuracy
            - $skillLevel * (int) config('combat.survivor.evasion_percent_per_skill_level')
            - ($armour?->defence_bonus ?? 0),
        );
    }

    private function withinHitChanceLimits(int $percent): int
    {
        return max((int) config('combat.hit_chance.minimum_percent'), min((int) config('combat.hit_chance.maximum_percent'), $percent));
    }

    private function lands(int $hitChancePercent): bool
    {
        return random_int(1, 100) <= $hitChancePercent;
    }

    /**
     * A base roll plus skill and weapon, landing softer the more hurt the survivor is.
     */
    private function survivorDamage(User $user, int $skillLevel, ?Item $weapon): int
    {
        $baseRoll = random_int((int) config('combat.survivor.base_damage_min'), (int) config('combat.survivor.base_damage_max'));

        return max(1, (int) round($this->survivorBlow($user, $baseRoll, $skillLevel, $weapon)));
    }

    private function survivorBlow(User $user, float $baseRoll, int $skillLevel, ?Item $weapon): float
    {
        $roll = $baseRoll
            + $skillLevel * (int) config('combat.survivor.damage_per_skill_level')
            + ($weapon?->attack_bonus ?? 0);

        $strengthPercent = 50 + 50 * $user->health / max(1, (int) config('survival.maximum'));

        return $roll * $strengthPercent / 100;
    }

    private function enemyDamage(Enemy $enemy, int $skillLevel): int
    {
        return (int) round($this->blowAfterBlocking(random_int($enemy->min_damage, max($enemy->min_damage, $enemy->max_damage)), $skillLevel));
    }

    /**
     * What is left of an enemy blow once the survivor's skill has turned part of it aside.
     */
    private function blowAfterBlocking(float $damage, int $skillLevel): float
    {
        $blockedPercent = min(
            (int) config('combat.survivor.maximum_blocked_percent'),
            $skillLevel * (int) config('combat.survivor.blocked_percent_per_skill_level'),
        );

        return $damage * (100 - $blockedPercent) / 100;
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
