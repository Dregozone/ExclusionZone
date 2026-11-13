<?php

namespace App\Actions\Game;

use App\Models\City;
use App\Models\CityAction;
use App\Models\Enemy;
use App\Models\Item;
use App\Models\Quest;
use App\Models\Skill;
use App\Models\TroubleType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Reads the live game data into the shapes the public manual shows, so every table in the
 * manual stays in step with what admins edit: which actions exist where, what they need,
 * what they give, and where every item comes from or goes.
 */
class BuildManualCatalogue
{
    /**
     * Experience needed to climb from one level to the next is the current level times this.
     */
    public const XP_PER_LEVEL_STEP = 100;

    /**
     * Each skill level above 1 shortens an action's timer by this percentage.
     */
    public const TIMER_REDUCTION_PERCENT_PER_LEVEL = 1;

    /**
     * No action timer drops below this, however skilled the survivor.
     */
    public const MINIMUM_ACTION_SECONDS = 10;

    /**
     * @var Collection<string, Item>|null
     */
    private ?Collection $items = null;

    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    private ?Collection $actionRows = null;

    /**
     * @var Collection<int, Enemy>|null
     */
    private ?Collection $enemies = null;

    public static function experienceToNextLevel(int $level): int
    {
        return max(1, $level) * self::XP_PER_LEVEL_STEP;
    }

    /**
     * Total experience earned from a fresh level 1 to reach the given level.
     */
    public static function totalExperienceForLevel(int $level): int
    {
        $level = max(1, $level);

        return intdiv(self::XP_PER_LEVEL_STEP * $level * ($level - 1), 2);
    }

    /**
     * How long one cycle of an action takes at a skill level, before weather and condition.
     */
    public static function cycleSecondsAtLevel(int $baseSeconds, int $level): int
    {
        $reductionPercent = max(0, $level - 1) * self::TIMER_REDUCTION_PERCENT_PER_LEVEL;
        $scaled = (int) ceil($baseSeconds * (100 - min(100, $reductionPercent)) / 100);

        return max(self::MINIMUM_ACTION_SECONDS, $scaled);
    }

    public static function citySlug(City $city): string
    {
        return Str::slug($city->city);
    }

    /**
     * @return Collection<string, Item>
     */
    public function items(): Collection
    {
        return $this->items ??= Item::query()->orderBy('name')->get()->keyBy('key');
    }

    /**
     * @return Collection<int, Enemy>
     */
    public function enemies(): Collection
    {
        return $this->enemies ??= Enemy::query()->with('city', 'skill')->orderBy('health')->get();
    }

