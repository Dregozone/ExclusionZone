<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'quest_id',
        'step_order',
        'city_id',
        'person_of_interest',
        'action_label',
        'interaction_text',
        'required_item_id',
        'required_item_quantity',
        'consumes_item',
        'requirement_variants',
    ];

    protected function casts(): array
    {
        return [
            'consumes_item' => 'bool',
            'required_item_quantity' => 'integer',
            'requirement_variants' => 'array',
        ];
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function requiredItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'required_item_id');
    }
}
