<?php

namespace App\Models;

use Database\Factories\EnemyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Something hostile waiting in a city. City actions there that use the same combat skill fight it.
 */
class Enemy extends Model
{
    /** @use HasFactory<EnemyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The skills a fight can test.
     *
     * @var list<string>
     */
    public const COMBAT_SKILLS = ['combat_melee', 'combat_ranged'];

    protected $fillable = [
        'city_id',
        'key',
        'name',
        'description',
        'skill_key',
        'health',
        'min_damage',
        'max_damage',
        'xp_reward',
        'loot',
    ];

    protected function casts(): array
    {
        return [
            'health' => 'integer',
            'min_damage' => 'integer',
            'max_damage' => 'integer',
            'xp_reward' => 'integer',
            'loot' => 'array',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_key', 'key');
    }

    /**
     * @return list<array{item_key: string, chance_percent: int, quantity: int}>
     */
    public function lootTable(): array
    {
        return array_values($this->loot ?? []);
    }
}