    /**
     * Every city action, ready to print as a manual row.
     *
     * @return Collection<int, array{action: CityAction, inputs: list<array{item: ?Item, key: string, quantity: int}>, output: ?array{item: ?Item, key: string, quantity: int}, xp: int, health: int, enemies: Collection<int, Enemy>}>
     */
    public function actionRows(): Collection
    {
        if ($this->actionRows !== null) {
            return $this->actionRows;
        }

        $items = $this->items();
        $enemies = $this->enemies();

        return $this->actionRows = CityAction::query()
            ->with(['city.country', 'skill'])
            ->whereHas('city')
            ->orderBy('min_level')
            ->orderBy('label')
            ->get()
            ->map(function (CityAction $action) use ($items, $enemies): array {
                $reward = $action->reward_profile ?? [];
                $outputKey = data_get($reward, 'item_key');

                return [
                    'action' => $action,
                    'inputs' => collect($action->required_items ?? [])
                        ->map(fn (array $input): array => [
                            'item' => $items->get($input['item_key']),
                            'key' => (string) $input['item_key'],
                            'quantity' => (int) $input['quantity'],
                        ])
                        ->values()
                        ->all(),
                    'output' => $outputKey === null ? null : [
                        'item' => $items->get($outputKey),
                        'key' => (string) $outputKey,
                        'quantity' => (int) data_get($reward, 'quantity', 1),
                    ],
                    'xp' => (int) data_get($reward, 'xp', 10),
                    'health' => (int) data_get($reward, 'health', 0),
                    'enemies' => in_array($action->skill_key, Enemy::COMBAT_SKILLS, true)
                        ? $enemies->where('city_id', $action->city_id)->where('skill_key', $action->skill_key)->values()
                        : collect(),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function actionRowsForSkill(Skill $skill): Collection
    {
        return $this->actionRows()
            ->filter(fn (array $row): bool => $row['action']->skill_key === $skill->key)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function actionRowsForCity(City $city): Collection
    {
        return $this->actionRows()
            ->filter(fn (array $row): bool => $row['action']->city_id === $city->id)
            ->values();
    }

    /**
     * Every way an item enters or leaves a survivor's pack.
     *
     * @return array{produced_by: Collection<int, array<string, mixed>>, used_by: Collection<int, array<string, mixed>>, dropped_by: Collection<int, array{enemy: Enemy, chance_percent: int, quantity: int}>, trouble_loot: Collection<int, TroubleType>, quest_rewards: Collection<int, Quest>}
     */
    public function usageFor(Item $item): array
    {
        $rows = $this->actionRows();

        return [
            'produced_by' => $rows->filter(fn (array $row): bool => ($row['output']['key'] ?? null) === $item->key)->values(),
            'used_by' => $rows->filter(fn (array $row): bool => collect($row['inputs'])->contains('key', $item->key))->values(),
            'dropped_by' => $this->enemies()
                ->flatMap(fn (Enemy $enemy): array => collect($enemy->lootTable())
                    ->where('item_key', $item->key)
                    ->map(fn (array $loot): array => [
                        'enemy' => $enemy,
                        'chance_percent' => (int) $loot['chance_percent'],
                        'quantity' => (int) $loot['quantity'],
                    ])
                    ->all())
                ->values(),
            'trouble_loot' => TroubleType::query()->where('loot_item_id', $item->id)->orderBy('fighter_level')->get(),
            'quest_rewards' => Quest::query()->where('is_active', true)->where('reward_item_id', $item->id)->orderBy('sequence_order')->get(),
        ];
    }

    /**
     * Cities with their country, neighbours and how much happens in each.
     *
     * @return Collection<int, City>
     */
    public function cities(): Collection
    {
        return City::query()
            ->with(['country', 'neighbors.country'])
            ->withCount(['actions'])
            ->orderBy('city')
            ->get();
    }

    public function findCityBySlug(string $slug): ?City
    {
        return City::query()
            ->with(['country', 'neighbors.country'])
            ->get()
            ->first(fn (City $city): bool => self::citySlug($city) === $slug);
    }

    /**
     * A flat list of every page and entry in the manual, for the search box.
     *
     * @return list<array{title: string, type: string, url: string, hint: string}>
     */
    public function searchIndex(): array
    {
        $entries = collect(config('manual.guides'))
            ->map(fn (array $guide, string $slug): array => [
                'title' => $guide['title'],
                'type' => 'Guide',
                'url' => route('manual.guide', $slug),
                'hint' => $guide['summary'],
            ])
            ->values();

        $entries->push(['title' => 'FAQ', 'type' => 'Guide', 'url' => route('manual.faq'), 'hint' => 'Quick answers to common questions']);
        $entries->push(['title' => 'Quests & jobs', 'type' => 'Guide', 'url' => route('manual.quests'), 'hint' => 'Story chapters and repeatable contracts']);

        foreach (Skill::query()->orderBy('display_name')->get() as $skill) {
            $entries->push(['title' => $skill->display_name, 'type' => 'Skill', 'url' => route('manual.skill', $skill->key), 'hint' => (string) $skill->description]);
        }

        foreach ($this->cities() as $city) {
            $entries->push(['title' => $city->city, 'type' => 'Location', 'url' => route('manual.location', self::citySlug($city)), 'hint' => trim(($city->country?->country ?? '').' · '.$city->biome, ' ·')]);
        }

        foreach ($this->items() as $item) {
            $entries->push(['title' => $item->name, 'type' => 'Item', 'url' => route('manual.item', $item->key), 'hint' => $item->rarity->label().' · '.Str::limit((string) $item->description, 60)]);
        }

        foreach ($this->actionRows() as $row) {
            $action = $row['action'];
            $entries->push([
                'title' => $action->label,
                'type' => 'Action',
                'url' => route('manual.location', self::citySlug($action->city)).'#action-'.$action->action_key,
                'hint' => ($action->skill?->display_name ?? $action->skill_key).' '.$action->min_level.' · '.$action->city->city,
            ]);
        }

        return $entries->values()->all();
    }
}
