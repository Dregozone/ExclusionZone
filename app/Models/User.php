<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role_id', 'premium_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            $user->initializeGameProfile();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'premium_active' => 'bool',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(UserLocation::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function cosmeticLoadout(): HasOne
    {
        return $this->hasOne(UserCosmeticLoadout::class);
    }

    public function activeMutes(): HasMany
    {
        return $this->hasMany(UserMute::class, 'target_user_id')
            ->where('ends_at', '>', now());
    }

    protected function roleKey(): Attribute
    {
        return Attribute::get(fn (): string => $this->role()->value('key') ?? 'user');
    }

    public function isAdmin(): bool
    {
        return $this->role_key === 'admin';
    }

    public function isModerator(): bool
    {
        return in_array($this->role_key, ['moderator', 'admin'], true);
    }

    public function hasPremiumEntitlement(): bool
    {
        return $this->premium_active || $this->role_key === 'premium';
    }

    public function canPerformTask(string $taskKey): bool
    {
        if ($taskKey === 'equip_cosmetic') {
            return $this->hasPremiumEntitlement();
        }

        return $this->role()
            ->with('tasks:id,key')
            ->first()
            ?->tasks
            ->contains('key', $taskKey) ?? false;
    }

    public function skillFor(string $skillKey): ?UserSkill
    {
        return $this->skills
            ->first(fn (UserSkill $skill) => $skill->skill?->key === $skillKey);
    }

    public function isMuted(): bool
    {
        return $this->activeMutes()->exists();
    }

    protected function initializeGameProfile(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        if ($this->role_id === null) {
            $this->forceFill([
                'role_id' => Role::query()->where('key', 'user')->value('id'),
            ])->saveQuietly();
        }

        if (Schema::hasTable('cities') && Schema::hasTable('user_locations') && ! $this->location()->exists()) {
            $defaultCity = City::query()
                ->with('country:id')
                ->where('city', 'Kyiv')
                ->first() ?? City::query()->with('country:id')->first();

            if ($defaultCity !== null) {
                $this->location()->create([
                    'country_id' => $defaultCity->country_id,
                    'city_id' => $defaultCity->id,
                ]);
            }
        }

        if (Schema::hasTable('skills') && Schema::hasTable('user_skills')) {
            Skill::query()
                ->select(['id'])
                ->get()
                ->each(fn (Skill $skill) => $this->skills()->firstOrCreate(
                    ['skill_id' => $skill->id],
                    ['level' => 1, 'xp' => 0],
                ));
        }

        if (Schema::hasTable('user_cosmetic_loadouts')) {
            $this->cosmeticLoadout()->firstOrCreate();
        }
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
