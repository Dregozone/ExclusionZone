<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuest extends Model
{
    protected $fillable = [
        'user_id',
        'quest_id',
        'current_step_index',
        'status',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'array',
            'completed_at' => 'datetime',
            'current_step_index' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
