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
    'photo'             => 'https://picsum.photos/seed/toko-tiruan/600/400',
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
    'admin.orders.index'         => [],
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
