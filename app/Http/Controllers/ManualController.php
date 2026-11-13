<?php

namespace App\Http\Controllers;

use App\Actions\Game\BuildManualCatalogue;
use App\Actions\Game\FeedBrainScorcher;
use App\ItemRarity;
use App\Models\Item;
use App\Models\Quest;
use App\Models\Skill;
use App\Models\SkillLevelRule;
use App\Models\TroubleType;
use App\Weather;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The public Survivor's Manual. Everything here is readable without an account.
 */
class ManualController extends Controller
{
    public function __construct(public BuildManualCatalogue $catalogue) {}

    public function index(): View
    {
        $kyiv = $this->catalogue->cities()->firstWhere('city', 'Kyiv');

        return view('manual.index', [
            'guides' => config('manual.guides'),
            'skills' => Skill::query()->withCount('actions')->orderBy('display_name')->get(),
            'counts' => [
                'cities' => $this->catalogue->cities()->count(),
                'actions' => $this->catalogue->actionRows()->count(),
                'items' => $this->catalogue->items()->count(),
                'skills' => Skill::query()->count(),
            ],
            'startingCity' => $kyiv,
        ]);
    }

    public function guide(string $topic): View
    {
        $guides = config('manual.guides');
        abort_unless(array_key_exists($topic, $guides), 404);

        $keys = array_keys($guides);
        $position = array_search($topic, $keys, true);
        $previous = $keys[$position - 1] ?? null;
        $next = $keys[$position + 1] ?? null;

        return view("manual.guides.{$topic}", [
            'topic' => $topic,
            'guide' => $guides[$topic],
            'previous' => $previous === null ? null : ['slug' => $previous, ...$guides[$previous]],
            'next' => $next === null ? null : ['slug' => $next, ...$guides[$next]],
            ...$this->guideData($topic),
        ]);
    }

    public function skills(): View
    {
        $rows = $this->catalogue->actionRows();

        return view('manual.skills.index', [
            'skills' => Skill::query()->orderBy('display_name')->get()->map(fn (Skill $skill): array => [
                'skill' => $skill,
                'actions' => $rows->filter(fn (array $row): bool => $row['action']->skill_key === $skill->key)->values(),
            ]),
            'tiers' => SkillLevelRule::query()->orderBy('level_min')->get(),
        ]);
    }

    public function skill(Request $request, Skill $skill): View
    {
        $rows = $this->catalogue->actionRowsForSkill($skill);
        $progress = $request->user()?->loadMissing('skills.skill')->skillFor($skill->key);

        return view('manual.skills.show', [
            'skill' => $skill,
            'rows' => $rows,
            'enemies' => $this->catalogue->enemies()->where('skill_key', $skill->key)->values(),
            'troubleTypes' => TroubleType::query()->with('lootItem')->where('skill_key', $skill->key)->orderBy('fighter_level')->get(),
            'tiers' => SkillLevelRule::query()->orderBy('level_min')->get(),
            'otherSkills' => Skill::query()->whereKeyNot($skill->id)->orderBy('display_name')->get(),
            'image' => $this->skillImage($skill->key),
            'playerLevel' => $progress?->level,
            'playerXp' => $progress?->xp,
            'allSkills' => Skill::query()->orderBy('display_name')->get(['id', 'key', 'display_name']),
        ]);
    }

    public function locations(): View
    {
        return view('manual.locations.index', [
            'continents' => $this->catalogue->cities()
                ->sortBy(fn ($city): string => ($city->country?->country ?? '').' '.$city->city)
                ->groupBy(fn ($city): string => $city->country?->continent ?? 'Unknown')
                ->sortKeys(),
            'enemyCounts' => $this->catalogue->enemies()->countBy('city_id'),
        ]);
    }

    public function location(string $city): View
    {
        $location = $this->catalogue->findCityBySlug($city);
        abort_if($location === null, 404);

        return view('manual.locations.show', [
            'city' => $location,
            'rows' => $this->catalogue->actionRowsForCity($location),
            'enemies' => $this->catalogue->enemies()->where('city_id', $location->id)->values(),
            'questSteps' => DB::table('quest_steps')
                ->join('quests', 'quests.id', '=', 'quest_steps.quest_id')
                ->where('quest_steps.city_id', $location->id)
                ->whereNull('quests.deleted_at')
                ->where('quests.is_active', true)
                ->orderBy('quests.quest_type')
                ->orderBy('quests.sequence_order')
                ->get(['quests.name', 'quests.quest_type', 'quest_steps.person_of_interest'])
                ->unique(fn ($step): string => $step->name.$step->person_of_interest)
                ->values(),
            'hasBrainScorcher' => $location->city === 'Pripyat',
            'isStartingCity' => $location->city === 'Kyiv',
        ]);
    }

