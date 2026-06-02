<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityAction;
use App\Models\PremiumCosmetic;
use App\Models\User;
use App\Models\UserWork;

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
            ->with(['neighbors:id', 'country:id,country'])
            ->orderBy('city')
            ->get(['id', 'city', 'country_id', 'biome']);

        foreach ($cities as $city) {
            $nodes[] = [
                'id' => $city->id,
                'label' => $city->city,
                'country' => $city->country?->country ?? '',
                'biome' => $city->biome,
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
}
