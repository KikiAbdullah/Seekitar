<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Nilai default tabel `settings`.
 *
 * Angka-angka ini sengaja tidak ditanam di kode: mengubah radius atau SLA
 * seharusnya cukup lewat panel admin, bukan lewat deploy ulang
 * (`Server_Implementation_Guide.md` §9.12).
 *
 * IDEMPOTEN DENGAN SYARAT: memakai `firstOrCreate`, BUKAN `updateOrCreate`.
 * Bedanya penting — `updateOrCreate` akan mengembalikan nilai yang sudah
 * disunting admin ke default setiap kali deploy. Yang boleh diperbarui hanya
 * metadata tampilan (`label`, `group`, `type`), bukan `value`.
 */
class SettingSeeder extends Seeder
{
    /** key => [value, type, group, label] — sesuai tabel di §9.12. */
    public const DEFAULTS = [
        'max_search_radius_km' => [
            25, 'integer', 'radius', 'Radius pencarian maksimum (km)',
        ],
        'default_request_radius_km' => [
            15, 'integer', 'radius', 'Radius siar permintaan default (km)',
        ],
        'request_expiry_hours' => [
            24, 'integer', 'kedaluwarsa', 'Masa aktif permintaan (jam)',
        ],
        'offer_expiry_hours' => [
            48, 'integer', 'kedaluwarsa', 'Masa aktif penawaran (jam)',
        ],
        'max_request_extensions' => [
            2, 'integer', 'kedaluwarsa', 'Batas perpanjangan permintaan',
        ],
        'review_window_days' => [
            7, 'integer', 'ulasan', 'Jendela pemberian ulasan (hari)',
        ],
        'ktp_review_sla_hours' => [
            24, 'integer', 'sla', 'SLA verifikasi KTP (jam)',
        ],
        'dispute_sla_hours' => [
            24, 'integer', 'sla', 'SLA tanggapan laporan masalah (jam)',
        ],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $key => [$value, $type, $group, $label]) {
            $setting = Setting::firstOrCreate(
                ['key' => $key],
                ['value' => (string) $value, 'type' => $type, 'group' => $group, 'label' => $label],
            );

            // Baris yang sudah ada: perbarui metadata tampilannya saja.
            // `value` DIBIARKAN agar penyetelan admin tidak tertimpa.
            if (! $setting->wasRecentlyCreated) {
                $setting->forceFill(['type' => $type, 'group' => $group, 'label' => $label])->save();
            }
        }

        // SettingService meng-cache seluruh tabel; tanpa dibersihkan, nilai
        // baru tidak terlihat sampai cache kedaluwarsa sendiri.
        Cache::forget('settings:all');
    }
}
