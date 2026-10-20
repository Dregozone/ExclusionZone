<?php

namespace App\Actions\Game;

use App\Models\CityAction;
use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use App\Weather;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Lottery;

class PerformCityAction
{
    /**
     * @return array{xp:int,item_name:?string,quantity:int,levels_gained:int,item_lost:bool}
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, CityAction $action, Weather $weather = Weather::Clear): array
    {
        $skillProgress = $this->authorize($user, $action);

        $reward = $action->reward_profile;
        $xp = (int) ceil((int) data_get($reward, 'xp', 10) * (100 + $weather->experienceBonusPercent()) / 100);
        $quantity = (int) data_get($reward, 'quantity', 1);
        $itemKey = data_get($reward, 'item_key');
        $levelsGained = 0;
        $itemName = null;
        $itemLost = false;

        DB::transaction(function () use ($user, $skillProgress, $xp, $quantity, $itemKey, $weather, &$levelsGained, &$itemName, &$itemLost): void {
            $levelsGained = $this->applyExperience($skillProgress, $xp);

            if ($itemKey !== null && $quantity > 0) {
                $item = Item::query()->where('key', $itemKey)->firstOrFail();
                $itemName = $item->name;
                $itemLost = $this->isItemLostToWeather($weather);

                if (! $itemLost) {
                    $inventoryItem = $user->inventoryItems()->firstOrCreate(
                        ['item_id' => $item->id],
                        ['quantity' => 0],
                    );

                    $inventoryItem->increment('quantity', $quantity);
                }
            }

            $user->location()->update([
                'updated_at' => now(),
            ]);
        });

        return [
            'xp' => $xp,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'levels_gained' => $levelsGained,
            'item_lost' => $itemLost,
        ];
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(User $user, CityAction $action): UserSkill
    {
        $user->loadMissing('location.city', 'skills.skill');

        if ($user->location?->city_id !== $action->city_id) {
            throw new AuthorizationException('You can only perform actions in your current city.');
        }

        $skillProgress = $user->skillFor($action->skill_key) ?? $this->createSkillProgress($user, $action->skill_key);

        if ($skillProgress->level < $action->min_level) {
            throw new AuthorizationException('Your skill level is too low for that action.');
        }

        return $skillProgress;
    }

    private function isItemLostToWeather(Weather $weather): bool
    {
        if ($weather->itemLossChancePercent() === 0) {
            return false;
        }

        return Lottery::odds($weather->itemLossChancePercent(), 100)->choose();
    }

    private function createSkillProgress(User $user, string $skillKey): UserSkill
    {
        $skill = Skill::query()->where('key', $skillKey)->firstOrFail();

        return $user->skills()->create([
            'skill_id' => $skill->id,
            'level' => 1,
            'xp' => 0,
        ]);
    }

    private function applyExperience(UserSkill $skillProgress, int $xp): int
    {
        $levelsGained = 0;
        $skillProgress->xp += $xp;

        while ($skillProgress->xp >= ($skillProgress->level * 100)) {
            $skillProgress->xp -= $skillProgress->level * 100;
            $skillProgress->level++;
            $levelsGained++;
        }

        $skillProgress->save();

        return $levelsGained;
    }
}
