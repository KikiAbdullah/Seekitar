<?php
/**
 * Render SETIAP halaman admin sebagai dua peran berbeda, tanpa MySQL.
 *
 * KENAPA PERLU
 * ------------
 * `compile-blade.php` hanya membuktikan bahwa Blade menghasilkan PHP yang sah.
 * Itu tidak cukup untuk panel admin, karena kelas kesalahan yang paling sering
 * terjadi di sini justru lolos kompilasi:
 *
 *   - `route('admin.x')` menunjuk nama route yang tidak ada → RouteNotFoundException
 *   - `@can` dengan nama permission salah ketik → menu hilang diam-diam
 *   - variabel yang lupa dikirim controller → ErrorException saat render
 *   - `@php` di dalam komentar Blade → "Cannot end a section without first starting one"
 *
 * Semuanya baru meledak ketika halamannya dibuka di browser — yang di sandbox
 * ini tidak pernah terjadi.
 *
 * CARA KERJA
 * ----------
 * Gate diisi ulang dengan ability sederhana (bukan Spatie), lalu pengguna
 * palsu disuntikkan. Dengan begitu izin bisa diatur per-render tanpa satu pun
 * baris di basis data. Yang dibuktikan: nama route, variabel view, struktur
 * direktif, dan menu mana yang tampil untuk izin tertentu.
 * Yang TIDAK dibuktikan: query berjalan benar — itu tetap butuh MySQL.
 *
 * Pemakaian:  ./tools/dev/php tools/dev/render-admin.php
 */

$root = __DIR__.'/../../seekitar-server';

putenv('CACHE_STORE=array');     $_ENV['CACHE_STORE']     = $_SERVER['CACHE_STORE']     = 'array';
putenv('SESSION_DRIVER=array');  $_ENV['SESSION_DRIVER']  = $_SERVER['SESSION_DRIVER']  = 'array';
putenv('QUEUE_CONNECTION=sync'); $_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';

require $root.'/vendor/autoload.php';

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/**
 * Angka lencana sidebar diambil dari basis data lewat SidebarComposer.
 *
 * Di sini komposer itu diganti stub: yang sedang diuji adalah STRUKTUR view
 * dan penyaringan @can, bukan hasil COUNT. Tanpa penggantian ini, render
 * pertama langsung membuka koneksi PDO dan runtime WASM mati dengan
 * "RuntimeError: unreachable" — bukan exception PHP yang bisa ditangkap.
 *
 * Di-bind SESUDAH bootstrap: View::composer() menyimpan nama kelasnya dan
 * baru me-resolve dari container saat view dirender.
 */
$app->bind(App\View\Composers\SidebarComposer::class, fn () => new class {
    public function compose(Illuminate\View\View $view): void
    {
        // Empat variabel PERSIS seperti composer asli — stub yang lebih
        // sedikit membiarkan lencana menu tersembunyi oleh `?? 0` dan
        // tata letak kosongnya tidak pernah terlihat di sini.
        $view->with([
            'pendingVerifikasiPengguna' => 2,
            'pendingVerifikasiToko'     => 1,
            'pendingVerifikasi'         => 3,
            'laporanLewatSla'           => 1,
        ]);
    }
});

/**
 * Pengguna uji.
 *
 * Sengaja BUKAN App\Models\User: model asli memakai trait Spatie HasRoles,
 * yang membuat closure Gate::before milik Spatie memanggil checkPermissionTo()
 * dan menembak MySQL. Model tanpa trait itu membuat closure tersebut
 * mengembalikan null, sehingga ability yang didefinisikan di bawah dipakai.
 */
final class RenderUser extends Authenticatable
{
    protected $guarded = [];
    public $timestamps = false;

    /** @var list<string> */
    public array $izin = [];

    /** @var list<string> */
    public array $peran = [];

    public function getRoleNames()
    {
        return collect($this->peran);
    }

    public function getAllPermissions()
    {
        return collect($this->izin)->map(fn (string $n) => (object) ['name' => $n]);
    }
}

const SEMUA_IZIN = [
    'manage-users', 'verify-users', 'manage-stores', 'verify-stores',
    'manage-categories', 'manage-listings', 'manage-requests', 'manage-offers',
    'manage-orders', 'manage-disputes', 'manage-reviews', 'manage-settings',
];

// Tiap permission menjadi ability Gate biasa, dijawab dari daftar $izin.
foreach (SEMUA_IZIN as $izin) {
    Gate::define($izin, fn (RenderUser $user) => in_array($izin, $user->izin, true));
}

