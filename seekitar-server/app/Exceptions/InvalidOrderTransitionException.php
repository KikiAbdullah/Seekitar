<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use RuntimeException;

/**
 * Dilempar saat status pesanan hendak dipindah ke keadaan yang tidak sah.
 *
 * Pesannya sengaja menyebutkan transisi apa yang SEBENARNYA diizinkan —
 * "transisi tidak valid" saja memaksa pembaca membuka kode state machine
 * untuk tahu apa yang salah.
 */
class InvalidOrderTransitionException extends RuntimeException
{
    /** @param array<int, OrderStatus> $allowed */
    public static function between(OrderStatus $from, OrderStatus $to, array $allowed = []): self
    {
        $allowedList = $allowed === []
            ? 'tidak ada (status akhir)'
            : implode(', ', array_map(static fn (OrderStatus $s): string => $s->value, $allowed));

        return new self(sprintf(
            'Transisi status pesanan dari "%s" ke "%s" tidak diizinkan. Yang diizinkan: %s.',
            $from->value,
            $to->value,
            $allowedList,
        ));
    }
}
