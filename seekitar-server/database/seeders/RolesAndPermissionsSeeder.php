<?php

namespace Database\Seeders;

use App\Enums\VerificationLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role, permission, dan akun super-admin pertama.
 *
 * Harus jalan PALING AWAL (lihat `DatabaseSeeder`) karena seeder ini membuat
 * pengguna yang langsung diberi role.
 *
 * IDEMPOTEN: `firstOrCreate` di semua tempat. Contoh di
 * `Server_Implementation_Guide.md` §19.2 memakai `Permission::create()`, yang
 * akan melempar `UniqueConstraintViolationException` pada deploy kedua —
 * bertentangan dengan syarat idempoten di dokumen yang sama.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** 12 permission, persis seperti Server_Implementation_Guide.md §6.2. */
    public const PERMISSIONS = [
        'manage-users',
        'verify-users',
        'manage-stores',
        'verify-stores',
        'manage-categories',
        'manage-listings',
        'manage-requests',
        'manage-offers',
        'manage-orders',
        'manage-disputes',
        'manage-reviews',
        'manage-settings',
    ];

    /**
     * Permission yang TIDAK diberikan ke role `admin`.
     *
     * `manage-users` ditahan agar admin biasa tidak bisa menghapus atau
     * mengubah sesama admin; `manage-settings` karena halaman pengaturan
     * dibatasi super-admin (`API_DOCUMENTATION.md` §10.5).
     * Keduanya tetap bisa memverifikasi pengguna lewat `verify-users`.
     */
    private const ADMIN_EXCLUDED = ['manage-users', 'manage-settings'];

    private const GUARD = 'web';

    public function run(): void
    {
        // Tanpa ini, permission yang baru dibuat tidak terlihat oleh
        // pengecekan role di proses yang sama (cache Spatie masih lama).
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () {
            /** @var array<string, Permission> $permissions */
            $permissions = [];
            foreach (self::PERMISSIONS as $name) {
                $permissions[$name] = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => self::GUARD]
                );
            }

            $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => self::GUARD]);
            $superAdmin->syncPermissions(array_values($permissions));

            $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => self::GUARD]);
            // Objek Permission dioper langsung, bukan namanya. Dengan string,
            // Spatie memanggil findByName() yang membaca cache permission —
            // dan cache itu bisa belum memuat baris yang baru saja dibuat
            // pada transaksi yang sama.
            $admin->syncPermissions(array_values(array_diff_key(
                $permissions,
                array_flip(self::ADMIN_EXCLUDED),
            )));

            // Role default pengguna biasa: sengaja TANPA permission apa pun.
            // Aksesnya diatur Policy berbasis kepemilikan, bukan permission.
            Role::firstOrCreate(['name' => 'user', 'guard_name' => self::GUARD]);

            $this->createSuperAdminUser()->assignRole($superAdmin);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Akun super-admin pertama.
     *
     * Nomornya diambil dari env agar tiap lingkungan punya pemilik yang
     * berbeda — menanam satu nomor di kode berarti nomor yang sama menjadi
     * super-admin di produksi.
     */
    private function createSuperAdminUser(): User
    {
        $phone = (string) config('seekitar.super_admin_phone', '6280000000000');

        $user = User::withTrashed()->firstOrCreate(
            ['phone' => $phone],
            [
                'name'               => 'Super Admin',
                'verification_level' => VerificationLevel::Pro,
            ],
        );

        // Akun super-admin tidak boleh tertinggal dalam keadaan terhapus.
        if ($user->trashed()) {
            $user->restore();
        }

        return $user;
    }
}