/** @return array{0:int,1:string} */
function renderSebagai(string $view, array $data, array $izin, array $peran): array
{
    $user = new RenderUser(['name' => 'Admin Uji', 'email' => 'uji@seekitar.test', 'phone' => '6280000000009']);
    $user->izin  = $izin;
    $user->peran = $peran;

    Auth::setUser($user);

    // $errors normalnya dibagikan middleware ShareErrorsFromSession. Tanpa
    // ini setiap view yang memakai @error gagal dengan "Undefined variable".
    View::share('errors', new ViewErrorBag());

    try {
        return [0, View::make($view, $data)->render()];
    } catch (Throwable $e) {
        return [1, $e::class.': '.$e->getMessage()];
    }
}

// ── Data tiruan seringan mungkin: yang diuji struktur view, bukan isinya ──
$paginatorKosong = new Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, [
    'path' => 'http://localhost/admin',
]);

// ── Toko tiruan utuh untuk halaman detail & sunting ─────────────────────
// Relasi HARUS disuntik manual: model non-persisted yang mengakses relasi
// tak disiapkan akan membuka koneksi basis data — yang sengaja tidak ada
// di harness ini — dan WASM mati dengan "unreachable", bukan exception.
$pemilikToko = (new App\Models\User())->setRawAttributes([
    'id'          => '019fa000-0000-7000-8000-0000000000e1',
    'name'        => 'Siti Aminah',
    'phone'       => '081234567890',
    'verified_at' => '2026-07-01 08:00:00',
], true);

$adminStempel = (new App\Models\User())->setRawAttributes([
    'id'   => '019fa000-0000-7000-8000-0000000000e2',
    'name' => 'Admin Seekitar',
], true);

$tipePertama = App\Enums\StoreType::cases()[0];

$tokoTiruan = (new App\Models\Store())->setRawAttributes([
    'id'                => '019fa000-0000-7000-8000-0000000000f1',
    'user_id'           => $pemilikToko->getAttribute('id'),
    'name'              => 'Toko Barokah ABC',
    'regency'           => 'Kabupaten Pasuruan',
    'regency_code'      => '3514',
    'store_type'        => $tipePertama->value,
    'category_ids'      => '[1]',
    'address'           => 'Jl. Raya Bangil No. 12, Kec. Bangil',
    'latitude'          => -7.59980000,
    'longitude'         => 112.81864000,
    'service_radius_km' => 5,
    'accepts_cod'       => 1,
    'offers_delivery'   => 1,
    'allows_pickup'     => 1,
    'operating_hours'   => json_encode([
        'senin' => ['open' => '07:00', 'close' => '21:00'],
        'minggu' => null,
    ]),
    'npwp'              => '12.345.678.9-012.345',
    'bank_account'      => 'BCA 1234567890',
    'bank_account_name' => 'Siti Aminah',
    'photo'             => 'https://placehold.co/600x400/E7F6EC/168A4A?text=Foto%20Toko',
    'rating_avg'        => 4.5,
    'total_reviews'     => 12,
    'is_active'         => 1,
    'status'            => App\Enums\StoreStatus::Verified->value,
    'verified_at'       => '2026-07-10 09:30:00',
    'verified_by'       => $adminStempel->getAttribute('id'),
    // withCount aslinya; di sini diisi manual agar angka tampil.
    'listings_count'    => 3,
    'offers_count'      => 1,
    'orders_count'      => 9,
    'reviews_count'     => 12,
    'created_at'        => '2026-06-15 10:00:00',
    'updated_at'        => '2026-07-20 10:00:00',
], true);
$tokoTiruan->setRelation('owner', $pemilikToko);
$tokoTiruan->setRelation('verifiedBy', $adminStempel);
$tokoTiruan->setRelation('rejectedBy', null);
$tokoTiruan->setRelation('blockedBy', null);

$kategoriTiruan = collect([
    (new App\Models\Category())->setRawAttributes(['id' => 1, 'name' => 'Sembako'], true),
]);

// Listing tiruan untuk halaman detail Etalase — relasi store disuntik
// langsung; pesanan & penggemar diberi SATU contoh agar cabang
// forelse/empty sama-sama pernah dirender di harness lain.
$listingTiruan = (new App\Models\Listing())->setRawAttributes([
    'id'              => '019fa000-0000-7000-8000-0000000000a1',
    'store_id'        => $tokoTiruan->getAttribute('id'),
    'title'           => 'Beras Pandan Wangi 5 kg',
    'description'     => "Beras pulen hasil panen sendiri.\nTanpa pemutih, wangi alami.",
    'listing_type'    => App\Enums\ListingType::Product->value,
    'price'           => 68000,
    'stock_qty'       => 25,
    'slot'            => null,
    'images'          => json_encode(['https://placehold.co/800x600/DCFCE7/166534?text=Beras%205%20kg']),
    'status'          => App\Enums\ListingStatus::Active->value,
    'favorites_count' => 7,
    'created_at'      => '2026-06-20 09:00:00',
    'updated_at'      => '2026-07-25 09:00:00',
], true);
$listingTiruan->setRelation('store', $tokoTiruan);

