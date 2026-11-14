<?php

namespace App;

use App\Models\Item;

/**
 * Where a survivor wears an item. Items list the kind of slot they fit (head, body, arm or feet),
 * and an arm item can be held in either arm.
 */
enum EquipSlot: string
{
    case Head = 'head';
    case Body = 'body';
    case LeftArm = 'left_arm';
    case RightArm = 'right_arm';
    case Feet = 'feet';

    /**
     * The kinds of slot an item can name in its equip_slot column, with a label for the admin form.
     */
    public const ITEM_SLOTS = ['head' => 'Headwear', 'body' => 'Body', 'arm' => 'Held in an arm', 'feet' => 'Footwear'];

    public function label(): string
    {
        return match ($this) {
            self::Head => 'Headwear',
            self::Body => 'Body',
            self::LeftArm => 'Left arm',
            self::RightArm => 'Right arm',
            self::Feet => 'Footwear',
        };
    }

    /**
     * The kind of item this slot holds, as named in an item's equip_slot column.
     */
    public function itemSlot(): string
    {
        return match ($this) {
            self::LeftArm, self::RightArm => 'arm',
            default => $this->value,
        };
    }

    /**
     * The slots an item fits, right arm before left so a single tool lands in the right hand.
     *
     * @return list<self>
     */
    public static function fitting(Item $item): array
    {
        return array_values(array_filter(
            [self::Head, self::Body, self::RightArm, self::LeftArm, self::Feet],
            fn (self $slot): bool => $item->equip_slot !== null && $slot->itemSlot() === $item->equip_slot,
        ));
    }
}
