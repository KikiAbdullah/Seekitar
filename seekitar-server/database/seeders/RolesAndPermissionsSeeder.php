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
     * Nomor, email, dan kata sandi diambil dari env agar tiap lingkungan
     * punya pemilik berbeda — menanamnya di kode berarti kredensial contoh
     * yang sama menjadi super-admin di produksi.
     *
     * Email & kata sandi WAJIB ada: panel admin memakai login web, bukan OTP
     * (Server_Implementation_Guide §18A.5). Tanpa keduanya, akun ini terbuat
     * tetapi tidak akan pernah bisa masuk.
     */
    private function createSuperAdminUser(): User
    {
        $phone    = (string) config('seekitar.super_admin_phone');
        $email    = (string) config('seekitar.super_admin_email');
        $password = (string) config('seekitar.super_admin_password');

        $user = User::withTrashed()->firstOrCreate(
            ['phone' => $phone],
            [
                'name'               => 'Super Admin',
                'email'              => $email,
                'verification_level' => VerificationLevel::Pro,
            ],
        );

        // Kata sandi hanya disetel saat akun BARU dibuat. Menimpanya setiap
        // deploy akan mengembalikan sandi yang sudah diganti admin ke nilai
        // default — dan nilai default itu ada di berkas .env.example.
        if ($user->wasRecentlyCreated || $user->password === null) {
            $user->password = $password;   // cast 'hashed' meng-hash otomatis
        }

        $user->email ??= $email;

        // Akun super-admin tidak boleh tertinggal dalam keadaan terhapus.
        if ($user->trashed()) {
            $user->restore();   // restore() sudah menyimpan barisnya
        }

        // WAJIB: tanpa save(), email & kata sandi di atas hanya hidup di
        // memori dan akun yang baru dibuat tetap tidak bisa masuk.
        if ($user->isDirty()) {
            $user->save();
        }

        return $user;
    }
}
