<?php

namespace App\Models\Concerns;

use DateTimeInterface;

/**
 * Timestamp diserialisasi sebagai ISO 8601 UTC berakhiran `Z` (API §12.1).
 *
 * KENAPA PERLU: bawaan Laravel memakai `toJSON()` yang menyertakan mikrodetik
 * (`2026-07-28T03:00:00.000000Z`). Kontrak API menyebut `2026-07-28T03:00:00Z`
 * — presisi tambahan itu tidak berguna bagi klien dan membuat perbandingan
 * string meleset.
 *
 * `serializeDate()` adalah method PROTECTED di setiap model, jadi tidak bisa
 * disetel sekali dari service provider. Trait ini adalah cara termurah
 * menerapkannya seragam tanpa base class baru.
 */
trait SerializesDatesAsUtc
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        // setTimezone('UTC') bukan formalitas: kalau app.timezone diubah,
        // nilai tetap keluar sebagai UTC seperti yang dijanjikan kontrak.
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
