<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CustomerRequest;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingSeeder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Kontrak seeder yang bisa diperiksa TANPA basis data.
 *
 * Test ini sengaja tidak menyentuh MySQL: isinya janji-janji yang tertulis di
 * dokumen (jumlah kategori, daftar permission, kunci settings) dan kemampuan
 * model yang dipakai seeder. Perilaku constraint tetap diuji terpisah pada
 * MySQL sungguhan lewat ModelSchemaTest.
 */
class SeederContractTest extends TestCase
{
    /** @return array<int, array{0:string,1:string,2:string,3:array}> */
    private function taxonomy(): array
    {
        return (new ReflectionClass(CategorySeeder::class))->getConstant('TAXONOMY');
    }

    public function test_kategori_berjumlah_24(): void
    {
        // Server_Implementation_Guide.md §19.2 menjanjikan 24 kategori.
        $this->assertSame(24, CategorySeeder::expectedCount());
    }

    public function test_kategori_hanya_dua_level(): void
    {
        // PRD §5.1: "Kategori wajib (sampai level 2, mis. Elektronik > AC)".
        foreach ($this->taxonomy() as [$slug, $name, $icon, $children]) {
            foreach ($children as $child) {
                $this->assertCount(
                    3,
                    $child,
                    "Subkategori '{$child[0]}' punya anak — taksonomi dibatasi dua level."
                );
            }
        }
    }

    public function test_slug_kategori_unik_dan_muat_di_kolom(): void
    {
        $slugs = [];
        foreach ($this->taxonomy() as [$slug, $name, $icon, $children]) {
            $slugs[] = $slug;
            foreach ($children as [$childSlug]) {
                $slugs[] = $childSlug;
            }
        }

        // UNIQUE(slug) di skema akan menolak duplikat saat deploy.
        $this->assertSame($slugs, array_values(array_unique($slugs)), 'ada slug ganda');

        foreach ($slugs as $slug) {
            // categories.slug VARCHAR(50)
            $this->assertLessThanOrEqual(50, strlen($slug), "slug '{$slug}' melebihi VARCHAR(50)");
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        }
    }

    public function test_dua_belas_permission_sesuai_dokumen(): void
    {
        // Server_Implementation_Guide.md §6.2.
        $this->assertSame([
            'manage-users', 'verify-users', 'manage-stores', 'verify-stores',
            'manage-categories', 'manage-listings', 'manage-requests', 'manage-offers',
            'manage-orders', 'manage-disputes', 'manage-reviews', 'manage-settings',
        ], RolesAndPermissionsSeeder::PERMISSIONS);
    }

    public function test_delapan_kunci_settings_dengan_default_yang_benar(): void
    {
        $defaults = SettingSeeder::DEFAULTS;

        $this->assertCount(8, $defaults);

        // Angka di §9.12 — radius toko (5) dan radius permintaan (15) mudah
        // tertukar, jadi keduanya dikunci di sini.
        $this->assertSame(25, $defaults['max_search_radius_km'][0]);
        $this->assertSame(15, $defaults['default_request_radius_km'][0]);
        $this->assertSame(24, $defaults['request_expiry_hours'][0]);
        $this->assertSame(48, $defaults['offer_expiry_hours'][0]);
        $this->assertSame(7, $defaults['review_window_days'][0]);
    }

    /**
     * Semua model yang menyimpan koordinat WAJIB punya setLocation().
     *
     * `users.location` sempat ada di skema tanpa trait HasLocation di
     * modelnya — akibatnya lokasi pengguna mustahil ditulis, dan
     * DummyDataSeeder gagal dengan "Call to undefined method setLocation()".
     */
    public function test_model_berlokasi_punya_setlocation(): void
    {
        foreach ([User::class, Store::class, CustomerRequest::class] as $model) {
            $this->assertTrue(
                method_exists($model, 'setLocation'),
                "{$model} menyimpan kolom POINT tetapi tidak memakai trait HasLocation."
            );
        }
    }

    /** Kategori tidak memakai HasLocation — memastikan test di atas bermakna. */
    public function test_model_tanpa_lokasi_tidak_punya_setlocation(): void
    {
        $this->assertFalse(method_exists(Category::class, 'setLocation'));
    }

    /**
     * User butuh HasRoles, kalau tidak `assignRole()` di seeder gagal.
     */
    public function test_user_memakai_trait_spatie(): void
    {
        $this->assertTrue(method_exists(User::class, 'assignRole'));
        $this->assertTrue(method_exists(User::class, 'hasRole'));
    }
}
