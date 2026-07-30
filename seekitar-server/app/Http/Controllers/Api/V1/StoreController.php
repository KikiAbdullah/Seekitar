<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StoreStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStoreRequest;
use App\Http\Requests\Api\UpdateStoreRequest;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SettingService $settings) {}

    /**
     * GET /stores/nearby
     *
     * Radius dibatasi `max_search_radius_km` supaya satu request tidak
     * memindai seluruh kabupaten.
     */
    public function nearby(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'    => ['required', 'numeric', 'between:-90,90'],
            'lng'    => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'numeric', 'min:0.1'],
            'type'   => ['sometimes', 'in:goods,services,rental'],
        ]);

        $maxRadius = $this->settings->int('max_search_radius_km', 25);
        $radius    = min((float) ($data['radius'] ?? $maxRadius), $maxRadius);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        /*
         * Dua tahap, tanpa SQL mentah (keputusan skema 2.3 — lokasi toko
         * adalah kolom DECIMAL berindeks, bukan POINT):
         *   1. SQL  — kotak pembatas lewat whereBetween (Eloquent murni);
         *   2. PHP  — lingkaran akurat lewat Jarak::haversineKm, lalu urut
         *             dan paginasi manual atas koleksinya.
         * Skalanya satu kabupaten: kandidat kotak berjumlah puluhan, jadi
         * menyortirnya di PHP lebih cepat daripada memaksa engine
         * menghitung fungsi jarak untuk setiap baris.
         */
        $kandidat = Store::query()
            ->where('is_active', true)
            // Publik hanya boleh melihat toko yang SUDAH diverifikasi admin —
            // toko pending/ditolak/diblokir tidak pernah tayang, apa pun
            // jaraknya. Cermin Store::isVisible().
            ->where('status', StoreStatus::Verified)
            ->withinBox($lat, $lng, $radius)
            ->when(isset($data['type']), fn ($q) => $q->whereStoreType($data['type']))
            ->get()
            ->map(function (Store $s) use ($lat, $lng): Store {
                $s->setAttribute('distance_km', \App\Support\Jarak::haversineKm(
                    $lat, $lng, (float) $s->latitude, (float) $s->longitude
                ));
                return $s;
            })
            // Sudut kotak pembatas bisa berada di luar lingkaran — buang.
            ->filter(fn (Store $s) => $s->distance_km <= $radius)
            ->sortBy('distance_km', SORT_NUMERIC)
            ->values();

        $halaman  = (int) $request->integer('page', 1);
        $paginasi = new \Illuminate\Pagination\LengthAwarePaginator(
            $kandidat->forPage($halaman, $this->perPage())->values(),
            $kandidat->count(),
            $this->perPage(),
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return $this->paginated($paginasi, StoreResource::class);
    }

    /** GET /stores/{store} */
    public function show(Request $request, Store $store): JsonResponse
    {
        // Toko yang belum tayang (pending/ditolak/nonaktif) tidak bisa
        // dilihat publik via URL tebakan — tetapi PEMILIKNYA tetap boleh
        // membukanya, mis. untuk memeriksa status peninjauan.
        if (! $store->isVisible() && $store->user_id !== $request->user()->id) {
            abort(404);
        }

        // latitude/longitude adalah kolom biasa — dibaca langsung, tanpa
        // fungsi spasial apa pun.
        $store = Store::whereKey($store->getKey())->firstOrFail();

        return $this->ok(['store' => new StoreResource($store)]);
    }

    /** POST /stores — butuh pengguna terverifikasi (no HP + KTP). */
    public function store(StoreStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Store::class);

        $user = $request->user();

        // latitude/longitude kini kolom biasa — ikut mass-assignment,
        // tidak ada lagi penulisan titik lewat ekspresi SQL mentah.
        $store = new Store($request->safe()->except(['photo']));
        $store->user_id      = $user->id;
        $store->regency      = config('seekitar.regency');
        $store->regency_code = config('seekitar.regency_code');

        if ($request->hasFile('photo')) {
            // Foto etalase PUBLIK seperti avatar (bukan data pribadi): akan
            // tampil di hasil pencarian — dan admin mencocokkannya dengan
            // kondisi asli saat verifikasi toko.
            $store->photo = $request->file('photo')->store('stores', 'public');
        }

        $store->save();

        return $this->created(['store' => new StoreResource($store->fresh())]);
    }

    /**
     * PATCH /stores/{store} — pemilik memperbarui profil tokonya (API §3.4).
     * Parsial: hanya field yang dikirim yang berubah.
     */
    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $store->fill($request->safe()->except(['photo']));

        if ($request->hasFile('photo')) {
            // Foto lama sengaja tidak dihapus dulu: membersihkan berkas yatim
            // adalah pekerjaan repositori terpisah, dan salah hapus lebih
            // mahal daripada sisa berkas.
            $store->photo = $request->file('photo')->store('stores', 'public');
        }

        $store->save();

        return $this->ok(['store' => new StoreResource($store->fresh())], 'Toko berhasil diperbarui');
    }

    /** GET /stores/{store}/reviews */
    public function reviews(Store $store): JsonResponse
    {
        $reviews = $store->reviews()
            ->with('reviewer')
            ->latest()
            ->paginate($this->perPage());

        return $this->paginated($reviews, ReviewResource::class);
    }
}
