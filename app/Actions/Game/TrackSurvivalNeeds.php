<?php

namespace App\Actions\Game;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrackSurvivalNeeds
{
    public const NEEDS = ['hunger', 'fatigue'];

    /**
     * Add hunger and fatigue from finished work or travel. Resting starts over from now.
     */
    public function exert(User $user, int $hunger, int $fatigue): void
    {
        $this->change($user, $hunger, $fatigue, restartsRest: true);
    }

    /**
     * Take hunger and fatigue away, such as when the survivor eats.
     */
    public function relieve(User $user, int $hunger, int $fatigue): void
    {
        $this->change($user, -$hunger, -$fatigue, restartsRest: false);
    }

    /**
     * Let fatigue fall for every whole minute the survivor has spent without active work.
     */
    public function rest(User $user): void
    {
        if ($user->activeWork()->exists()) {
            return;
        }

        $restedAt = $user->rested_at;

        if ($restedAt === null || $user->fatigue === 0) {
            $user->forceFill(['rested_at' => now()])->save();

            return;
        }

        $minutesRested = (int) floor($restedAt->diffInSeconds(now(), true) / 60);

        if ($minutesRested === 0) {
            return;
        }

        $recovered = $minutesRested * (int) config('survival.rest.fatigue_recovered_per_minute');

        $user->forceFill([
            'fatigue' => max(0, $user->fatigue - $recovered),
            'rested_at' => $restedAt->addMinutes($minutesRested),
        ])->save();
    }

    /**
     * Resting counts from now, such as when the survivor stops work part way through.
     */
    public function startResting(User $user): void
    {
        $user->forceFill(['rested_at' => now()])->save();
    }

    /**
     * How much longer every work timer runs because of the survivor's hunger and fatigue.
     */
    public function timerIncreasePercent(User $user): int
    {
        return collect(self::NEEDS)->sum(fn (string $need): int => $this->thresholdReached($need, (int) $user->{$need})['timer_increase_percent'] ?? 0);
    }

    /**
     * @return array{value: int, maximum: int, label: string, timer_increase_percent: int}
     */
    public function condition(User $user, string $need): array
    {
        $value = (int) $user->{$need};
        $threshold = $this->thresholdReached($need, $value);

        return [
            'value' => $value,
            'maximum' => $this->maximum(),
            'label' => $threshold['label'] ?? ($need === 'hunger' ? 'Fed' : 'Rested'),
            'timer_increase_percent' => $threshold['timer_increase_percent'] ?? 0,
        ];
    }

    /**
     * @return array{at: int, label: string, timer_increase_percent: int}|null
     */
    private function thresholdReached(string $need, int $value): ?array
    {
        return collect(config("survival.thresholds.{$need}", []))
            ->filter(fn (array $threshold): bool => $value >= $threshold['at'])
            ->sortBy('at')
            ->last();
    }

    private function change(User $user, int $hungerChange, int $fatigueChange, bool $restartsRest): void
    {
        DB::transaction(function () use ($user, $hungerChange, $fatigueChange, $restartsRest): void {
            $current = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail(['id', 'hunger', 'fatigue']);

            $user->forceFill([
                'hunger' => $this->clamp($current->hunger + $hungerChange),
                'fatigue' => $this->clamp($current->fatigue + $fatigueChange),
                'rested_at' => $restartsRest ? now() : ($user->rested_at ?? now()),
            ])->save();
        });
    }

    private function clamp(int $value): int
    {
        return max(0, min($this->maximum(), $value));
    }

    private function maximum(): int
    {
        return (int) config('survival.maximum');
    }
}
