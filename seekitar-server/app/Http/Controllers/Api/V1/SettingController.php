<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Konfigurasi runtime publik — dipakai mobile app untuk membaca pengaturan
 * yang diperlukan di sisi klien (radius maksimum, URL Play Store, tenggat, dll).
 *
 * TIDAK mengembalikan SELURUH pengaturan — hanya yang aman & relevan untuk
 * klien mobile. Rahasia (token gateway, dll) tidak pernah dikirim ke klien.
 */
class SettingController extends Controller
{
    use ApiResponse;

    /** GET /config — publik, tanpa token. */
    public function publicConfig(): JsonResponse
    {
        return $this->ok([
            'max_search_radius_km'    => (int) config('settings.max_search_radius_km', 25),
            'default_request_radius'  => (int) config('settings.default_request_radius_km', 15),
            'request_expiry_hours'    => (int) config('settings.request_expiry_hours', 24),
            'offer_expiry_hours'      => (int) config('settings.offer_expiry_hours', 48),
            'max_request_extensions'  => (int) config('settings.max_request_extensions', 2),
            'dispute_sla_hours'       => (int) config('settings.dispute_sla_hours', 24),
            'max_listing_images'      => (int) config('settings.max_listing_images', 5),
            'max_image_size_kb'       => (int) config('settings.max_image_size_kb', 5120),
            'regency'                 => config('seekitar.regency'),
            'regency_code'            => config('seekitar.regency_code'),
            'company_name'            => config('seekitar.company.name'),
            'play_store_url'          => config('seekitar.play_store_url'),
            'whatsapp'                => config('seekitar.contacts.whatsapp'),
            'email_complaint'         => config('seekitar.contacts.complaint'),
        ]);
    }
}
