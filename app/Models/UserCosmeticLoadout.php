<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCosmeticLoadout extends Model
{
    protected $fillable = [
        'user_id',
        'outfit_skin_id',
        'ui_theme_id',
        'profile_flair_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outfitSkin(): BelongsTo
    {
        return $this->belongsTo(PremiumCosmetic::class, 'outfit_skin_id');
    }

    public function uiTheme(): BelongsTo
    {
        return $this->belongsTo(PremiumCosmetic::class, 'ui_theme_id');
    }

    public function profileFlair(): BelongsTo
    {
        return $this->belongsTo(PremiumCosmetic::class, 'profile_flair_id');
    }
}
