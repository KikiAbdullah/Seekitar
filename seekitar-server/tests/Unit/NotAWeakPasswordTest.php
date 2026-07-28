<?php

namespace Tests\Unit;

use App\Rules\NotAWeakPassword;
use PHPUnit\Framework\TestCase;

/**
 * Aturan kata sandi lemah.
 *
 * Aturan ini menggantikan `Password::uncompromised()` bawaan Laravel, yang
 * memanggil api.pwnedpasswords.com dan GAGAL-TERBUKA bila API tak terjangkau
 * — perilaku itu diverifikasi langsung, bukan diasumsikan. Karena penggantinya
 * adalah satu-satunya penjaga yang tersisa, ia wajib punya test sendiri.
 */
class NotAWeakPasswordTest extends TestCase
{
    /** @return list<string> pesan kegagalan */
    private function jalankan(string $password): array
    {
        $pesan = [];

        (new NotAWeakPassword())->validate(
            'password',
            $password,
            function (string $m) use (&$pesan): void { $pesan[] = $m; },
        );

        return $pesan;
    }

    private function assertDitolak(string $password): void
    {
        $this->assertNotEmpty(
            $this->jalankan($password),
            "Kata sandi '{$password}' seharusnya ditolak.",
        );
    }

    private function assertDiterima(string $password): void
    {
        $this->assertSame(
            [],
            $this->jalankan($password),
            "Kata sandi '{$password}' seharusnya diterima.",
        );
    }

    public function test_menolak_sandi_default_seeder(): void
    {
        // Inilah nilai di .env.example dan AdminUserSeeder — sandi yang paling
        // mungkin terbawa ke produksi.
        $this->assertDitolak('password');
        $this->assertDitolak('admin');
        $this->assertDitolak('seekitar');
    }

    public function test_menolak_tanpa_peduli_huruf_besar_kecil(): void
    {
        // Menaikkan satu huruf tidak menambah keamanan sama sekali.
        $this->assertDitolak('Password');
        $this->assertDitolak('PASSWORD');
        $this->assertDitolak('AdMiN');
    }

    public function test_menolak_variasi_dengan_angka_di_belakang(): void
    {
        // Pola yang paling sering dipakai untuk "memenuhi syarat angka".
        $this->assertDitolak('password123');
        $this->assertDitolak('seekitar2026');
        $this->assertDitolak('admin2026!');
    }

    public function test_menolak_satu_karakter_diulang(): void
    {
        // Lolos syarat panjang 12, tetapi ruang tebaknya praktis nol.
        $this->assertDitolak('aaaaaaaaaaaa');
        $this->assertDitolak('111111111111');
    }

    public function test_menolak_hanya_angka(): void
    {
        $this->assertDitolak('192837465012');
    }

    public function test_menolak_deret_berurutan(): void
    {
        $this->assertDitolak('abcdefghijkl');
        $this->assertDitolak('987654321');
    }

    public function test_menerima_sandi_yang_wajar(): void
    {
        $this->assertDiterima('Kabupaten2026Aman');
        $this->assertDiterima('BangilPasuruan99');
        // Mengandung kata terlarang sebagai BAGIAN, bukan keseluruhan —
        // aturannya sengaja tidak menolak substring, karena itu akan
        // membuang sandi panjang yang sebenarnya kuat.
        $this->assertDiterima('RumahAdminBesar77');
    }

    public function test_mengabaikan_nilai_kosong(): void
    {
        // Kekosongan adalah urusan aturan `required`; melaporkannya dua kali
        // menghasilkan dua pesan untuk satu masalah.
        $this->assertSame([], $this->jalankan(''));
    }

    public function test_daftar_terlarang_memuat_sandi_seeder(): void
    {
        $this->assertContains(
            'password',
            NotAWeakPassword::daftarDilarang(),
            'Sandi default seeder wajib ada di daftar; itu satu-satunya yang pasti bocor.',
        );
    }
}
