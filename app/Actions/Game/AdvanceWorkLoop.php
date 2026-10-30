<?php

namespace App\Actions\Game;

use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AdvanceWorkLoop
{
    /**
     * How long a work loop may run before the player has to restart it.
     */
    public const MAX_LOOP_HOURS = 8;

    public function __construct(
        public PerformCityAction $performCityAction,
        public CalculateWorkDuration $calculateWorkDuration,
        public RecordQuestActionProgress $recordQuestActionProgress,
    ) {}

    /**
     * Settle every cycle of the player's city action whose timer has run out, restarting
     * the timer after each one. A cycle that can't be followed by another ends the loop.
     *
     * @return array{cycles:int,last_status:?string,loop_ended:bool}
     */
    public function __invoke(User $user): array
    {
        $cycles = 0;
        $lastStatus = null;

        while (true) {
            $cycle = DB::transaction(fn (): ?array => $this->settleNextCycle($user));

            if ($cycle === null) {
                return ['cycles' => $cycles, 'last_status' => $lastStatus, 'loop_ended' => false];
            }

            $lastStatus = $cycle['status'];
            $cycles += $cycle['rewarded'] ? 1 : 0;

            if ($cycle['loop_ended']) {
                return ['cycles' => $cycles, 'last_status' => $lastStatus, 'loop_ended' => true];
            }
        }
    }

    /**
     * @return array{status:string,rewarded:bool,loop_ended:bool}|null
     */
    private function settleNextCycle(User $user): ?array
    {
        $work = UserWork::query()
            ->with('cityAction.skill')
            ->whereBelongsTo($user)
            ->where('work_type', 'city_action')
            ->where('available_at', '<=', now())
            ->lockForUpdate()
            ->first();

        if ($work === null) {
            return null;
        }

        // Another request may have settled a cycle since these relations were loaded.
        $user->load('location.city', 'skills.skill');

        try {
            $cycle = $this->rewardCycle($user, $work);
        } catch (AuthorizationException $exception) {
            return $this->endLoop($user, $work, $exception->getMessage(), $exception->getMessage(), false);
        }

        if (! $cycle['rewarded']) {
            return $this->endLoop($user, $work, $cycle['status'], $cycle['status'], false);
        }

        $work->cycles_completed++;
        $work->xp_earned += $cycle['xp'];
        $work->last_cycle_summary = $cycle['status'];

        $stopReason = $this->reasonLoopCannotContinue($user, $work);

        if ($stopReason !== null) {
            return $this->endLoop($user, $work, $cycle['status'], $stopReason, true);
        }

        $duration = $this->calculateWorkDuration->forCityAction($user, $work->cityAction);

        $work->fill([
            'duration_seconds' => $duration,
            'weather' => $this->calculateWorkDuration->currentWeather($user),
            'available_at' => $work->available_at->addSeconds($duration),
        ])->save();

        return ['status' => $cycle['status'], 'rewarded' => true, 'loop_ended' => false];
    }

    /**
     * @return array{status:string,rewarded:bool,xp:int}
     *
     * @throws AuthorizationException
     */
    private function rewardCycle(User $user, UserWork $work): array
    {
        $action = $work->cityAction;

        if ($action === null) {
            throw new AuthorizationException('That action is no longer available.');
        }

        $missingItems = $this->performCityAction->missingRequiredItems($user, $action);

        if ($missingItems !== []) {
            return [
                'status' => 'Action failed: you no longer have '.$this->performCityAction->describeRequiredItems($missingItems).'. No rewards were granted.',
                'rewarded' => false,
                'xp' => 0,
            ];
        }

        $weather = $work->weather;
        $reward = ($this->performCityAction)($user, $action, $weather);

        $summary = $reward['items_used'] === ''
            ? "Action completed: +{$reward['xp']} XP"
            : "Action completed: used {$reward['items_used']}, +{$reward['xp']} XP";

        if ($weather->isBad()) {
            $summary .= " (+{$weather->experienceBonusPercent()}% {$weather->label()} bonus)";
        }

        if ($reward['item_name'] !== null) {
            $summary .= $reward['item_lost']
                ? ", {$reward['quantity']} {$reward['item_name']} lost to the {$weather->value}"
                : ", +{$reward['quantity']} {$reward['item_name']}";
        }

        if ($reward['levels_gained'] > 0) {
            $summary .= ", {$reward['levels_gained']} level gained";
        }

        $summary .= '.';

        $questProgress = ($this->recordQuestActionProgress)($user, $action);

        if ($questProgress !== []) {
            $summary .= ' Job progress: '.implode(', ', $questProgress).'.';
        }

        return ['status' => $summary, 'rewarded' => true, 'xp' => $reward['xp']];
    }

    private function reasonLoopCannotContinue(User $user, UserWork $work): ?string
    {
        $denialReason = $user->denialReasonForTask('city_action_perform');

        if ($denialReason !== null) {
            return $denialReason;
        }

        try {
            $this->performCityAction->authorize($user, $work->cityAction);
        } catch (AuthorizationException $exception) {
            return $exception->getMessage();
        }

        $nextCycleEndsAt = $work->available_at->addSeconds(
            $this->calculateWorkDuration->forCityAction($user, $work->cityAction),
        );

        return $nextCycleEndsAt->greaterThan($work->created_at->addHours(self::MAX_LOOP_HOURS))
            ? 'You reached the '.self::MAX_LOOP_HOURS.' hour limit. Start the work again to keep going.'
            : null;
    }

    /**
     * @return array{status:string,rewarded:bool,loop_ended:bool}
     */
    private function endLoop(User $user, UserWork $work, string $status, string $reason, bool $rewarded): array
    {
        $user->forceFill([
            'last_work_report' => $work->cycles_completed > 0
                ? "{$work->cityAction?->label} stopped after {$work->cycles_completed} ".str('cycle')->plural($work->cycles_completed)." (+{$work->xp_earned} XP). {$reason}"
                : "{$work->cityAction?->label} stopped. {$reason}",
        ])->save();

        $work->delete();

        return ['status' => $status, 'rewarded' => $rewarded, 'loop_ended' => true];
    }
}
