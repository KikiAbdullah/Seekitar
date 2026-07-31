<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ListingStatus;
use App\Enums\StoreStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreListingRequest;
use App\Http\Requests\Api\UpdateListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\Store;
use App\Services\SettingService;
use App\Support\Jarak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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

        // Radius berlaku pada LOKASI TOKO, jadi penyaringannya lewat relasi —
        // listing sendiri tidak punya kolom koordinat. Pra-filternya kotak
        // pembatas (withinBox, Eloquent murni); lingkaran akuratnya diputus
        // PHP di bawah — tanpa SQL mentah (keputusan skema 2.3).
        $query = Listing::query()
            ->with('store')
            ->where('status', ListingStatus::Active)
            ->whereHas('store', function ($q) use ($lat, $lng, $radius): void {
                // Hanya toko yang tayang — toko pending/ditolak/nonaktif
                // menyeret listingnya keluar dari pencarian (Store::isVisible).
                $q->where('is_active', true)
                    ->where('status', StoreStatus::Verified->value)
                    ->withinBox($lat, $lng, $radius);
            });

        if (isset($data['category'])) {
            $query->whereHas('store', fn ($q) => $q->whereJsonContains('category_ids', (int) $data['category']));
        }

        if (isset($data['type'])) {
            $query->where('listing_type', $data['type']);
        }

        if (isset($data['keyword'])) {
            // Fulltext, bukan LIKE '%kata%': LIKE berawalan wildcard tidak
            // bisa memakai indeks sama sekali (DATABASE.md §7.2).
            $query->whereFullText(['title', 'description'], $data['keyword']);
        }

        /*
         * Lingkaran akurat & pengurutan diputus di PHP atas koleksi kandidat
         * kotak, persis pola StoreController::nearby — paginasi manual,
         * karena halaman SQL akan salah potong kandidat yang sesungguhnya
         * jatuh di luar lingkaran.
         */
        $kandidat = $query->get()
            // Safety limit: jangan muat >500 listing ke memori
            ->take(config('query-cache.max_collection', 500))
            ->map(function (Listing $l) use ($lat, $lng): Listing {
                $l->setAttribute('distance_km', Jarak::haversineKm(
                    $lat, $lng, (float) $l->store->latitude, (float) $l->store->longitude
                ));
                return $l;
            })
            // Sudut kotak pembatas bisa berada di luar lingkaran — buang.
            ->filter(fn (Listing $l) => $l->distance_km <= $radius)
            ->values();

        $kandidat = match ($data['sort'] ?? 'nearest') {
            // Harga null (jasa) tidak boleh menempati urutan teratas
            // "termurah" — didorong ke ekor dengan nilai penjaga.
            'cheapest' => $kandidat->sortBy(
                fn (Listing $l) => $l->price === null ? PHP_FLOAT_MAX : (float) $l->price, SORT_NUMERIC
            )->values(),
            'newest'   => $kandidat->sortByDesc('created_at')->values(),
            default    => $kandidat->sortBy('distance_km', SORT_NUMERIC)->values(),
        };

        $halaman  = (int) $request->integer('page', 1);
        $paginasi = new LengthAwarePaginator(
            $kandidat->forPage($halaman, $this->perPage())->values(),
            $kandidat->count(),
            $this->perPage(),
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return $this->paginated($paginasi, ListingResource::class);
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

    /**
     * PUT /listings/{listing} (API §4.4) — pemilik memperbarui judul, harga,
     * stok/slot, foto, atau status dagangannya. Parsial; tipe tidak berubah
     * (lihat UpdateListingRequest).
     */
    public function update(UpdateListingRequest $request, Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        $listing->fill($request->validated());
        $listing->save();

        return $this->ok(['listing' => new ListingResource($listing->load('store'))], 'Listing berhasil diperbarui');
    }

    /**
     * DELETE /listings/{listing} (API §4.4) — soft delete: riwayat pesanan
     * lama tetap bisa membuka listing asalnya sebagaimana tertangkap saat
     * transaksi berlangsung.
     */
    public function destroy(Listing $listing): JsonResponse
    {
        $this->authorize('delete', $listing);

        $listing->delete();

        return $this->noContent();
    }
}
