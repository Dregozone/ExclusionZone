<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\PremiumCosmetic;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\User;
use App\Models\UserQuest;
use App\Models\UserWork;
use App\Weather;
use Illuminate\Support\Collection;

class BuildCityMenuData
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $user): array
    {
        $user->load([
            'role.tasks',
            'location.city.country',
            'skills.skill',
            'inventoryItems.item',
            'activeWork.cityAction.skill',
            'activeWork.originCity',
            'activeWork.destinationCity',
            'activeWork.skill',
            'activeWork.offeredItem',
            'cosmeticLoadout.outfitSkin',
            'cosmeticLoadout.uiTheme',
            'cosmeticLoadout.profileFlair',
            'userQuests.quest.steps.city',
            'userQuests.quest.steps.requiredItem',
            'userQuests.quest.steps.requiredCityAction',
            'userQuests.quest.rewardSkill',
            'userQuests.quest.rewardItem',
        ]);

        $location = $user->location;
        $city = $location?->city?->loadMissing('country');
        $neighbors = $city?->neighbors()->with('country')->orderBy('city')->get() ?? collect();
        $activeWork = $user->activeWork;
        $cityActionRestriction = $activeWork !== null
            ? 'Finish or cancel your current work before starting another action or route.'
            : $user->denialReasonForTask('city_action_perform');
        $canPerformCityActions = $cityActionRestriction === null;
        $cityActions = $city === null
            ? collect()
            : CityAction::query()
                ->with('skill')
                ->whereBelongsTo($city)
                ->orderBy('label')
                ->get();

        return [
            'player' => $user,
            'location' => $location,
            'city' => $city,
            'country' => $city?->country,
            'neighbors' => $neighbors,
            'can_perform_city_actions' => $canPerformCityActions,
            'city_action_restriction' => $cityActionRestriction,
            'active_work' => $activeWork === null ? null : $this->activeWorkData($user, $activeWork),
            'actions' => $cityActions->map(function (CityAction $action) use ($user, $canPerformCityActions): array {
                $requiredItems = (new PerformCityAction)->requiredItemsFor($user, $action);
                $hasRequiredItems = collect($requiredItems)->every(
                    fn (array $requiredItem): bool => $requiredItem['owned'] >= $requiredItem['quantity'],
                );

                return [
                    'action' => $action,
                    'required_level' => $action->min_level,
                    'user_level' => $user->skillFor($action->skill_key)?->level ?? 1,
                    'required_items' => $requiredItems,
                    'has_required_items' => $hasRequiredItems,
                    'available' => $canPerformCityActions
                        && $hasRequiredItems
                        && ($user->skillFor($action->skill_key)?->level ?? 1) >= $action->min_level,
                ];
            }),
            'skills' => $user->skills->sortBy(fn ($skill) => $skill->skill?->display_name)->values(),
            'inventory' => $user->inventoryItems->sortBy(fn ($item) => $item->item?->name)->values(),
            'brain_scorcher' => $this->brainScorcherDataFor($user, $city, $canPerformCityActions),
            'quest_step_actions' => $this->questStepActionsFor($user, $city),
            'jobs' => $this->jobsDataFor($user),
            'cosmetics' => PremiumCosmetic::query()
                ->orderBy('cosmetic_type')
                ->orderBy('name')
                ->get()
                ->mapToGroups(fn (PremiumCosmetic $cosmetic): array => [$cosmetic->cosmetic_type => $cosmetic]),
            'loadout' => $user->cosmeticLoadout,
            'map_data' => $this->buildMapData(),
            'current_city_id' => $city?->id,
            'weather' => $city?->weather,
            'local_event' => $this->localEventFor($city?->weather, $city?->trouble_chance_pct),
            'hooks' => collect([
                ['key' => 'chat_send', 'route' => 'chat', 'label' => 'Open radio chat', 'description' => 'Check survivor chatter and faction rumors.'],
                ['key' => 'trade_create', 'route' => 'trade', 'label' => 'Review trade board', 'description' => 'Price out scavenged loot and swap supplies.'],
                ['key' => 'combat_initiate', 'route' => 'combat', 'label' => 'Scout combat contracts', 'description' => 'Preview PvE and PvP danger hooks for the next pass.'],
            ])->map(fn (array $hook): array => [
                ...$hook,
                'available' => $user->canPerformTask($hook['key']),
            ]),
        ];
    }

    /**
     * @return array{nodes: array<int,array{id:int,label:string,country:string,biome:string}>, edges: array<int,array{from:int,to:int}>}
     */
    private function buildMapData(): array
    {
        $nodes = [];
        $edges = [];

        $cities = City::query()
            ->with(['neighbors:id', 'country:id,country,continent'])
            ->orderBy('city')
            ->get(['id', 'city', 'country_id', 'biome', 'lat', 'lng']);

        foreach ($cities as $city) {
            $nodes[] = [
                'id' => $city->id,
                'label' => $city->city,
                'country' => $city->country?->country ?? '',
                'continent' => $city->country?->continent ?? '',
                'biome' => $city->biome,
                'lat' => $city->lat,
                'lng' => $city->lng,
            ];

            foreach ($city->neighbors as $neighbor) {
                $edges[] = ['from' => $city->id, 'to' => $neighbor->id];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Offerings the player can feed the Brain Scorcher, with the odds for every quantity
     * that still changes them, so the menu can show the odds as the player picks.
     *
     * @return array{available: bool, duration_seconds: int, offerings: array<int, array{item_id: int, name: string, rarity: string, owned: int, odds_by_quantity: array<int, array{jackpot: int, upgrade: int, same: int, downgrade: int, nothing: int}>}>}|null
     */
    private function brainScorcherDataFor(User $user, ?City $city, bool $canPerformCityActions): ?array
    {
        $feedBrainScorcher = new FeedBrainScorcher;

        if (! $feedBrainScorcher->isAvailableIn($city?->city)) {
            return null;
        }

        return [
            'available' => $canPerformCityActions,
            'duration_seconds' => (new CalculateWorkDuration)->forBrainScorcher($user),
            'offerings' => $user->inventoryItems
                ->filter(fn ($inventoryItem): bool => $inventoryItem->item !== null && $inventoryItem->quantity > 0)
                ->sortBy(fn ($inventoryItem) => $inventoryItem->item->name)
                ->map(fn ($inventoryItem): array => [
                    'item_id' => $inventoryItem->item->id,
                    'name' => $inventoryItem->item->name,
                    'rarity' => $inventoryItem->item->rarity->label(),
                    'owned' => $inventoryItem->quantity,
                    'odds_by_quantity' => collect(range(1, min($inventoryItem->quantity, FeedBrainScorcher::MAX_EFFECTIVE_QUANTITY)))
                        ->mapWithKeys(fn (int $quantity): array => [$quantity => $feedBrainScorcher->oddsFor($inventoryItem->item->rarity, $quantity)])
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function localEventFor(?Weather $weather, ?int $troubleChance): string
    {
        if ($weather === null || $troubleChance === null) {
            return 'No scout report available.';
        }

        return match (true) {
            $weather === Weather::Storm => 'A storm is tearing through the district; loose loot will not survive the trip back.',
            $troubleChance >= 70 => 'Hostile movement reported in nearby districts.',
            $weather === Weather::Rain => 'Heavy rain is soaking the route and slowing travel.',
            $troubleChance >= 45 => 'Tension is rising; keep your pack tight and your exits clear.',
            default => 'Scouts report a steady window for salvage and movement.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function activeWorkData(User $user, UserWork $activeWork): array
    {
        $skillProgress = $activeWork->skill_key === null ? null : $user->skillFor($activeWork->skill_key);
        $currentLevel = $skillProgress?->level ?? 1;
        $nextLevelXp = $currentLevel * 100;
        $currentXp = $skillProgress?->xp ?? 0;
        $title = match (true) {
            $activeWork->isCityAction() => 'Working on '.($activeWork->cityAction?->label ?? 'city action'),
            $activeWork->isBrainScorcher() => 'Feeding the Brain Scorcher',
            default => 'Traveling from '.($activeWork->originCity?->city ?? 'your current city').' to '.($activeWork->destinationCity?->city ?? 'your destination'),
        };

        return [
            'type' => $activeWork->work_type,
            'title' => $title,
            'description' => match (true) {
                $activeWork->isCityAction() => $activeWork->cityAction?->description ?? 'Stay focused until the work window closes.',
                $activeWork->isBrainScorcher() => 'The machine is chewing on your '.$activeWork->offered_quantity.'x '.($activeWork->offeredItem?->name ?? 'offering').'. Keep it in your pack until the timer ends, or cancel to take it back.',
                default => 'Movement is locked in until you arrive or cancel the route.',
            },
            'duration_seconds' => $activeWork->duration_seconds,
            'weather' => $activeWork->weather,
            'available_at_iso' => $activeWork->available_at->toIso8601String(),
            'available_at_human' => $activeWork->available_at->diffForHumans(),
            'remaining_seconds' => max(0, now()->diffInSeconds($activeWork->available_at, false)),
            'skill_name' => $activeWork->skill?->display_name,
            'skill_level' => $skillProgress?->level,
            'skill_xp' => $currentXp,
            'skill_next_level_xp' => $activeWork->skill_key === null ? null : $nextLevelXp,
            'skill_progress_percent' => $activeWork->skill_key === null
                ? null
                : (int) min(100, round(($currentXp / max(1, $nextLevelXp)) * 100)),
            'skill_xp_remaining' => $activeWork->skill_key === null ? null : max(0, $nextLevelXp - $currentXp),
            'from_city' => $activeWork->originCity?->city,
            'to_city' => $activeWork->destinationCity?->city,
        ];
    }

    /**
     * @return Collection<int, array{step: QuestStep, quest_name: string, requirements: array<int, array{item: ?Item, quantity: int}>}>
     */
    private function questStepActionsFor(User $user, ?City $city): Collection
    {
        if ($city === null) {
            return collect();
        }

        return $user->userQuests
            ->where('status', 'active')
            ->flatMap(function (UserQuest $userQuest) use ($user, $city): array {
                $step = $userQuest->quest->steps->get($userQuest->current_step_index);

                if ($step === null || $step->city_id !== $city->id) {
                    return [];
                }

                $actionProgress = $this->actionProgressFor($userQuest, $step);

                if ($actionProgress !== null && $actionProgress['progress'] < $actionProgress['count']) {
                    return [];
                }

                $requirements = $this->requirementsFor($userQuest, $step);

                $hasAllItems = collect($requirements)->every(
                    fn (array $requirement): bool => $user->inventoryItems->contains(
                        fn ($i) => $i->item_id === $requirement['item']?->id
                            && $i->quantity >= $requirement['quantity'],
                    ),
                );

                if (! $hasAllItems) {
                    return [];
                }

                return [[
                    'step' => $step,
                    'quest_name' => $userQuest->quest->name,
                    'requirements' => $requirements,
                ]];
            })
            ->values();
    }

    /**
     * @return array<int, array{item: ?Item, quantity: int}>
     */
    private function requirementsFor(UserQuest $userQuest, QuestStep $step): array
    {
        $requirements = (new InteractWithQuestStep)->resolveRequirements($userQuest, $step, $userQuest->current_step_index);

        $items = Item::query()
            ->whereIn('id', array_column($requirements, 'required_item_id'))
            ->get()
            ->keyBy('id');

        return array_map(fn (array $requirement): array => [
            'item' => $items->get($requirement['required_item_id']),
            'quantity' => $requirement['required_item_quantity'],
        ], $requirements);
    }

    /**
     * @return array{action: ?CityAction, progress: int, count: int}|null
     */
    private function actionProgressFor(UserQuest $userQuest, QuestStep $step): ?array
    {
        if ($step->required_city_action_id === null) {
            return null;
        }

        return [
            'action' => $step->requiredCityAction,
            'progress' => $userQuest->step_action_progress,
            'count' => (int) $step->required_action_count,
        ];
    }

    /**
     * @return array{available: array<int, array{quest: Quest, type: string, completion_count: ?int, userQuest: ?UserQuest}>, active: array<int, array{userQuest: UserQuest, quest: Quest, current_step: ?QuestStep, requirements: array<int, array{item: ?Item, quantity: int}>, action_progress: ?array{action: ?CityAction, progress: int, count: int}}>, completed: array<int, UserQuest>}
     */
    private function jobsDataFor(User $user): array
    {
        $activeOrCompletedQuestIds = $user->userQuests
            ->whereIn('status', ['active', 'completed'])
            ->pluck('quest_id')
            ->all();

        $completedStoryQuestIds = $user->userQuests
            ->where('status', 'completed')
            ->filter(fn (UserQuest $uq) => $uq->quest?->quest_type === 'story')
            ->pluck('quest_id')
            ->all();

        // Story quests: only show the next unlocked one (prerequisite completed or none)
        $availableStoryQuests = Quest::query()
            ->where('is_active', true)
            ->where('quest_type', 'story')
            ->whereNotIn('id', $activeOrCompletedQuestIds)
            ->where(function ($q) use ($completedStoryQuestIds): void {
                $q->whereNull('prerequisite_quest_id')
                    ->orWhereIn('prerequisite_quest_id', $completedStoryQuestIds);
            })
            ->orderBy('sequence_order')
            ->get()
            ->map(fn (Quest $quest): array => [
                'quest' => $quest,
                'type' => 'story',
                'completion_count' => null,
                'userQuest' => null,
            ]);

        // Regular non-repeatable jobs not yet started
        $availableRegularJobs = Quest::query()
            ->where('is_active', true)
            ->where('quest_type', 'job')
            ->where('is_repeatable', false)
            ->whereNotIn('id', $activeOrCompletedQuestIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Quest $quest): array => [
                'quest' => $quest,
                'type' => 'job',
                'completion_count' => null,
                'userQuest' => null,
            ]);

        // Repeatable jobs ready to run again
        $availableRepeatableJobs = $user->userQuests
            ->where('status', 'repeatable')
            ->map(fn (UserQuest $uq): array => [
                'quest' => $uq->quest,
                'type' => 'repeatable_job',
                'completion_count' => $uq->completion_count,
                'userQuest' => $uq,
            ])
            ->values();

        $available = $availableStoryQuests
            ->concat($availableRegularJobs)
            ->concat($availableRepeatableJobs)
            ->values()
            ->all();

        $active = $user->userQuests
            ->where('status', 'active')
            ->map(function (UserQuest $uq): array {
                $currentStep = $uq->quest->steps->get($uq->current_step_index);

                return [
                    'userQuest' => $uq,
                    'quest' => $uq->quest,
                    'current_step' => $currentStep,
                    'requirements' => $currentStep === null ? [] : $this->requirementsFor($uq, $currentStep),
                    'action_progress' => $currentStep === null ? null : $this->actionProgressFor($uq, $currentStep),
                ];
            })
            ->values()
            ->all();

        $completed = $user->userQuests
            ->where('status', 'completed')
            ->sortByDesc('completed_at')
            ->values()
            ->all();

        return compact('available', 'active', 'completed');
    }
}