$pesananTiruan = (new App\Models\Order())->setRawAttributes([
    'id'              => '019fa000-0000-7000-8000-0000000000b1',
    'order_number'    => 'ORD-20260725-0001',
    'buyer_id'        => '019fa000-0000-7000-8000-0000000000e3',
    'order_type'      => 'product',
    'delivery_method' => 'delivery',
    'quantity'        => 2,
    'total_amount'    => 136000,
    'status'          => App\Enums\OrderStatus::Selesai->value,
    'created_at'      => '2026-07-25 10:00:00',
], true);
$pesananTiruan->setRelation('buyer', (new App\Models\User())->setRawAttributes([
    'id' => '019fa000-0000-7000-8000-0000000000e3', 'name' => 'Budi Santoso', 'phone' => '085600000001',
], true));

$penggemarTiruan = (new App\Models\Favorite())->setRawAttributes([
    'id' => 1, 'listing_id' => $listingTiruan->getAttribute('id'),
    'user_id' => '019fa000-0000-7000-8000-0000000000e4',
], true);
$penggemarTiruan->setRelation('user', (new App\Models\User())->setRawAttributes([
    'id' => '019fa000-0000-7000-8000-0000000000e4', 'name' => 'Rina Wulandari',
], true));

// Pesanan tiruan untuk halaman detail Pesanan — relasi penuh disuntik:
// kedua pihak, sumber (listing), ulasan, dan koordinat tujuan antar
// (kolom virtual withCoordinates, diisi manual di sini).
$pembeliTiruan = (new App\Models\User())->setRawAttributes([
    'id' => '019fa000-0000-7000-8000-0000000000e5', 'name' => 'Agus Prasetyo',
    'phone' => '085600000002', 'verified_at' => '2026-06-01 08:00:00',
], true);

$orderTiruan = (new App\Models\Order())->setRawAttributes([
    'id'                   => '019fa000-0000-7000-8000-0000000000c1',
    'order_number'         => 'ORD-20260727-0007',
    'buyer_id'             => $pembeliTiruan->getAttribute('id'),
    'store_id'             => $tokoTiruan->getAttribute('id'),
    'listing_id'           => $listingTiruan->getAttribute('id'),
    'order_type'           => App\Enums\OrderType::Product->value,
    'quantity'             => 2,
    'total_amount'         => 136000,
    'status'               => App\Enums\OrderStatus::Diproses->value,
    'payment_method'       => App\Enums\PaymentMethod::Transfer->value,
    'delivery_method'      => App\Enums\DeliveryMethod::Delivery->value,
    'shipping_address'     => 'Perum Griya Asri Blok C-7, Bangil',
    'payment_proof_url'    => 'https://placehold.co/800x500/F3F4F6/6B7280?text=Bukti%20Bayar',
    'payment_confirmed_at' => '2026-07-27 09:15:00',
    'latitude'             => -7.60123400,
    'longitude'            => 112.82156700,
    'created_at'           => '2026-07-27 09:00:00',
], true);
$orderTiruan->setRelation('buyer', $pembeliTiruan);
$orderTiruan->setRelation('store', $tokoTiruan);
$orderTiruan->setRelation('listing', $listingTiruan);
$orderTiruan->setRelation('offer', null);
$orderTiruan->setRelation('cancelledBy', null);
$orderTiruan->setRelation('disputes', collect());

$ulasanTiruan = (new App\Models\Review())->setRawAttributes([
    'id' => '019fa000-0000-7000-8000-0000000000d1',
    'order_id' => $orderTiruan->getAttribute('id'),
    'reviewer_id' => $pembeliTiruan->getAttribute('id'),
    'direction' => App\Enums\ReviewDirection::BuyerToStore->value,
    'rating' => 5, 'comment' => 'Beras pulen, pengiriman cepat!',
    'created_at' => '2026-07-28 10:00:00',
], true);
$ulasanTiruan->setRelation('reviewer', $pembeliTiruan);
$orderTiruan->setRelation('reviews', collect([$ulasanTiruan]));

