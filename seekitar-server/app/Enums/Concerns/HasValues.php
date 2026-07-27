<?php

namespace App\Enums\Concerns;

/**
 * Daftar nilai mentah sebuah enum.
 *
 * KENAPA ADA: migrasi mendefinisikan kolom `ENUM(...)`/`SET(...)` MySQL dari
 * sini, bukan dari daftar string yang ditulis ulang. Dengan begitu menambah
 * satu case di enum PHP tidak bisa lupa disalin ke skema — keduanya selalu
 * bersumber dari satu tempat.
 */
trait HasValues
{
    /** @return array<int, string|int> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
