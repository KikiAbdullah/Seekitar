<?php

namespace Tests\Unit;

use App\Enums\VerificationLevel;
use App\Support\PhoneNumber;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\TestCase;

/**
 * Kontrak akun contoh panel admin.
 *
 * Diperiksa tanpa basis data; perilaku login sesungguhnya diuji terpisah
 * sebagai Feature test terhadap MySQL.
 */
class AdminUserSeederTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(__DIR__.'/../../database/seeders/AdminUserSeeder.php');
    }

    public function test_menyediakan_akun_untuk_setiap_peran(): void
    {
        $roles = array_column(AdminUserSeeder::accounts(), 'role');

        // Ketiga peran yang dibuat RolesAndPermissionsSeeder harus terwakili.
        $this->assertEqualsCanonicalizing(['super-admin', 'admin', 'user'], $roles);
    }

    public function test_peran_yang_dipakai_benar_benar_dibuat_seeder_role(): void
    {
        $roleSeeder = file_get_contents(
            __DIR__.'/../../database/seeders/RolesAndPermissionsSeeder.php'
        );

        foreach (array_column(AdminUserSeeder::accounts(), 'role') as $role) {
            $this->assertStringContainsString(
                "'{$role}'",
                $roleSeeder,
                "Peran '{$role}' tidak pernah dibuat — assignRole() akan gagal.",
            );
        }
    }

    public function test_hanya_admin_dan_super_admin_yang_boleh_masuk_panel(): void
    {
        foreach (AdminUserSeeder::accounts() as $account) {
            $expected = in_array($account['role'], ['admin', 'super-admin'], true);

            $this->assertSame(
                $expected,
                $account['panel'],
                "Harapan akses panel untuk peran '{$account['role']}' tidak konsisten.",
            );
        }
    }

    /**
     * Akun berperan `user` sengaja ada sebagai KONTROL: tanpa akun yang
     * seharusnya ditolak, tidak ada cara membuktikan pembatasan peran bekerja.
     */
    public function test_menyertakan_akun_kontrol_yang_harus_ditolak(): void
    {
        $ditolak = array_filter(AdminUserSeeder::accounts(), fn ($a) => $a['panel'] === false);

        $this->assertNotEmpty($ditolak, 'Tidak ada akun kontrol untuk menguji penolakan.');
    }

    public function test_nomor_telepon_dan_email_unik(): void
    {
        $accounts = AdminUserSeeder::accounts();

        // users.phone & users.email keduanya UNIQUE — duplikat membuat
        // seeder gagal di tengah jalan.
        foreach (['phone', 'email'] as $field) {
            $values = array_column($accounts, $field);
            $this->assertSame(
                count($values),
                count(array_unique($values)),
                "Ada {$field} yang sama; UNIQUE constraint akan menolaknya.",
            );
        }
    }

    public function test_nomor_telepon_sudah_ternormalisasi(): void
    {
        foreach (AdminUserSeeder::accounts() as $account) {
            $phone = $account['phone'];

            // Seeder tidak melewati FormRequest, jadi nomornya harus sudah
            // dalam bentuk simpanan (62xxx) sejak awal.
            $this->assertSame(PhoneNumber::normalize($phone), $phone);
            $this->assertLessThanOrEqual(PhoneNumber::MAX_LENGTH, strlen($phone));
        }
    }

    public function test_tidak_memakai_nomor_super_admin_utama(): void
    {
        // Dibaca langsung dari berkas config: unit test murni tidak punya
        // container, jadi helper config() tidak tersedia di sini.
        $config = require __DIR__.'/../../config/seekitar.php';
        $utama  = $config['super_admin_phone'];

        // Bentrok dengan akun pemilik dari RolesAndPermissionsSeeder akan
        // menimpa perannya lewat syncRoles().
        foreach (AdminUserSeeder::accounts() as $account) {
            $this->assertNotSame($utama, $account['phone']);
        }
    }

    public function test_dibatasi_pada_local_dan_testing(): void
    {
        // Akun ini memakai kata sandi yang tertulis di repositori.
        $this->assertStringContainsString(
            "environment('local', 'testing')",
            $this->source(),
        );
        $this->assertStringContainsString('RuntimeException', $this->source());
    }

    public function test_memakai_syncroles_bukan_assignrole(): void
    {
        $src = $this->source();

        // assignRole() menumpuk peran tiap kali seeder dijalankan ulang.
        $this->assertStringContainsString('syncRoles(', $src);
        $this->assertStringNotContainsString('->assignRole(', $src);
    }

    public function test_membaca_password_secara_aman_dari_strict_mode(): void
    {
        // Model::shouldBeStrict() aktif di luar produksi; membaca properti
        // yang tidak ter-SELECT melempar MissingAttributeException.
        $this->assertStringContainsString(
            "getAttributes()['password']",
            $this->source(),
        );
    }

    public function test_verifikasi_level_admin_paling_tinggi(): void
    {
        foreach (AdminUserSeeder::accounts() as $account) {
            if (in_array($account['role'], ['admin', 'super-admin'], true)) {
                $this->assertSame(VerificationLevel::Pro, $account['level']);
            }
        }
    }

    public function test_dipanggil_databaseseeder_setelah_role(): void
    {
        $db = file_get_contents(__DIR__.'/../../database/seeders/DatabaseSeeder.php');

        $posRole  = strpos($db, RolesAndPermissionsSeeder::class === '' ? '' : 'RolesAndPermissionsSeeder');
        $posAdmin = strpos($db, 'AdminUserSeeder');

        $this->assertNotFalse($posAdmin, 'AdminUserSeeder tidak dipanggil DatabaseSeeder.');
        $this->assertLessThan(
            $posAdmin,
            $posRole,
            'Role harus dibuat sebelum akun yang memakainya.',
        );
    }
}