$halaman = [
    'admin.dashboard' => [
        'stats'     => [],
        'sorotan'   => ['label' => 'Total Pengguna', 'nilai' => 0],
        'antrian'   => [],
        'ringkas'   => ['requests' => collect(), 'offers' => collect()],
        'chartHari' => 14,
    ],
    'admin.users.index'          => [],
    'admin.stores.index'         => [],
    'admin.stores.show'          => [
        'store'    => $tokoTiruan,
        'kategori' => collect(['Sembako']),
    ],
    'admin.stores.edit'          => [
        'store'    => $tokoTiruan,
        'kategori' => $kategoriTiruan,
        'tipeToko' => App\Enums\StoreType::cases(),
    ],
    'admin.maps.stores'          => [
        'pusat'  => [-7.5966, 112.8203],
        'batas'  => ['sw' => [-8.5056, 112.5653], 'ne' => [-7.5428, 113.5103]],
        // Kedudukan toko — enum VerificationStatus lama sudah dihapus (2.3).
        'status' => App\Enums\StoreStatus::cases(),
    ],
    'admin.listings.index'       => [],
    'admin.listings.show'        => [
        'listing'   => $listingTiruan,
        'statistik' => ['total' => 9, 'selesai' => 6, 'omzet' => 4500000],
        'pesanan'   => collect([$pesananTiruan]),
        'penggemar' => collect([$penggemarTiruan]),
    ],
    'admin.orders.index'         => [],
    'admin.orders.show'          => ['order' => $orderTiruan],
    'admin.requests.index'       => [],
    'admin.offers.index'         => [],
    'admin.reviews.index'        => [],
    'admin.disputes.index'       => [],
    'admin.categories.index'     => ['categories' => collect()],
    'admin.categories.form'      => ['category' => new App\Models\Category(), 'parents' => collect()],
    'admin.settings.index'       => ['groups' => collect()],
    'admin.verifications.users'  => ['pending' => $paginatorKosong],
    'admin.verifications.stores' => ['pending' => $paginatorKosong],
    'admin.profile.edit'         => ['user' => null],   // diisi ulang di bawah
    'admin.profile.password'     => [],
    'admin.auth.login'           => [],
];

$gagal = 0;
$total = 0;

foreach ($halaman as $view => $data) {
    // Halaman profil menampilkan pengguna yang sedang masuk.
    $perlUser = $view === 'admin.profile.edit';

    foreach ([
        'super-admin' => [SEMUA_IZIN, ['super-admin']],
        'admin'       => [array_values(array_diff(SEMUA_IZIN, ['manage-users', 'manage-settings'])), ['admin']],
    ] as $peranLabel => [$izin, $peran]) {

        $total++;
        $payload = $data;

        if ($perlUser) {
            $u = new RenderUser(['name' => 'Admin Uji', 'email' => 'uji@seekitar.test', 'phone' => '6280000000009']);
            $u->izin = $izin;
            $u->peran = $peran;
            $payload['user'] = $u;
        }

        [$kode, $hasil] = renderSebagai($view, $payload, $izin, $peran);

        if ($kode !== 0) {
            echo "GAGAL RENDER [{$peranLabel}] {$view}\n         -> {$hasil}\n";
            $gagal++;
        }
    }
}

echo "{$total} render halaman admin, {$gagal} gagal\n";

/*
 * Bagian kedua: pastikan menu benar-benar BERBEDA per izin.
 *
 * Render yang sukses tidak membuktikan @can bekerja — sebuah sidebar yang
 * menampilkan semua menu ke semua orang juga "berhasil dirender".
 */
[$k1, $htmlPenuh]  = renderSebagai('admin.partials.sidebar', [], SEMUA_IZIN, ['super-admin']);
[$k2, $htmlKosong] = renderSebagai('admin.partials.sidebar', [], [], ['admin']);

$masalahMenu = 0;

if ($k1 !== 0 || $k2 !== 0) {
    echo "GAGAL RENDER sidebar -> ", $k1 !== 0 ? $htmlPenuh : $htmlKosong, "\n";
    $masalahMenu++;
} else {
    // Tautan yang HANYA boleh muncul saat izinnya ada.
    $terkunci = [
        'manage-users'      => '/admin/users',
        'manage-settings'   => '/admin/settings',
        'manage-offers'     => '/admin/offers',
        'verify-users'      => '/admin/verifications/users',
        'verify-stores'     => '/admin/verifications/stores',
        'manage-disputes'   => '/admin/disputes',
    ];

    foreach ($terkunci as $izin => $jalur) {
        if (! str_contains($htmlPenuh, $jalur)) {
            echo "MENU HILANG: {$jalur} tidak muncul meski izin {$izin} dimiliki\n";
            $masalahMenu++;
        }
        if (str_contains($htmlKosong, $jalur)) {
            echo "MENU BOCOR: {$jalur} muncul padahal izin {$izin} TIDAK dimiliki\n";
            $masalahMenu++;
        }
    }

    // Dasbor harus selalu ada, apa pun izinnya.
    if (! str_contains($htmlKosong, 'Dasbor')) {
        echo "MENU HILANG: Dasbor tidak muncul untuk admin tanpa izin\n";
        $masalahMenu++;
    }
}

echo count($terkunci ?? []) * 2, " pemeriksaan menu, {$masalahMenu} masalah\n";

exit($gagal + $masalahMenu === 0 ? 0 : 1);
