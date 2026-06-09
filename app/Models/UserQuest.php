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
        'completion_count',
        'active_requirements',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'array',
            'completed_at' => 'datetime',
            'current_step_index' => 'integer',
            'completion_count' => 'integer',
            'active_requirements' => 'array',
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

    public function isRepeatable(): bool
    {
        return $this->status === 'repeatable';
    }
}
