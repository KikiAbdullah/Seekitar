<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
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
            'status' => VerificationStatus::cases(),
        ]);
    }

    /**
     * Titik toko sebagai GeoJSON FeatureCollection.
     *
     * Endpoint TERPISAH dari halaman supaya peta bisa digambar duluan lalu
     * diisi titik — halaman tidak menunggu query selesai.
     *
     * `withCoordinates()` membaca kolom POINT lewat ST_Latitude/ST_Longitude.
     * Membacanya sebagai properti biasa hanya menghasilkan WKB biner yang
     * tidak berguna di PHP, dan itu gagal DIAM-DIAM: JSON tetap terbentuk,
     * hanya saja koordinatnya sampah.
     *
     * URUTAN select() WAJIB sebelum withCoordinates(): select() MENIMPA
     * seluruh daftar kolom, jadi menuliskannya setelah scope akan membuang
     * latitude/longitude yang baru ditambahkan — geometry jadi null dan
     * tak satu pun titik tergambar di peta (lihat docblock HasLocation).
     */
    public function data(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $stores = Store::query()
            ->select(['id', 'name', 'address', 'verification_status', 'is_active', 'location'])
            ->withCoordinates()
            /*
             * Toko tanpa koordinat DIBUANG di SQL, bukan disaring di PHP.
             * Menyaringnya belakangan berarti membawa baris yang pasti
             * dibuang melewati jaringan, dan lebih buruk: `null` yang lolos
             * ke Leaflet melempar "Invalid LatLng" yang mematikan SELURUH
             * peta, bukan hanya satu titik.
             */
            ->whereNotNull('location')
            ->when(
                $status !== '' && VerificationStatus::tryFrom($status),
                fn ($q) => $q->where('verification_status', $status),
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
                    'status'  => $s->verification_status?->value,
                    'label'   => $s->verification_status?->label(),
                    'aktif'   => (bool) $s->is_active,
                ],
            ])->all(),
        ]);
    }
}
