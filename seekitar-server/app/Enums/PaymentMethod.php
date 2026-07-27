<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PaymentMethod: string
{
    use HasValues;

    case Cod      = 'cod';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cod      => 'Bayar di Tempat (COD)',
            self::Transfer => 'Transfer Bank',
        };
    }

    /** Transfer perlu unggah bukti sebelum penjual memproses. */
    public function requiresProof(): bool
    {
        return $this === self::Transfer;
    }
}
