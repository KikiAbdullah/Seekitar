<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Database\Factories\Support\Wilayah;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Peta sebaran toko (Server_Implementation_Guide.md §9.2).
 *
 * KENAPA HALAMAN TERPISAH, BUKAN TAB DI /admin/stores
 * ---------------------------------------------------
 * Tabel menjawab "toko mana yang perlu saya tindak"; peta menjawab "wilayah
 * mana yang belum terlayani". Pertanyaan kedua tidak bisa dijawab tabel
 * berpaginasi 25 baris — pola sebarannya baru terlihat kalau seluruh titik
 * digambar sekaligus.
 *
 * KENAPA LEAFLET + OpenStreetMap
 * ------------------------------
 * Tanpa API key, tanpa kuota, tanpa kartu kredit. Google Maps JS API menuntut
 * penagihan aktif sejak 2018 dan akan menimpa peta dengan "development only"
 * bila tidak dibayar — tidak layak untuk panel internal satu kabupaten.
 * Ubinnya dari tile.openstreetmap.org, yang mensyaratkan atribusi; atribusi
 * itu ditegakkan tools/dev/check-admin-menu.mjs.
 */
class StoreMapController extends Controller
{
    /**
     * Batas Kabupaten Pasuruan (BPK Jatim): 112°33'55"–113°30'37" BT,
     * 7°32'34"–8°30'20" LS.
     *
     * Dipakai untuk mengunci pan/zoom peta. Tanpa batas ini pengguna bisa
     * menggeser peta ke Samudra Hindia dan mengira datanya hilang.
     */
    private const BATAS = [
        'sw' => [-8.5056, 112.5653],   // barat daya [lat, lng]
        'ne' => [-7.5428, 113.5103],   // timur laut
    ];

    public function index(): View
    {
        return view('admin.maps.stores', [
            'pusat'  => [Wilayah::PUSAT_LAT, Wilayah::PUSAT_LNG],
            'batas'  => self::BATAS,
            'status' => StoreStatus::cases(),
        ]);
    }

    /**
     * Titik toko sebagai GeoJSON FeatureCollection.
     *
     * Endpoint TERPISAH dari halaman supaya peta bisa digambar duluan lalu
     * diisi titik — halaman tidak menunggu query selesai.
     *
     * latitude/longitude toko adalah kolom DECIMAL biasa (bukan POINT
     * lagi), jadi dibaca langsung — tidak ada fungsi spasial, tidak ada
     * jebakan urutan sumbu, dan filternya memakai indeks stores_latlng_idx
     * bila suatu saat peta membatasi viewport.
     */
    public function data(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $stores = Store::query()
            ->select(['id', 'name', 'address', 'status', 'is_active', 'latitude', 'longitude'])
            ->when(
                $status !== '' && StoreStatus::tryFrom($status),
                fn ($q) => $q->where('status', $status),
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $stores->map(fn (Store $s) => [
                'type'     => 'Feature',
                'geometry' => $s->coordinates(),
                'properties' => [
                    'id'      => $s->id,
                    'nama'    => $s->name,
                    'alamat'  => $s->address,
                    'status'  => $s->status?->value,
                    'label'   => $s->status?->label(),
                    'aktif'   => (bool) $s->is_active,
                ],
            ])->all(),
        ]);
    }
}
