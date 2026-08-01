<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\StoreStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerRequestResource;
use App\Http\Resources\ListingResource;
use App\Models\CustomerRequest;
use App\Models\Listing;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feed beranda — listing terdekat & permintaan baru.
 *
 * Berbeda dari /listings yang hanya pencarian: feed menggabungkan dua jenis
 * konten yang paling relevan untuk layar pertama aplikasi.
 */
class HomeController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SettingService $settings) {}

    /** GET /home */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'  => ['required', 'numeric', 'between:-90,90'],
            'lng'  => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];
        $radius = (float) $this->settings->int('max_search_radius_km', 25);

        $listings = Listing::query()
            ->with('store')
            ->where('status', ListingStatus::Active->value)
            ->whereHas('store', fn ($q) => $q
                ->where('is_active', true)
                ->where('status', StoreStatus::Verified->value)
                ->withinBox($lat, $lng, $radius))
            ->latest()
            ->limit(12)
            ->get();

        $requests = CustomerRequest::query()
            ->with('user')
            ->withCount('offers')
            ->where('status', RequestStatus::Open)
            ->where('expires_at', '>', now())
            ->where('user_id', '!=', $request->user()->id)
            ->withCoordinates()
            ->nearby($lat, $lng, $radius)
            ->latest()
            ->limit(6)
            ->get();

        return $this->ok([
            'listings' => ListingResource::collection($listings),
            'requests' => CustomerRequestResource::collection($requests),
        ]);
    }
}
