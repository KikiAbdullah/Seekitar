<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Akun contoh untuk setiap peran — agar panel admin bisa diuji dari
 * sudut pandang tiap tingkat akses.
 *
 * ⚠️ HANYA local & testing. Seeder ini membuat akun dengan kata sandi yang
 * tertulis di repositori; menjalankannya di produksi sama dengan membuka
 * pintu belakang. Penjagaannya ada di dua lapis: di sini dan di
 * `DatabaseSeeder`.
 *
 * KENAPA TERPISAH DARI RolesAndPermissionsSeeder
 * ----------------------------------------------
 * Seeder itu WAJIB jalan di produksi karena membuat role, permission, dan
 * satu akun pemilik. Kalau akun contoh ikut di dalamnya, tidak ada cara
 * menjalankan yang satu tanpa yang lain.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Akun per peran.
     *
     * `user` sengaja ikut dibuat — bukan untuk masuk panel, melainkan untuk
     * MEMBUKTIKAN bahwa ia ditolak. Tanpa akun kontrol seperti ini, tidak
     * ada cara menguji bahwa pembatasan peran benar-benar bekerja.
     *
     * @var list<array{role: string, name: string, phone: string, email: string, ktp: bool, panel: bool}>
     */
    private const ACCOUNTS = [
        [
            'role'  => 'super-admin',
            'name'  => 'Sinta Wijaya',
            'phone' => '6280000000001',
            'email' => 'superadmin@seekitar.test',
            // Staf panel dianggap identitasnya terverifikasi: stempel KTP
            // diisi supaya halaman pengguna menampilkannya utuh. "Pro"
            // tidak ditulis manual — turunan dari toko tervalidasi.
            'ktp'   => true,
            'panel' => true,
        ],
        [
            'role'  => 'admin',
            'name'  => 'Andi Prasetyo',
            'phone' => '6280000000002',
            'email' => 'admin.staf@seekitar.test',
            'ktp'   => true,
            'panel' => true,
        ],
        [
            // Peran default pengguna aplikasi. TIDAK bisa masuk panel:
            // LoginController memeriksa canAccessAdminPanel() setelah
            // kredensial benar, lalu membuang sesinya.
            'role'  => 'user',
            'name'  => 'Budi Santoso',
            'phone' => '6280000000003',
            'email' => 'warga@seekitar.test',
            'ktp'   => false,
            'panel' => false,
        ],
    ];

    /** Sengaja seragam & sederhana; hanya berlaku di local/testing. */
    private const PASSWORD = 'password';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException(
                'AdminUserSeeder membuat akun dengan kata sandi yang tertulis di '
                .'repositori dan HANYA boleh jalan di local/testing. '
                .'Environment saat ini: '.app()->environment()
            );
        }

        // Role dibuat RolesAndPermissionsSeeder. Kalau belum ada, assignRole()
        // akan melempar RoleDoesNotExist yang tidak menjelaskan urutannya.
        $this->assertRolesExist();

        DB::transaction(function (): void {
            foreach (self::ACCOUNTS as $account) {
                $this->createAccount($account);
            }
        });

        // Tanpa ini, pemeriksaan peran pada proses yang sama masih memakai
        // cache lama dan akun baru tampak tidak punya peran apa pun.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->printCredentials();
    }

    /** @param array{role: string, name: string, phone: string, email: string, ktp: bool, panel: bool} $account */
    private function createAccount(array $account): void
    {
        // Kunci pencarian `phone`, bukan `email`: nomor telepon adalah
        // identitas akun di Seekitar (DATABASE.md §4.1).
        $user = User::withTrashed()->firstOrCreate(
            ['phone' => $account['phone']],
            [
                'name'  => $account['name'],
                'email' => $account['email'],
            ] + ($account['ktp'] ? [
                // Stempel + status == "terverifikasi": tidak ada kolom lain
                // untuk ditulis; lencana & izin buka toko turunan dari ini.
                'status'      => \App\Enums\UserStatus::Terverifikasi,
                'verified_at' => now(),
            ] : []),
        );

        // getAttributes(), bukan $user->password: Model::shouldBeStrict()
        // aktif di luar produksi dan melempar MissingAttributeException bila
        // kolomnya tidak ikut ter-SELECT.
        $existingPassword = $user->getAttributes()['password'] ?? null;

        // Sandi hanya disetel saat akun baru — konsisten dengan
        // RolesAndPermissionsSeeder, supaya sandi yang sudah diganti saat
        // pengujian manual tidak tertimpa tiap `db:seed`.
        if ($user->wasRecentlyCreated || $existingPassword === null) {
            $user->password = self::PASSWORD;
        }

        $user->email ??= $account['email'];

        if ($user->trashed()) {
            $user->restore();
        }

        if ($user->isDirty()) {
            $user->save();
        }

        // syncRoles, bukan assignRole: menjalankan ulang seeder tidak boleh
        // menumpuk peran, dan peran yang dihapus dari daftar harus benar-benar
        // tercabut.
        $user->syncRoles([$account['role']]);
    }

    private function assertRolesExist(): void
    {
        $roleClass = app(PermissionRegistrar::class)->getRoleClass();
        $expected  = array_column(self::ACCOUNTS, 'role');
        $existing  = $roleClass::whereIn('name', $expected)->pluck('name')->all();
        $missing   = array_diff($expected, $existing);

        if ($missing !== []) {
            throw new RuntimeException(
                'Role belum ada: '.implode(', ', $missing).'. '
                .'Jalankan RolesAndPermissionsSeeder lebih dulu — lihat urutan di DatabaseSeeder.'
            );
        }
    }

    /** Kredensial dicetak supaya tidak perlu dicari-cari di kode. */
    private function printCredentials(): void
    {
        $this->command?->newLine();
        $this->command?->info('Akun contoh panel admin (local/testing saja):');

        $rows = array_map(fn (array $a): array => [
            $a['role'],
            $a['email'],
            self::PASSWORD,
            $a['panel'] ? 'ya' : 'DITOLAK (uji kontrol)',
        ], self::ACCOUNTS);

        $this->command?->table(['Peran', 'Email', 'Kata Sandi', 'Akses panel'], $rows);
    }

    /** Dipakai test untuk memverifikasi tanpa menyalin daftarnya. */
    public static function accounts(): array
    {
        return self::ACCOUNTS;
    }

    public static function password(): string
    {
        return self::PASSWORD;
    }
}
