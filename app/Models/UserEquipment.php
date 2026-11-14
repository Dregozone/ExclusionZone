<?php

namespace App\Models;

use App\EquipSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One item worn by a survivor in one slot. A worn item is out of the pack until it is taken off.
 */
class UserEquipment extends Model
{
    protected $table = 'user_equipment';

    protected $fillable = [
        'user_id',
        'slot',
        'item_id',
    ];

    protected function casts(): array
    {
        return [
            'slot' => EquipSlot::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
