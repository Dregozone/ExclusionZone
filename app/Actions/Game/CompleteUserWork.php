<?php

namespace App\Actions\Game;

use App\Models\User;
use App\Models\UserWork;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CompleteUserWork
{
    public function __construct(
        public PerformCityAction $performCityAction,
        public TravelToCity $travelToCity,
        public FeedBrainScorcher $feedBrainScorcher,
        public RecordQuestActionProgress $recordQuestActionProgress,
    ) {}

    /**
     * @return array{type:string,status:string}
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $work = UserWork::query()
                ->with(['cityAction.skill', 'originCity', 'destinationCity', 'offeredItem'])
                ->whereBelongsTo($user)
                ->lockForUpdate()
                ->first();

            if ($work === null) {
                throw new AuthorizationException('There is no active work to complete.');
            }

            if ($work->available_at->isFuture()) {
                throw new AuthorizationException('That work is still in progress. Wait for the timer to finish or cancel it.');
            }

            $result = match (true) {
                $work->isCityAction() => $this->completeCityAction($user, $work),
                $work->isBrainScorcher() => $this->completeBrainScorcher($user, $work),
                default => $this->completeTravel($user, $work),
            };

            $work->delete();

            return $result;
        });
    }

    /**
     * @return array{type:string,status:string}
     */
    private function completeCityAction(User $user, UserWork $work): array
    {
        $action = $work->cityAction;

        if ($action === null) {
            throw new AuthorizationException('That action is no longer available.');
        }

        $missingItems = $this->performCityAction->missingRequiredItems($user, $action);

        if ($missingItems !== []) {
            return [
                'type' => 'city_action',
                'status' => 'Action failed: you no longer have '.$this->performCityAction->describeRequiredItems($missingItems).'. No rewards were granted.',
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

        return [
            'type' => 'city_action',
            'status' => $summary,
        ];
    }

    /**
     * @return array{type:string,status:string}
     */
    private function completeBrainScorcher(User $user, UserWork $work): array
    {
        $item = $work->offeredItem;
        $quantity = (int) $work->offered_quantity;

        if ($item === null) {
            throw new AuthorizationException('That offering is no longer available.');
        }

        $offering = $this->feedBrainScorcher->describeOffering($item, $quantity);

        if ($this->feedBrainScorcher->ownedQuantity($user, $item) < $quantity) {
            return [
                'type' => 'brain_scorcher',
                'status' => "The Brain Scorcher found nothing to burn: you no longer have {$offering}. Nothing was lost.",
            ];
        }

        $result = ($this->feedBrainScorcher)($user, $item, $quantity);
        $reward = $result['reward'];

        $status = match ($result['outcome']) {
            'jackpot' => "Jackpot! The Brain Scorcher burned {$offering} and spat out 1x {$reward?->name} ({$reward?->rarity->label()}).",
            'upgrade' => "The Brain Scorcher burned {$offering} and returned something better: 1x {$reward?->name} ({$reward?->rarity->label()}).",
            'same' => "The Brain Scorcher burned {$offering} and returned 1x {$reward?->name}.",
            'downgrade' => "The Brain Scorcher burned {$offering} and returned something worse: 1x {$reward?->name} ({$reward?->rarity->label()}).",
            default => "The Brain Scorcher burned {$offering} and gave nothing back.",
        };

        return [
            'type' => 'brain_scorcher',
            'status' => $status,
        ];
    }

    /**
     * @return array{type:string,status:string}
     */
    private function completeTravel(User $user, UserWork $work): array
    {
        $destination = $work->destinationCity;

        if ($destination === null) {
            throw new AuthorizationException('That route is no longer available.');
        }

        ($this->travelToCity)($user, $destination);

        return [
            'type' => 'travel',
            'status' => 'You arrived in '.$destination->city.' and refreshed your options.',
        ];
    }
}
