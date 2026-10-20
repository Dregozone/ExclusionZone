<?php

namespace App;

enum Weather: string
{
    case Clear = 'clear';
    case Rain = 'rain';
    case Storm = 'storm';

    public function label(): string
    {
        return match ($this) {
            self::Clear => 'Clear',
            self::Rain => 'Rain',
            self::Storm => 'Storm',
        };
    }

    public function timerIncreasePercent(): int
    {
        return match ($this) {
            self::Clear => 0,
            self::Rain => 15,
            self::Storm => 30,
        };
    }

    public function itemLossChancePercent(): int
    {
        return match ($this) {
            self::Clear => 0,
            self::Rain => 10,
            self::Storm => 25,
        };
    }

    public function experienceBonusPercent(): int
    {
        return match ($this) {
            self::Clear => 0,
            self::Rain => 10,
            self::Storm => 25,
        };
    }

    public function isBad(): bool
    {
        return $this !== self::Clear;
    }

    public function effectsSummary(): string
    {
        if (! $this->isBad()) {
            return 'No effect on timers or loot.';
        }

        return "Timers +{$this->timerIncreasePercent()}% · {$this->itemLossChancePercent()}% chance to lose loot · +{$this->experienceBonusPercent()}% XP";
    }
}
