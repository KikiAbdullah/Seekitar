<?php

namespace Tests\Unit;

use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\TestCase;

/**
 * Kontrak menu panel admin.
 *
 * Ketidakcocokan antara @can di sidebar dan `permission:` di route tidak
 * menimbulkan error apa pun. Ia muncul sebagai menu yang menolak saat diklik,
 * atau fitur yang hilang tanpa jejak — keduanya baru ketahuan dari keluhan
 * pengguna. Test ini menahannya di tingkat berkas, tanpa perlu basis data.
 */
class AdminMenuContractTest extends TestCase
{
    private function sidebar(): string
    {
        return file_get_contents(__DIR__.'/../../resources/views/admin/partials/sidebar.blade.php');
    }

    private function routes(): string
    {
        return file_get_contents(__DIR__.'/../../routes/admin.php');
    }

    /** Komentar Blade dibuang: catatan yang MENJELASKAN @can bukan pemakaian. */
    private function sidebarBersih(): string
    {
        return preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $this->sidebar());
    }

    public function test_setiap_permission_punya_butir_menu(): void
    {
        $sidebar = $this->sidebarBersih();

        foreach (RolesAndPermissionsSeeder::PERMISSIONS as $permission) {
            $this->assertStringContainsString(
                "'{$permission}'",
                $sidebar,
                "Permission '{$permission}' tidak dipakai di menu mana pun — "
                .'izinnya diberikan tetapi tidak membuka apa pun.',
            );
        }
    }

    public function test_induk_dropdown_verifikasi_memakai_canany(): void
    {
        // Dengan @can, menu "Verifikasi" hilang bagi admin yang hanya punya
        // salah satu dari dua izinnya.
        $this->assertMatchesRegularExpression(
            "/@canany\(\['verify-users', 'verify-stores'\]\)/",
            $this->sidebarBersih(),
        );
    }

    public function test_menu_tidak_memakai_url_mentah(): void
    {
        // URL mentah menjadi tautan mati begitu prefix route berubah, dan
        // tidak ada yang memberi tahu.
        $this->assertDoesNotMatchRegularExpression(
            '/href="\/admin\//',
            $this->sidebarBersih(),
            'Tautan menu harus memakai route(), bukan URL yang ditulis mentah.',
        );
    }

    public function test_endpoint_data_datatables_dijaga_permission(): void
    {
        $routes = $this->routes();

        // Tanpa penjagaan, admin tanpa izin tetap bisa memanggil JSON-nya
        // langsung dan menarik seluruh tabel meski menunya tersembunyi.
        foreach ([
            'users/data'    => 'manage-users',
            'stores/data'   => 'manage-stores',
            'disputes/data' => 'manage-disputes',
            'listings/data' => 'manage-listings',
            'orders/data'   => 'manage-orders',
            'requests/data' => 'manage-requests',
            'offers/data'   => 'manage-offers',
            'reviews/data'  => 'manage-reviews',
        ] as $uri => $permission) {
            $this->assertMatchesRegularExpression(
                '/'.preg_quote($uri, '/').'.*?permission:'.preg_quote($permission, '/').'/s',
                $routes,
                "Endpoint '{$uri}' tidak dijaga permission:{$permission}.",
            );
        }
    }

    public function test_halaman_akun_sendiri_tanpa_permission(): void
    {
        $routes = $this->routes();

        // Admin tanpa `manage-users` sekalipun harus tetap bisa mengganti
        // kata sandinya sendiri. Kalau halaman ini menuntut permission, akun
        // dengan izin minimal terkunci pada sandi default selamanya.
        foreach (["name('profile.edit')", "name('password.edit')"] as $penanda) {
            $this->assertStringContainsString($penanda, $routes);
        }

        // Blok akun sendiri harus berada SEBELUM grup permission mana pun.
        $posisiProfil = strpos($routes, "name('profile.edit')");
        $posisiIzin   = strpos($routes, "middleware('permission:");

        $this->assertNotFalse($posisiProfil);
        $this->assertLessThan(
            $posisiIzin,
            $posisiProfil,
            'Route profil berada di dalam grup permission — admin berizin minimal akan terkunci.',
        );
    }

    public function test_verifikasi_dipisah_dua_halaman(): void
    {
        $routes = $this->routes();

        // `verify-users` dan `verify-stores` adalah dua permission berbeda
        // (§6.2). Satu halaman gabungan memaksa admin yang hanya punya salah
        // satunya melihat data yang bukan haknya.
        $this->assertStringContainsString("name('verifications.users')", $routes);
        $this->assertStringContainsString("name('verifications.stores')", $routes);
    }

    public function test_route_admin_menuntut_peran(): void
    {
        $this->assertStringContainsString(
            "Route::middleware(['auth', 'role:admin|super-admin'])",
            $this->routes(),
            'Grup admin harus menuntut peran; permission saja tidak cukup karena '
            .'pengguna biasa tidak punya baris permission sama sekali.',
        );
    }

    public function test_dasbor_tidak_dibungkus_permission(): void
    {
        // Setiap admin yang lolos role harus melihat dasbor; isinya sendiri
        // yang disaring per izin, bukan pintunya.
        $this->assertMatchesRegularExpression(
            "/Route::get\('\/', \[DashboardController::class, 'index'\]\)->name\('dashboard'\);/",
            $this->routes(),
        );
    }
}
