<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ListingStatus;
use App\Enums\VerificationStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\Store;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SettingService $settings) {}

    /**
     * GET /listings
     *
     * `lat` & `lng` SELALU wajib — bukan hanya untuk `sort=nearest`. Seekitar
     * hyperlocal: daftar tanpa acuan lokasi tidak punya makna (API §4.1).
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'radius'   => ['sometimes', 'numeric', 'min:0.1'],
            'category' => ['sometimes', 'integer', 'exists:categories,id'],
            'type'     => ['sometimes', 'in:product,service,rental'],
            'keyword'  => ['sometimes', 'string', 'max:100'],
            'sort'     => ['sometimes', 'in:nearest,cheapest,newest'],
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        $maxRadius = $this->settings->int('max_search_radius_km', 25);
        $radius    = min((float) ($data['radius'] ?? $maxRadius), $maxRadius);

        // Radius berlaku pada LOKASI TOKO, jadi penyaringan spasial dilakukan
        // lewat relasi — listing sendiri tidak punya kolom koordinat.
        $query = Listing::query()
            ->with('store')
            ->where('status', ListingStatus::Active)
            ->whereHas('store', function ($q) use ($lat, $lng, $radius): void {
                // Hanya toko yang tayang — toko pending/ditolak/nonaktif
                // menyeret listingnya keluar dari pencarian (Store::isVisible).
                $q->where('is_active', true)
                    ->where('verification_status', VerificationStatus::Verified->value)
                    ->nearby($lat, $lng, $radius);
            });

        if (isset($data['category'])) {
            $query->whereHas('store', fn ($q) => $q->whereRaw(
                'JSON_CONTAINS(category_ids, ?)', [(string) $data['category']]
            ));
        }

        if (isset($data['type'])) {
            $query->where('listing_type', $data['type']);
        }

        if (isset($data['keyword'])) {
            // Fulltext, bukan LIKE '%kata%': LIKE berawalan wildcard tidak
            // bisa memakai indeks sama sekali (DATABASE.md §7.2).
            $query->whereFullText(['title', 'description'], $data['keyword']);
        }

        match ($data['sort'] ?? 'nearest') {
            // `price IS NULL` lebih dulu: jasa berharga null tidak boleh
            // menempati urutan teratas "termurah".
            'cheapest' => $query->orderByRaw('listings.price IS NULL, listings.price ASC'),
            'newest'   => $query->latest('listings.created_at'),

            // Jarak ada di tabel stores, jadi di-JOIN sekali — bukan subquery
            // berkorelasi yang dievaluasi ulang untuk tiap baris listing.
            default => $query
                ->join('stores', 'stores.id', '=', 'listings.store_id')
                ->select('listings.*')
                ->selectRaw(
                    'ST_Distance_Sphere(stores.location, ST_GeomFromText(?, 4326, ?)) / 1000 AS distance_km',
                    [sprintf('POINT(%F %F)', $lng, $lat), 'axis-order=long-lat'],
                )
                ->orderBy('distance_km'),
        };

        return $this->paginated($query->paginate($this->perPage()), ListingResource::class);
    }

    /** GET /listings/{listing} */
    public function show(Request $request, Listing $listing): JsonResponse
    {
        $listing->load('store');

        // Listing ikut tokonya: kalau tokonya tidak tayang, listingnya pun
        // tidak — kecuali untuk pemilik toko itu sendiri.
        if (! $listing->store->isVisible() && $listing->store->user_id !== $request->user()->id) {
            abort(404);
        }

        return $this->ok(['listing' => new ListingResource($listing)]);
    }

    /** POST /listings */
    public function store(StoreListingRequest $request): JsonResponse
    {
        $store = Store::findOrFail($request->validated('store_id'));

        $this->authorize('createFor', [Listing::class, $store]);

        $listing = Listing::create($request->validated());

        return $this->created(['listing' => new ListingResource($listing->load('store'))]);
    }
}
