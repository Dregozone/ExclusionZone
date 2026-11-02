<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\User;
use App\Models\UserQuest;

class BuildWorldMapData
{
    /**
     * Everything the world map needs: every city, every route, and where the player stands.
     *
     * @return array{
     *     nodes: array<int, array{id: int, label: string, country: string, continent: string, biome: ?string, loot_tier: ?string, weather: ?string, has_trade_post: bool, lat: ?float, lng: ?float}>,
     *     edges: array<int, array{from: int, to: int, duration_seconds: ?int}>,
     *     player: ?array{current_city_id: ?int, can_travel: bool, travel_restriction: ?string, transit: ?array{from: int, to: int}, objectives: array<int, array{city_id: int, quest: string, action: ?string}>}
     * }
     */
    public function __invoke(?User $user): array
    {
        $nodes = [];
        $edges = [];

        $cities = City::query()
            ->with(['neighbors:id', 'country:id,country,continent'])
            ->orderBy('city')
            ->get(['id', 'city', 'country_id', 'biome', 'baseline_loot_tier', 'weather', 'has_trade_post', 'lat', 'lng']);

        foreach ($cities as $city) {
            $nodes[] = [
                'id' => $city->id,
                'label' => $city->city,
                'country' => $city->country?->country ?? '',
                'continent' => $city->country?->continent ?? '',
                'biome' => $city->biome,
                'loot_tier' => $city->baseline_loot_tier,
                'weather' => $city->weather?->label(),
                'has_trade_post' => $city->has_trade_post,
                'lat' => $city->lat,
                'lng' => $city->lng,
            ];

            foreach ($city->neighbors as $neighbor) {
                $edges[] = [
                    'from' => $city->id,
                    'to' => $neighbor->id,
                    'duration_seconds' => $neighbor->pivot->duration_seconds,
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'player' => $user === null ? null : $this->playerDataFor($user),
        ];
    }

    /**
     * @return array{current_city_id: ?int, can_travel: bool, travel_restriction: ?string, transit: ?array{from: int, to: int}, objectives: array<int, array{city_id: int, quest: string, action: ?string}>}
     */
    private function playerDataFor(User $user): array
    {
        $user->loadMissing(['location', 'activeWork', 'userQuests.quest.steps']);

        $activeWork = $user->activeWork;
        $travelRestriction = $user->cityActionRestriction();

        return [
            'current_city_id' => $user->location?->city_id,
            'can_travel' => $travelRestriction === null,
            'travel_restriction' => $travelRestriction,
            'transit' => $activeWork?->isTravel() && $activeWork->origin_city_id !== null && $activeWork->destination_city_id !== null
                ? ['from' => $activeWork->origin_city_id, 'to' => $activeWork->destination_city_id]
                : null,
            'objectives' => $user->userQuests
                ->where('status', 'active')
                ->map(function (UserQuest $userQuest): ?array {
                    $step = $userQuest->quest?->steps->get($userQuest->current_step_index);

                    if ($step?->city_id === null) {
                        return null;
                    }

                    return [
                        'city_id' => $step->city_id,
                        'quest' => $userQuest->quest->name,
                        'action' => $step->action_label,
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
