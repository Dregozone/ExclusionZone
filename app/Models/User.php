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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role_id', 'premium_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * New survivors start at full health.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'health' => 100,
    ];

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
            'last_active_at' => 'datetime',
            'health' => 'integer',
            'hunger' => 'float',
            'fatigue' => 'float',
            'rested_at' => 'datetime',
            'recovering_until' => 'datetime',
            'scrip' => 'integer',
            'radio_seen_message_id' => 'integer',
            'trades_seen_at' => 'datetime',
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

    public function equipment(): HasMany
    {
        return $this->hasMany(UserEquipment::class);
    }

    public function cosmeticLoadout(): HasOne
    {
        return $this->hasOne(UserCosmeticLoadout::class);
    }

    public function activeWork(): HasOne
    {
        return $this->hasOne(UserWork::class);
    }

    public function troubleEncounter(): HasOne
    {
        return $this->hasOne(TroubleEncounter::class);
    }

    public function cityVisits(): HasMany
    {
        return $this->hasMany(CityVisit::class);
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }

    public function radioMessages(): HasMany
    {
        return $this->hasMany(RadioMessage::class);
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function feedbackReports(): HasMany
    {
        return $this->hasMany(FeedbackReport::class);
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
        return $this->isAdmin() || $this->role_key === 'moderator';
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

    /**
     * Why the player cannot start a city action or route right now, or null when they can.
     */
    public function cityActionRestriction(): ?string
    {
        return match (true) {
            $this->hasActiveWork() => 'Finish or cancel your current work before starting another action or route.',
            $this->recoveryRestriction() !== null => $this->recoveryRestriction(),
            $this->isHeldByTrouble() => 'Trouble is blocking your way. Deal with it before doing anything else here.',
            default => $this->denialReasonForTask('city_action_perform'),
        };
    }

    /**
     * Why the survivor cannot work or travel while recovering from their last death, or null once they can.
     */
    public function recoveryRestriction(): ?string
    {
        if ($this->recovering_until === null || ! $this->recovering_until->isFuture()) {
            return null;
        }

        return "You are still recovering from your collapse. You can work or travel again {$this->recovering_until->diffForHumans()}.";
    }

    /**
     * Whether trouble met on arrival still has to be dealt with before the survivor can act in this city.
     */
    public function isHeldByTrouble(): bool
    {
        return $this->relationLoaded('troubleEncounter')
            ? $this->troubleEncounter !== null
            : $this->troubleEncounter()->exists();
    }

    public function denialReasonForTask(string $taskKey): ?string
    {
        if ($this->canPerformTask($taskKey)) {
            return null;
        }

        return match ($taskKey) {
            'city_action_perform' => 'Your current role cannot travel or perform city actions.',
            'chat_send' => 'Your current role cannot access radio chat.',
            'trade_create' => 'Your current role cannot access the trade board.',
            'combat_initiate' => 'Your current role cannot scout combat contracts.',
            'equip_cosmetic' => 'You need premium access to equip cosmetics.',
            default => 'You cannot perform that action right now.',
        };
    }

    /**
     * The total of one bonus across everything the survivor wears, up to its cap in config/equipment.php.
     * Work speed only counts items that speed up the given skill.
     *
     * @param  'work_speed'|'hunger_reduction'|'fatigue_reduction'|'healing_bonus'|'radiation_protection'  $bonus
     */
    public function equipmentBonusPercent(string $bonus, ?string $skillKey = null): int
    {
        $column = $bonus === 'work_speed' ? 'work_speed_bonus_percent' : "{$bonus}_percent";

        $total = $this->wornItems()
            ->filter(fn (Item $item): bool => $bonus !== 'work_speed' || ($skillKey !== null && $item->bonus_skill_key === $skillKey))
            ->sum(fn (Item $item): int => (int) $item->{$column});

        return min((int) config("equipment.caps.{$bonus}", 0), $total);
    }

    /**
     * The stronger of the weapons held in the survivor's arms, if any.
     */
    public function heldWeapon(): ?Item
    {
        return $this->wornItems()
            ->filter(fn (Item $item): bool => $item->equip_slot === 'arm' && $item->isWeapon())
            ->sortByDesc('attack_bonus')
            ->first();
    }

    /**
     * The worn item with the highest defence bonus, if any.
     */
    public function wornArmour(): ?Item
    {
        return $this->wornItems()
            ->filter(fn (Item $item): bool => $item->isArmour())
            ->sortByDesc('defence_bonus')
            ->first();
    }

    /**
     * @return Collection<int, Item>
     */
    private function wornItems(): Collection
    {
        $this->loadMissing('equipment.item');

        return $this->equipment->pluck('item')->filter()->values();
    }

    public function skillFor(string $skillKey): ?UserSkill
    {
        return $this->skills
            ->first(fn (UserSkill $skill) => $skill->skill?->key === $skillKey);
    }

    public function hasActiveWork(): bool
    {
        return $this->relationLoaded('activeWork')
            ? $this->activeWork !== null
            : $this->activeWork()->exists();
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

                if (Schema::hasTable('city_visits')) {
                    $this->cityVisits()->firstOrCreate(['city_id' => $defaultCity->id]);
                }
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
