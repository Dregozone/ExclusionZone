<?php

namespace App\Actions\Game;

use App\Events\SurvivorDied;
use App\HarmCause;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrackSurvivalNeeds
{
    /**
     * Everything shown as the survivor's condition. Each one can slow work timers.
     */
    public const NEEDS = ['health', 'hunger', 'fatigue'];

    private const DEFAULT_LABELS = ['health' => 'Healthy', 'hunger' => 'Fed', 'fatigue' => 'Rested'];

    /**
     * Add hunger and fatigue from finished work or travel. Resting starts over from now.
     * Pushing on while starving or spent then costs health.
     *
     * @return bool Whether the survivor died from it.
     */
    public function exert(User $user, float $hunger, float $fatigue): bool
    {
        $this->change($user, $hunger, $fatigue, 0, restartsRest: true);

        $harms = [
            [HarmCause::Starvation, $user->hunger >= $this->maximum() ? (int) config('survival.health.lost_while_starving') : 0],
            [HarmCause::Exhaustion, $user->fatigue >= $this->maximum() ? (int) config('survival.health.lost_while_spent') : 0],
        ];

        foreach ($harms as [$cause, $healthLost]) {
            if ($this->harm($user, $healthLost, $cause)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Take hunger and fatigue away and give health back, such as when the survivor eats.
     */
    public function relieve(User $user, float $hunger, float $fatigue, int $health = 0): void
    {
        $this->change($user, -$hunger, -$fatigue, $health, restartsRest: false);
    }

    /**
     * Take health away. Reaching 0 fires the SurvivorDied event, whose listeners decide what death means.
     *
     * @return bool Whether the survivor died from it.
     */
    public function harm(User $user, int $amount, HarmCause $cause): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $died = DB::transaction(function () use ($user, $amount): bool {
            $current = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail(['id', 'health']);
            $health = max(0, $current->health - $amount);

            $user->forceFill(['health' => $health])->save();

            return $current->health > 0 && $health === 0;
        });

        if ($died) {
            SurvivorDied::dispatch($user, $cause);
        }

        return $died;
    }

    /**
     * Let fatigue fall, and health come back unless starving, for every whole minute the survivor has spent without active work.
     */
    public function rest(User $user): void
    {
        if ($user->activeWork()->exists()) {
            return;
        }

        $restedAt = $user->rested_at;
        $healsWhileResting = $this->healsWhileResting($user);

        if ($restedAt === null || ($user->fatigue <= 0 && ! $healsWhileResting)) {
            $user->forceFill(['rested_at' => now()])->save();

            return;
        }

        $minutesRested = (int) floor($restedAt->diffInSeconds(now(), true) / 60);

        if ($minutesRested === 0) {
            return;
        }

        $recovered = $minutesRested * (float) config('survival.rest.fatigue_recovered_per_minute');

        $healed = $healsWhileResting ? $minutesRested * (int) config('survival.rest.health_recovered_per_minute') : 0;

        $user->forceFill([
            'fatigue' => $this->clampNeed($user->fatigue - $recovered),
            'health' => $this->clamp($user->health + $healed),
            'rested_at' => $restedAt->addMinutes($minutesRested),
        ])->save();
    }

    /**
     * Resting heals a hurt survivor who is not starving.
     */
    private function healsWhileResting(User $user): bool
    {
        return (int) config('survival.rest.health_recovered_per_minute') > 0
            && $user->health > 0
            && $user->health < $this->maximum()
            && $user->hunger < $this->maximum();
    }

    /**
     * Resting counts from now, such as when the survivor stops work part way through.
     */
    public function startResting(User $user): void
    {
        $user->forceFill(['rested_at' => now()])->save();
    }

    /**
     * How much longer every work timer runs because of the survivor's health, hunger and fatigue.
     */
    public function timerIncreasePercent(User $user): int
    {
        return collect(self::NEEDS)->sum(fn (string $need): int => $this->thresholdReached($need, (float) $user->{$need})['timer_increase_percent'] ?? 0);
    }

    /**
     * Health is a whole number; hunger and fatigue move in tenths.
     *
     * @return array{value: int|float, maximum: int, label: string, timer_increase_percent: int}
     */
    public function condition(User $user, string $need): array
    {
        $value = $need === 'health' ? (int) $user->health : $this->roundNeed((float) $user->{$need});
        $threshold = $this->thresholdReached($need, $value);

        return [
            'value' => $value,
            'maximum' => $this->maximum(),
            'label' => $threshold['label'] ?? self::DEFAULT_LABELS[$need],
            'timer_increase_percent' => $threshold['timer_increase_percent'] ?? 0,
        ];
    }

    /**
     * Hunger and fatigue thresholds apply at or above their value; health thresholds at or below it.
     *
     * @return array{at: int, label: string, timer_increase_percent: int}|null
     */
    private function thresholdReached(string $need, int|float $value): ?array
    {
        $thresholds = collect(config("survival.thresholds.{$need}", []))->sortBy('at');

        if ($need === 'health') {
            return $thresholds->first(fn (array $threshold): bool => $value <= $threshold['at']);
        }

        return $thresholds->filter(fn (array $threshold): bool => $value >= $threshold['at'])->last();
    }

    private function change(User $user, float $hungerChange, float $fatigueChange, int $healthChange, bool $restartsRest): void
    {
        DB::transaction(function () use ($user, $hungerChange, $fatigueChange, $healthChange, $restartsRest): void {
            $current = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail(['id', 'health', 'hunger', 'fatigue']);

            $user->forceFill([
                'health' => $this->clamp($current->health + $healthChange),
                'hunger' => $this->clampNeed($current->hunger + $hungerChange),
                'fatigue' => $this->clampNeed($current->fatigue + $fatigueChange),
                'rested_at' => $restartsRest ? now() : ($user->rested_at ?? now()),
            ])->save();
        });
    }

    private function clamp(int $value): int
    {
        return max(0, min($this->maximum(), $value));
    }

    /**
     * Keep hunger or fatigue within range, to the nearest tenth.
     */
    private function clampNeed(float $value): float
    {
        return $this->roundNeed(max(0.0, min((float) $this->maximum(), $value)));
    }

    private function roundNeed(float $value): float
    {
        return round($value, 1);
    }

    private function maximum(): int
    {
        return (int) config('survival.maximum');
    }
}
