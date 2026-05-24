<?php

namespace App\Actions\Game;

use App\Models\CityAction;
use App\Models\PremiumCosmetic;
use App\Models\User;

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
            'cosmeticLoadout.outfitSkin',
            'cosmeticLoadout.uiTheme',
            'cosmeticLoadout.profileFlair',
        ]);

        $location = $user->location;
        $city = $location?->city?->loadMissing('country');
        $neighbors = $city?->neighbors()->with('country')->orderBy('city')->get() ?? collect();
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
            'actions' => $cityActions->map(fn (CityAction $action): array => [
                'action' => $action,
                'required_level' => $action->min_level,
                'user_level' => $user->skillFor($action->skill_key)?->level ?? 1,
                'available' => ($user->skillFor($action->skill_key)?->level ?? 1) >= $action->min_level,
            ]),
            'skills' => $user->skills->sortBy(fn ($skill) => $skill->skill?->display_name)->values(),
            'inventory' => $user->inventoryItems->sortBy(fn ($item) => $item->item?->name)->values(),
            'cosmetics' => PremiumCosmetic::query()->orderBy('cosmetic_type')->orderBy('name')->get()->groupBy('cosmetic_type'),
            'loadout' => $user->cosmeticLoadout,
            'local_event' => $this->localEventFor($city?->rain_chance_pct, $city?->trouble_chance_pct),
            'hooks' => collect([
                ['key' => 'chat_send', 'route' => 'chat', 'label' => 'Open radio chat', 'description' => 'Check survivor chatter and faction rumors.'],
                ['key' => 'trade_create', 'route' => 'trade', 'label' => 'Review trade board', 'description' => 'Price out scavenged loot and swap supplies.'],
                ['key' => 'combat_initiate', 'route' => 'combat', 'label' => 'Scout combat contracts', 'description' => 'Preview PvE and PvP danger hooks for the next pass.'],
            ]),
        ];
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
}
