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
        $view->with(['pendingVerifikasi' => 3, 'laporanLewatSla' => 1]);
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

$halaman = [
    'admin.dashboard' => [
        'stats'     => [],
        'antrian'   => [],
        'ringkas'   => ['requests' => collect(), 'offers' => collect()],
        'chartHari' => 14,
    ],
    'admin.users.index'          => [],
    'admin.stores.index'         => [],
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
