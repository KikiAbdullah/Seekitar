<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum DeliveryMethod: string
{
    use HasValues;

    case Pickup   = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup   => 'Ambil di Tempat',
            self::Delivery => 'Diantar Penjual',
        };
    }

    public function requiresAddress(): bool
    {
        return $this === self::Delivery;
    }
}
