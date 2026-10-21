<?php

namespace App\Models;

use Database\Factories\RadioMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadioMessage extends Model
{
    /** @use HasFactory<RadioMessageFactory> */
    use HasFactory;

    public const MAX_LENGTH = 500;

    protected $fillable = [
        'user_id',
        'body',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