    public function items(): View
    {
        $rows = $this->catalogue->actionRows();

        return view('manual.items.index', [
            'items' => $this->catalogue->items()->values(),
            'producedCounts' => $rows->filter(fn (array $row): bool => $row['output'] !== null)->countBy(fn (array $row): string => $row['output']['key']),
            'rarities' => ItemRarity::cases(),
        ]);
    }

    public function item(Item $item): View
    {
        return view('manual.items.show', [
            'item' => $item,
            ...$this->catalogue->usageFor($item),
        ]);
    }

    public function quests(): View
    {
        $quests = Quest::query()
            ->with(['steps.city', 'rewardItem', 'rewardSkill', 'prerequisite'])
            ->where('is_active', true)
            ->orderBy('sequence_order')
            ->orderBy('name')
            ->get();

        return view('manual.quests', [
            'story' => $quests->where('quest_type', 'story')->values(),
            'jobs' => $quests->where('quest_type', '!=', 'story')->values(),
        ]);
    }

    public function faq(): View
    {
        return view('manual.faq', [
            'tradePosts' => $this->catalogue->cities()->where('has_trade_post', true)->values(),
        ]);
    }

    public function search(): JsonResponse
    {
        return response()->json($this->catalogue->searchIndex());
    }

    /**
     * @return array<string, mixed>
     */
    private function guideData(string $topic): array
    {
        return match ($topic) {
            'getting-started' => [
                'starterRows' => $this->catalogue->actionRows()
                    ->filter(fn (array $row): bool => $row['action']->city?->city === 'Kyiv')
                    ->values(),
                'startingCity' => $this->catalogue->cities()->firstWhere('city', 'Kyiv'),
            ],
            'survival' => [
                'thresholds' => config('survival.thresholds'),
                'weathers' => Weather::cases(),
                'foods' => $this->catalogue->items()->filter(fn (Item $item): bool => $item->isFood())->sortByDesc('hunger_restored')->values(),
                'medical' => $this->catalogue->items()->filter(fn (Item $item): bool => $item->isMedical())->sortBy('health_restored')->values(),
                'healingRows' => $this->catalogue->actionRows()->filter(fn (array $row): bool => $row['health'] > 0)->values(),
                'irradiated' => $this->catalogue->cities()->where('radiation_damage', '>', 0)->values(),
                'tradePosts' => $this->catalogue->cities()->where('has_trade_post', true)->values(),
            ],
            'travel' => [
                'troubleTypes' => TroubleType::query()->with(['skill', 'lootItem'])->orderBy('fighter_level')->get(),
                'riskiestCities' => $this->catalogue->cities()->sortByDesc('trouble_chance_pct')->take(5)->values(),
                'safestCities' => $this->catalogue->cities()->sortBy('trouble_chance_pct')->take(5)->values(),
            ],
            'combat' => [
                'enemies' => $this->catalogue->enemies(),
                'catalogueItems' => $this->catalogue->items(),
                'weapons' => $this->catalogue->items()->filter(fn (Item $item): bool => $item->isWeapon())->sortBy('attack_bonus')->values(),
                'troubleTypes' => TroubleType::query()->with(['skill', 'lootItem'])->orderBy('fighter_level')->get(),
            ],
            'trading-and-radio' => [
                'tradePosts' => $this->catalogue->cities()->where('has_trade_post', true)->values(),
            ],
            'brain-scorcher' => [
                'rarities' => ItemRarity::cases(),
                'scorchedItems' => $this->catalogue->items()->filter(fn (Item $item): bool => $item->rarity === ItemRarity::Scorched)->values(),
                'odds' => collect(ItemRarity::cases())
                    ->reject(fn (ItemRarity $rarity): bool => $rarity->isScorcherExclusive())
                    ->mapWithKeys(fn (ItemRarity $rarity): array => [$rarity->value => [
                        'rarity' => $rarity,
                        'by_quantity' => collect([1, 4, FeedBrainScorcher::MAX_EFFECTIVE_QUANTITY])
                            ->mapWithKeys(fn (int $quantity): array => [$quantity => app(FeedBrainScorcher::class)->oddsFor($rarity, $quantity)])
                            ->all(),
                    ]]),
                'burnMinutes' => intdiv(FeedBrainScorcher::BASE_DURATION_SECONDS, 60),
                'maxEffectiveQuantity' => FeedBrainScorcher::MAX_EFFECTIVE_QUANTITY,
            ],
            'skills-and-work' => [
                'tiers' => SkillLevelRule::query()->orderBy('level_min')->get(),
            ],
            default => [],
        };
    }

    private function skillImage(string $skillKey): string
    {
        return file_exists(public_path("images/work/{$skillKey}.jpg"))
            ? "images/work/{$skillKey}.jpg"
            : 'images/work/default.jpg';
    }
}
