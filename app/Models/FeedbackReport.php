<?php

namespace App\Models;

use App\FeedbackType;
use Database\Factories\FeedbackReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A private bug report or piece of feedback, seen only by the reporter and admins.
 */
class FeedbackReport extends Model
{
    /** @use HasFactory<FeedbackReportFactory> */
    use HasFactory;

    public const TITLE_MAX_LENGTH = 120;

    public const DETAILS_MAX_LENGTH = 5000;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'details',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => FeedbackType::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * @param  Builder<FeedbackReport>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }
}
