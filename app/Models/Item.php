<?php

namespace App\Models;

use App\ItemRarity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'rarity' => 'common',
        'is_quest_item' => false,
    ];

    protected $fillable = [
        'key',
        'name',
        'description',
        'rarity',
        'is_quest_item',
    ];

    protected function casts(): array
    {
        return [
            'rarity' => ItemRarity::class,
            'is_quest_item' => 'boolean',
        ];
    }
}
