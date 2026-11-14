<?php

namespace App\Actions\Game;

use App\EquipSlot;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Enemy;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\PremiumCosmetic;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserEquipment;
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
            'inventoryItems.item.bonusSkill',
            'equipment.item.bonusSkill',
            'activeWork.cityAction.skill',
            'activeWork.originCity',
            'activeWork.destinationCity',
            'activeWork.skill',
            'activeWork.offeredItem',
            'activeWork.enemy',
            'cosmeticLoadout.outfitSkin',
            'cosmeticLoadout.uiTheme',
            'cosmeticLoadout.profileFlair',
            'userQuests.quest.steps.city',
            'userQuests.quest.steps.requiredItem',
            'userQuests.quest.steps.requiredCityAction',
            'userQuests.quest.rewardSkill',
            'userQuests.quest.rewardItem',
            'troubleEncounter.troubleType.skill',
            'troubleEncounter.originCity',
        ]);

        $location = $user->location;
        $city = $location?->city?->loadMissing('country');
        $activeWork = $user->activeWork;
        $cityActionRestriction = $user->cityActionRestriction();
        $canPerformCityActions = $cityActionRestriction === null;
        $cityActions = $city === null
            ? collect()
            : CityAction::query()
                ->with('skill')
                ->whereBelongsTo($city)
                ->orderBy('label')
                ->get();
        $fightEnemy = app(FightEnemy::class);
        $enemiesBySkill = $city === null
            ? collect()
            : Enemy::query()->whereBelongsTo($city)->orderBy('name')->get()->groupBy('skill_key')->map(
                fn (Collection $enemies): array => $enemies
                    ->map(fn (Enemy $enemy): array => ['name' => $enemy->name, ...$fightEnemy->odds($user, $enemy)])
                    ->all(),
            );

        return [
            'player' => $user,
            'location' => $location,
            'city' => $city,
            'country' => $city?->country,
            'can_perform_city_actions' => $canPerformCityActions,
            'city_action_restriction' => $cityActionRestriction,
            'active_work' => $activeWork === null ? null : $this->activeWorkData($user, $activeWork),
            'last_work_report' => $user->last_work_report,
            'death_report' => $user->death_report,
            'trouble' => $this->troubleDataFor($user),
            'recovery' => $this->recoveryFor($user),
            'actions' => $cityActions->map(function (CityAction $action) use ($user, $cityActionRestriction, $enemiesBySkill): array {
                $performCityAction = new PerformCityAction;
                $requiredItems = $performCityAction->requiredItemsFor($user, $action);
                $missingItems = array_values(array_filter($requiredItems, fn (array $requiredItem): bool => $requiredItem['owned'] < $requiredItem['quantity']));
                $userLevel = $user->skillFor($action->skill_key)?->level ?? 1;
                $isHealing = $performCityAction->healthRestoredBy($action) > 0;

                [$unavailableLabel, $unavailableReason] = match (true) {
                    $cityActionRestriction !== null => [$user->recoveryRestriction() !== null ? 'Recovering' : 'Unavailable', $cityActionRestriction],
                    $userLevel < $action->min_level => ['Locked', "Needs {$action->skill?->display_name} level {$action->min_level}. You are level {$userLevel}."],
                    $missingItems !== [] => ['Missing items', 'You need '.$performCityAction->describeRequiredItems($missingItems).' more. '.collect($missingItems)->map(fn (array $missing): string => "{$missing['item']->name}: {$missing['owned']} of {$missing['quantity']} carried")->implode(', ').'.'],
                    $isHealing && $user->health >= (int) config('survival.maximum') => ['Not hurt', 'You are at full health, so there is nothing to patch up.'],
                    default => [null, null],
                };

                return [
                    'action' => $action,
                    'required_level' => $action->min_level,
                    'user_level' => $userLevel,
                    'required_items' => $requiredItems,
                    'has_required_items' => $missingItems === [],
                    'enemies' => $enemiesBySkill->get($action->skill_key, []),
                    'gear_speed_percent' => $user->equipmentBonusPercent('work_speed', $action->skill_key),
                    'available' => $unavailableReason === null,
                    'unavailable_label' => $unavailableLabel,
                    'unavailable_reason' => $unavailableReason,
                ];
            }),
            'skills' => $user->skills->sortBy(fn ($skill) => $skill->skill?->display_name)->values(),
            'inventory' => $user->inventoryItems->filter(fn (InventoryItem $inventoryItem): bool => $inventoryItem->item !== null && $inventoryItem->quantity > 0)->sortBy(fn ($item) => $item->item?->name)->values(),
            'gear' => $this->gearFor($user),
            'bazaar' => $this->bazaarFor($user),
            'alerts' => (new TrackPdaAlerts)->counts($user),
            'condition' => $this->conditionFor($user),
            'brain_scorcher' => $this->brainScorcherDataFor($user, $city, $canPerformCityActions),
            'library' => $this->libraryDataFor($user, $city, $canPerformCityActions),
            'quest_step_actions' => $this->questStepActionsFor($user, $city),
            'jobs' => $this->jobsDataFor($user),
            'cosmetics' => PremiumCosmetic::query()
                ->orderBy('cosmetic_type')
                ->orderBy('name')
                ->get()
                ->mapToGroups(fn (PremiumCosmetic $cosmetic): array => [$cosmetic->cosmetic_type => $cosmetic]),
            'loadout' => $user->cosmeticLoadout,
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
     * When a survivor who died can work and travel again, so the city menu can count it down.
     *
     * @return array{until_iso: string, until_human: string, remaining_seconds: int}|null
     */
    private function recoveryFor(User $user): ?array
    {
        if ($user->recoveryRestriction() === null) {
            return null;
        }

        return [
            'until_iso' => $user->recovering_until->toIso8601String(),
            'until_human' => $user->recovering_until->diffForHumans(),
            'remaining_seconds' => (int) max(0, now()->diffInSeconds($user->recovering_until, false)),
        ];
    }

    /**
     * What the survivor wears in each slot, the wearable gear in their pack, and what it all adds up to.
     *
     * @return array{slots: list<array{slot: EquipSlot, item: ?Item}>, wearables: list<array{item: Item, owned: int, slots: list<EquipSlot>}>, effects: list<string>}
     */
    private function gearFor(User $user): array
    {
        $worn = $user->equipment->keyBy(fn (UserEquipment $equipment): string => $equipment->slot->value);

        return [
            'slots' => array_map(fn (EquipSlot $slot): array => [
                'slot' => $slot,
                'item' => $worn->get($slot->value)?->item,
            ], EquipSlot::cases()),
            'wearables' => $user->inventoryItems
                ->filter(fn (InventoryItem $inventoryItem): bool => $inventoryItem->item?->isWearable() === true && $inventoryItem->quantity > 0)
                ->sortBy(fn (InventoryItem $inventoryItem): string => $inventoryItem->item->name)
                ->map(fn (InventoryItem $inventoryItem): array => [
                    'item' => $inventoryItem->item,
                    'owned' => $inventoryItem->quantity,
                    'slots' => EquipSlot::fitting($inventoryItem->item),
                ])
                ->values()
                ->all(),
            'effects' => array_values(array_filter([
                ...$user->equipment
                    ->pluck('item')
                    ->filter(fn (?Item $item): bool => $item !== null && $item->work_speed_bonus_percent > 0 && $item->bonus_skill_key !== null)
                    ->groupBy('bonus_skill_key')
                    ->map(fn (Collection $items, string $skillKey): string => ($items->first()->bonusSkill?->display_name ?? str($skillKey)->headline()).' work '.$user->equipmentBonusPercent('work_speed', $skillKey).'% faster')
                    ->values()
                    ->all(),
                $user->heldWeapon() === null ? null : 'Fighting with your '.$user->heldWeapon()->name.' (attack +'.$user->heldWeapon()->attack_bonus.')',
                $user->equipmentBonusPercent('hunger_reduction') > 0 ? 'Hunger -'.$user->equipmentBonusPercent('hunger_reduction').'% while working' : null,
                $user->equipmentBonusPercent('fatigue_reduction') > 0 ? 'Fatigue -'.$user->equipmentBonusPercent('fatigue_reduction').'% while working' : null,
                $user->equipmentBonusPercent('healing_bonus') > 0 ? 'Healing +'.$user->equipmentBonusPercent('healing_bonus').'%' : null,
                $user->equipmentBonusPercent('radiation_protection') > 0 ? 'Radiation -'.$user->equipmentBonusPercent('radiation_protection').'%' : null,
            ])),
        ];
    }

    /**
     * The trade post bazaar: whether the survivor can use it, what it sells, and what it pays for their goods.
     *
     * @return array{restriction: ?string, scrip: int, barter_sell_bonus_percent: int, barter_buy_discount_percent: int, stock: list<array{item: Item, price: int}>, sellable: list<array{item: Item, owned: int, unit_price: int}>}
     */
    private function bazaarFor(User $user): array
    {
        $tradeAtBazaar = new TradeAtBazaar;
        $restriction = $tradeAtBazaar->restriction($user);

        return [
            'restriction' => $restriction,
            'scrip' => (int) $user->scrip,
            'barter_sell_bonus_percent' => $tradeAtBazaar->barterSellBonusPercent($user),
            'barter_buy_discount_percent' => $tradeAtBazaar->barterBuyDiscountPercent($user),
            'stock' => $restriction !== null ? [] : $tradeAtBazaar->stock()
                ->map(fn (Item $item): array => ['item' => $item, 'price' => $tradeAtBazaar->buyPrice($user, $item)])
                ->all(),
            'sellable' => $restriction !== null ? [] : $user->inventoryItems
                ->filter(fn (InventoryItem $inventoryItem): bool => $inventoryItem->item?->isSellable() === true && $inventoryItem->quantity > 0)
                ->sortBy(fn (InventoryItem $inventoryItem): string => $inventoryItem->item->name)
                ->map(fn (InventoryItem $inventoryItem): array => [
                    'item' => $inventoryItem->item,
                    'owned' => $inventoryItem->quantity,
                    'unit_price' => $tradeAtBazaar->sellPrice($user, $inventoryItem->item, 1),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * The trouble holding the survivor at the city's edge, with what each way out of it would take.
     *
     * @return array{name: string, description: string, skill_name: ?string, fighter_level: int, damage_min: int, damage_max: int, win_chance_percent: int, can_attack: bool, demand_value: int, barter_goods: list<array{item_id: int, name: string, rarity: string, owned: int, value: int}>, can_buy_in: bool, origin_city: ?string, can_run: bool, route_stops: list<string>}|null
     */
    private function troubleDataFor(User $user): ?array
    {
        $encounter = $user->troubleEncounter;

        if ($encounter === null) {
            return null;
        }

        $dealWithTrouble = app(DealWithTrouble::class);
        $troubleType = $encounter->troubleType;
        $barterGoods = $dealWithTrouble->barterGoodsFor($user);
        $canActHere = $user->canPerformTask('city_action_perform');
        $origin = $encounter->originCity;
        $queuedCityIds = $encounter->queuedCityIds();
        $queuedCityNames = City::query()->withTrashed()->whereKey($queuedCityIds)->pluck('city', 'id');

        return [
            'name' => $troubleType->name,
            'description' => $troubleType->description,
            'skill_name' => $troubleType->skill?->display_name,
            'fighter_level' => $troubleType->fighter_level,
            'damage_min' => $troubleType->damage_min,
            'damage_max' => $troubleType->damage_max,
            'win_chance_percent' => $dealWithTrouble->winChancePercent($user, $troubleType),
            'can_attack' => $user->canPerformTask('combat_initiate'),
            'demand_value' => $troubleType->demand_value,
            'barter_goods' => $barterGoods,
            'can_buy_in' => $canActHere && collect($barterGoods)->sum(fn (array $goods): int => $goods['value'] * $goods['owned']) >= $troubleType->demand_value,
            'origin_city' => $origin?->city,
            'can_run' => $canActHere && $origin !== null && ! $origin->trashed(),
            'route_stops' => array_values(array_map(fn (int $cityId): string => $queuedCityNames[$cityId] ?? 'Unknown city', $queuedCityIds)),
        ];
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

    /**
     * The skills the library teaches and the goods the player can pay with, with the XP each one buys.
     *
     * @return array{available: bool, seconds_per_xp: int, max_xp: int, skills: list<array{skill_id: int, name: string, level: int}>, payments: list<array{item_id: int, name: string, rarity: string, owned: int, xp_per_item: int, max_quantity: int}>}|null
     */
    private function libraryDataFor(User $user, ?City $city, bool $canPerformCityActions): ?array
    {
        $studyAtLibrary = new StudyAtLibrary;

        if (! $studyAtLibrary->isAvailableIn($city?->city)) {
            return null;
        }

        return [
            'available' => $canPerformCityActions,
            'seconds_per_xp' => StudyAtLibrary::SECONDS_PER_XP,
            'max_xp' => StudyAtLibrary::MAX_XP_PER_LESSON,
            'skills' => Skill::query()
                ->orderBy('display_name')
                ->get()
                ->map(fn (Skill $skill): array => [
                    'skill_id' => $skill->id,
                    'name' => $skill->display_name,
                    'level' => $user->skillFor($skill->key)?->level ?? 1,
                ])
                ->all(),
            'payments' => $user->inventoryItems
                ->filter(fn ($inventoryItem): bool => $inventoryItem->item !== null && ! $inventoryItem->item->is_quest_item && $inventoryItem->quantity > 0)
                ->sortBy(fn ($inventoryItem) => $inventoryItem->item->name)
                ->map(fn ($inventoryItem): array => [
                    'item_id' => $inventoryItem->item->id,
                    'name' => $inventoryItem->item->name,
                    'rarity' => $inventoryItem->item->rarity->label(),
                    'owned' => $inventoryItem->quantity,
                    'xp_per_item' => $studyAtLibrary->experienceFor($inventoryItem->item, 1),
                    'max_quantity' => min($inventoryItem->quantity, $studyAtLibrary->maxQuantityPerLesson($inventoryItem->item)),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * The survivor's health, hunger and fatigue, how much they slow work timers, and the food and medical items they carry.
     *
     * @return array{health: array{value: int, maximum: int, label: string, timer_increase_percent: int}, hunger: array{value: float, maximum: int, label: string, timer_increase_percent: int}, fatigue: array{value: float, maximum: int, label: string, timer_increase_percent: int}, timer_increase_percent: int, foods: list<array{item_id: int, name: string, image_url: string, owned: int, health_restored: int, hunger_restored: int, fatigue_restored: int}>, medical_items: list<array{item_id: int, name: string, image_url: string, owned: int, health_restored: int}>}
     */
    private function conditionFor(User $user): array
    {
        $trackSurvivalNeeds = new TrackSurvivalNeeds;

        return [
            'health' => $trackSurvivalNeeds->condition($user, 'health'),
            'hunger' => $trackSurvivalNeeds->condition($user, 'hunger'),
            'fatigue' => $trackSurvivalNeeds->condition($user, 'fatigue'),
            'timer_increase_percent' => $trackSurvivalNeeds->timerIncreasePercent($user),
            'foods' => $user->inventoryItems
                ->filter(fn ($inventoryItem): bool => $inventoryItem->item?->isFood() === true && $inventoryItem->quantity > 0)
                ->sortByDesc(fn ($inventoryItem): int => $inventoryItem->item->health_restored + $inventoryItem->item->hunger_restored + $inventoryItem->item->fatigue_restored)
                ->map(fn ($inventoryItem): array => [
                    'item_id' => $inventoryItem->item->id,
                    'name' => $inventoryItem->item->name,
                    'image_url' => $inventoryItem->item->imageUrl(),
                    'owned' => $inventoryItem->quantity,
                    'health_restored' => $inventoryItem->item->health_restored,
                    'hunger_restored' => $inventoryItem->item->hunger_restored,
                    'fatigue_restored' => $inventoryItem->item->fatigue_restored,
                ])
                ->values()
                ->all(),
            'medical_items' => $user->inventoryItems
                ->filter(fn ($inventoryItem): bool => $inventoryItem->item?->isMedical() === true && $inventoryItem->quantity > 0)
                ->sortByDesc(fn ($inventoryItem): int => $inventoryItem->item->health_restored)
                ->map(fn ($inventoryItem): array => [
                    'item_id' => $inventoryItem->item->id,
                    'name' => $inventoryItem->item->name,
                    'image_url' => $inventoryItem->item->imageUrl(),
                    'owned' => $inventoryItem->quantity,
                    'health_restored' => $inventoryItem->item->health_restored,
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
            $activeWork->isLesson() => 'Studying '.($activeWork->skill?->display_name ?? 'a skill').' at the library',
            $activeWork->is_retreat => 'Running from '.($activeWork->originCity?->city ?? 'trouble').' back to '.($activeWork->destinationCity?->city ?? 'safety'),
            default => 'Traveling from '.($activeWork->originCity?->city ?? 'your current city').' to '.($activeWork->destinationCity?->city ?? 'your destination'),
        };

        return [
            'type' => $activeWork->work_type,
            'is_retreat' => $activeWork->is_retreat,
            'title' => $title,
            'image_url' => asset($activeWork->sceneImagePath()),
            'description' => match (true) {
                $activeWork->isCityAction() => $activeWork->cityAction?->description ?? 'This work repeats until you stop it.',
                $activeWork->is_retreat => 'You turned tail on the trouble. There is no turning back until you reach safety.',
                $activeWork->isLesson() => 'The librarian is teaching you. You pay '.$activeWork->offered_quantity.'x '.($activeWork->offeredItem?->name ?? 'goods').' when the lesson ends, so keep it in your pack. Cancel to keep your items and learn nothing.',
                $activeWork->isBrainScorcher() => 'The machine is chewing on your '.$activeWork->offered_quantity.'x '.($activeWork->offeredItem?->name ?? 'offering').'. Keep it in your pack until the timer ends, or cancel to take it back.',
                $activeWork->queuedCityIds() !== [] => 'Movement is locked in until you arrive or cancel the route. Each later leg sets off on its own as soon as you reach a stop.',
                default => 'Movement is locked in until you arrive or cancel the route.',
            },
            'duration_seconds' => $activeWork->duration_seconds,
            'cycles_completed' => $activeWork->cycles_completed,
            'xp_earned' => $activeWork->xp_earned,
            'last_cycle_summary' => $activeWork->last_cycle_summary,
            'loop_ends_at_human' => $activeWork->isCityAction()
                ? $activeWork->created_at->addHours(AdvanceWorkLoop::MAX_LOOP_HOURS)->diffForHumans()
                : null,
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
            'route_stops' => $this->queuedStopNamesFor($activeWork),
            'enemy' => $activeWork->enemy === null ? null : [
                'name' => $activeWork->enemy->name,
                'description' => $activeWork->enemy->description,
                'health' => $activeWork->enemy_health ?? $activeWork->enemy->health,
                'maximum' => $activeWork->enemy->health,
                ...app(FightEnemy::class)->odds($user, $activeWork->enemy),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function queuedStopNamesFor(UserWork $activeWork): array
    {
        $queuedCityIds = $activeWork->queuedCityIds();

        if ($queuedCityIds === []) {
            return [];
        }

        $names = City::query()->withTrashed()->whereKey($queuedCityIds)->pluck('city', 'id');

        return array_values(array_map(fn (int $cityId): string => $names[$cityId] ?? 'Unknown city', $queuedCityIds));
    }

    /**
     * @return Collection<int, array{step: QuestStep, quest_name: string, requirements: array<int, array{item: ?Item, quantity: int}>}>
     */
    private function questStepActionsFor(User $user, ?City $city): Collection
    {
        if ($city === null || $user->isHeldByTrouble()) {
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

        // Jobs the player has never taken, repeatable or not. Repeatable jobs they
        // have taken before come back below with their run count.
        $availableNewJobs = Quest::query()
            ->where('is_active', true)
            ->where('quest_type', 'job')
            ->whereNotIn('id', $user->userQuests->pluck('quest_id')->all())
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
            ->concat($availableNewJobs)
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
