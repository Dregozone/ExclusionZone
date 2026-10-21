<?php

namespace App;

enum ItemRarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case Epic = 'epic';
    case Scorched = 'scorched';

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Common',
            self::Uncommon => 'Uncommon',
            self::Rare => 'Rare',
            self::Epic => 'Epic',
            self::Scorched => 'Scorched',
        };
    }

    /**
     * Position on the ladder from Common (1) to Scorched (5); higher is more valuable.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Common => 1,
            self::Uncommon => 2,
            self::Rare => 3,
            self::Epic => 4,
            self::Scorched => 5,
        };
    }

    public function higher(): self
    {
        return self::fromRank(min($this->rank() + 1, self::Scorched->rank()));
    }

    public function lower(): ?self
    {
        return $this === self::Common ? null : self::fromRank($this->rank() - 1);
    }

    /**
     * Scorched items only come out of the Brain Scorcher; city actions never drop them.
     */
    public function isScorcherExclusive(): bool
    {
        return $this === self::Scorched;
    }

    private static function fromRank(int $rank): self
    {
        return collect(self::cases())->first(fn (self $rarity): bool => $rarity->rank() === $rank);
    }
}
