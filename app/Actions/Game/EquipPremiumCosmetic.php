<?php

namespace App\Actions\Game;

use App\Models\PremiumCosmetic;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class EquipPremiumCosmetic
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $user, PremiumCosmetic $cosmetic): void
    {
        if (! $user->hasPremiumEntitlement()) {
            throw new AuthorizationException('Premium cosmetics require a premium entitlement.');
        }

        if ($cosmetic->gameplay_bonus !== 'none') {
            throw new AuthorizationException('Gameplay-affecting cosmetics are not allowed.');
        }

        $column = match ($cosmetic->cosmetic_type) {
            'outfit_skin' => 'outfit_skin_id',
            'ui_theme' => 'ui_theme_id',
            'profile_flair' => 'profile_flair_id',
            default => throw new AuthorizationException('Unknown cosmetic type.'),
        };

        $user->cosmeticLoadout()->updateOrCreate(
            ['user_id' => $user->id],
            [$column => $cosmetic->id],
        );
    }
}
