<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'reward_item_id',
        'reward_item_quantity',
        'reward_skill_id',
        'reward_xp_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'reward_xp_amount' => 'integer',
            'reward_item_quantity' => 'integer',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(QuestStep::class)->orderBy('step_order');
    }

    public function rewardItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'reward_item_id');
    }

    public function rewardSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'reward_skill_id');
    }
}
