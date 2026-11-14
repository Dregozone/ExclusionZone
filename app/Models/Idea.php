<?php

namespace App\Models;

use Database\Factories\IdeaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A public suggestion that other survivors can upvote.
 */
class Idea extends Model
{
    /** @use HasFactory<IdeaFactory> */
    use HasFactory;

    public const TITLE_MAX_LENGTH = 120;

    public const DESCRIPTION_MAX_LENGTH = 1000;

    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Survivors who would like this idea.
     */
    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idea_votes')->withTimestamps();
    }
}
