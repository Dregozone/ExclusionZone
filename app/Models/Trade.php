<?php

namespace App\Models;

use App\TradeStatus;
use Database\Factories\TradeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    /** @use HasFactory<TradeFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'initiator_id',
        'recipient_id',
        'city_id',
        'status',
        'initiator_committed_at',
        'recipient_committed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'initiator_committed_at' => 'immutable_datetime',
            'recipient_committed_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('initiator_id', $user->id)
            ->orWhere('recipient_id', $user->id));
    }

    public function involves(User $user): bool
    {
        return $this->initiator_id === $user->id || $this->recipient_id === $user->id;
    }

    public function isInitiator(User $user): bool
    {
        return $this->initiator_id === $user->id;
    }

    public function partnerIdOf(User $user): int
    {
        return $this->isInitiator($user) ? $this->recipient_id : $this->initiator_id;
    }

    public function hasCommitted(User $user): bool
    {
        return $this->isInitiator($user)
            ? $this->initiator_committed_at !== null
            : $this->recipient_committed_at !== null;
    }

    public function bothCommitted(): bool
    {
        return $this->initiator_committed_at !== null && $this->recipient_committed_at !== null;
    }
}
