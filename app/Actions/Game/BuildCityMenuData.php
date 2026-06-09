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
            'cosmeticLoadout.outfitSkin',
            'cosmeticLoadout.uiTheme',
            'cosmeticLoadout.profileFlair',
            'userQuests.quest.steps.city',
            'userQuests.quest.steps.requiredItem',
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
            'actions' => $cityActions->map(fn (CityAction $action): array => [
                'action' => $action,
                'required_level' => $action->min_level,
                'user_level' => $user->skillFor($action->skill_key)?->level ?? 1,
                'available' => $canPerformCityActions
                    && ($user->skillFor($action->skill_key)?->level ?? 1) >= $action->min_level,
            ]),
            'skills' => $user->skills->sortBy(fn ($skill) => $skill->skill?->display_name)->values(),
            'inventory' => $user->inventoryItems->sortBy(fn ($item) => $item->item?->name)->values(),
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
            'local_event' => $this->localEventFor($city?->rain_chance_pct, $city?->trouble_chance_pct),
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

    private function localEventFor(?int $rainChance, ?int $troubleChance): string
    {
        if ($rainChance === null || $troubleChance === null) {
            return 'No scout report available.';
        }

        return match (true) {
            $troubleChance >= 70 => 'Hostile movement reported in nearby districts.',
            $rainChance >= 60 => 'Heavy rain is soaking the route and slowing travel.',
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
        $title = $activeWork->isCityAction()
            ? 'Working on '.($activeWork->cityAction?->label ?? 'city action')
            : 'Traveling from '.($activeWork->originCity?->city ?? 'your current city').' to '.($activeWork->destinationCity?->city ?? 'your destination');

        return [
            'type' => $activeWork->work_type,
            'title' => $title,
            'description' => $activeWork->isCityAction()
                ? ($activeWork->cityAction?->description ?? 'Stay focused until the work window closes.')
                : 'Movement is locked in until you arrive or cancel the route.',
            'duration_seconds' => $activeWork->duration_seconds,
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
     * @return Collection<int, array{step: QuestStep, quest_name: string, resolved_required_item: ?Item, resolved_required_quantity: int}>
     */
    private function questStepActionsFor(User $user, ?City $city): Collection
    {
        if ($city === null) {
            return collect();
        }

        $resolver = new InteractWithQuestStep;

        return $user->userQuests
            ->where('status', 'active')
            ->flatMap(function (UserQuest $userQuest) use ($user, $city, $resolver): array {
                $stepIndex = $userQuest->current_step_index;
                $step = $userQuest->quest->steps->get($stepIndex);

                if ($step === null || $step->city_id !== $city->id) {
                    return [];
                }

                $requirement = $resolver->resolveRequirement($userQuest, $step, $stepIndex);

                if ($requirement !== null) {
                    $hasItem = $user->inventoryItems->contains(
                        fn ($i) => $i->item_id === $requirement['required_item_id']
                            && $i->quantity >= $requirement['required_item_quantity'],
                    );

                    if (! $hasItem) {
                        return [];
                    }
                }

                $resolvedItem = $requirement !== null
                    ? Item::find($requirement['required_item_id'])
                    : null;

                return [[
                    'step' => $step,
                    'quest_name' => $userQuest->quest->name,
                    'resolved_required_item' => $resolvedItem,
                    'resolved_required_quantity' => $requirement['required_item_quantity'] ?? 1,
                ]];
            })
            ->values();
    }

    /**
     * @return array{available: array<int, array{quest: Quest, type: string, completion_count: ?int, userQuest: ?UserQuest}>, active: array<int, array{userQuest: UserQuest, quest: Quest, current_step: ?QuestStep}>, completed: array<int, UserQuest>}
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
            ->map(fn (UserQuest $uq): array => [
                'userQuest' => $uq,
                'quest' => $uq->quest,
                'current_step' => $uq->quest->steps->get($uq->current_step_index),
            ])
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
