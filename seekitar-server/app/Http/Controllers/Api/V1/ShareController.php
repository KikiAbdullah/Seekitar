<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ListingStatus;
use App\Enums\StoreStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;

/**
 * Generate teks/link untuk dibagikan ke WhatsApp dan media sosial.
 */
class ShareController extends Controller
{
    use ApiResponse;

    /** GET /listings/{listing}/share */
    public function listing(Listing $listing): JsonResponse
    {
        if ($listing->status !== ListingStatus::Active
            || ! $listing->store
            || ! $listing->store->is_active
            || $listing->store->status !== StoreStatus::Verified) {
            return $this->fail('Listing tidak tersedia.', 404);
        }

        $title    = $listing->title;
        $store    = $listing->store->name;
        $price    = $listing->price !== null
            ? \App\Support\Angka::rupiah($listing->price)
            : 'Hubungi penjual';
        $regency  = config('seekitar.regency');
        $webUrl   = route('web.listing.show', $listing);

        $whatsappText = "*{$title}*\n"
            . "💰 {$price}\n"
            . "🏪 {$store}\n"
            . "📍 {$regency}\n\n"
            . "Lihat selengkapnya di Seekitar 👇\n"
            . "{$webUrl}\n\n"
            . "📲 Unduh aplikasi: " . config('seekitar.play_store_url', '#');

        return $this->ok([
            'whatsapp_text' => $whatsappText,
            'web_url'       => $webUrl,
            'title'         => $title,
            'price'         => $price,
            'store_name'    => $store,
        ]);
    }
}
