<?php

namespace App\Enums;

enum DeliveryMethod: string
{
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
