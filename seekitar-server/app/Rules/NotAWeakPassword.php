<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Menolak kata sandi yang jelas lemah — TANPA memanggil layanan luar.
 *
 * KENAPA TIDAK MEMAKAI Password::uncompromised()
 * ----------------------------------------------
 * Aturan bawaan Laravel memanggil api.pwnedpasswords.com lewat HTTP. Sifat
 * kegagalannya sudah diuji langsung di sandbox ini, dan hasilnya:
 *
 *   $verifier->verify(['value' => 'password', 'threshold' => 0]) === true
 *
 * Artinya ketika API tidak terjangkau — jaringan diblokir, DNS gagal, atau
 * layanan sedang mati — `NotPwnedVerifier::search()` menangkap exception,
 * mengembalikan badan respons kosong, dan aturan itu MELULUSKAN sandi apa pun.
 * Ia gagal-terbuka (fail open). Pemeriksaan keamanan yang diam-diam berhenti
 * bekerja lebih buruk daripada tidak ada, karena tidak ada yang tahu.
 *
 * Tambahan lain: panggilan itu berjalan sinkron di dalam siklus request,
 * dengan timeout bawaan 30 detik. Satu API yang lambat membuat form ganti
 * sandi tampak menggantung.
 *
 * Daftar di bawah bersifat lokal, deterministik, dan bisa diuji tanpa
 * jaringan. Ia tidak selengkap HIBP, tetapi menutup kasus yang benar-benar
 * terjadi: sandi bawaan seeder dan variasi kata umum Indonesia.
 */
class NotAWeakPassword implements ValidationRule
{
    /**
     * Sandi yang dilarang mentah-mentah.
     *
     * `password` ada di urutan pertama karena itulah nilai default di
     * `.env.example` dan `AdminUserSeeder` — sandi yang paling mungkin
     * terbawa ke produksi.
     *
     * @var list<string>
     */
    private const DILARANG = [
        'password', 'password123', 'passw0rd', 'kata sandi', 'katasandi',
        'admin', 'admin123', 'administrator', 'superadmin', 'root', 'toor',
        'qwerty', 'qwerty123', 'asdfgh', 'zxcvbn', '1q2w3e4r',
        '12345678', '123456789', '1234567890', '11111111', '00000000',
        'seekitar', 'seekitar123', 'seekitaradmin',
        'rahasia', 'rahasia123', 'indonesia', 'pasuruan', 'surabaya',
        'letmein', 'welcome', 'iloveyou', 'monkey', 'dragon', 'sunshine',
        'abc12345', 'test1234', 'coba1234', 'bismillah',
    ];

    /**
     * Pola struktural yang selalu lemah, sepanjang apa pun sandinya.
     *
     * @var array<string, string>
     */
    private const POLA = [
        // Satu karakter diulang: "aaaaaaaaaaaa" lolos panjang 12 & huruf.
        '/^(.)\1+$/u' => 'Kata sandi tidak boleh berupa satu karakter yang diulang.',
        // Hanya angka: "192837465012" lolos panjang tapi ruang tebaknya kecil.
        '/^\d+$/' => 'Kata sandi tidak boleh hanya terdiri dari angka.',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;   // ditangani aturan `required`/`string`
        }

        // Perbandingan case-insensitive: "Password" sama lemahnya dengan
        // "password", dan menaikkan satu huruf tidak menambah keamanan.
        $normal = mb_strtolower(trim($value));

        if (in_array($normal, self::DILARANG, true)) {
            $fail('Kata sandi ini terlalu umum dan mudah ditebak. Pilih yang lain.');

            return;
        }

        // Angka di belakang tidak menyelamatkan kata yang sudah ada di daftar:
        // "seekitar2026" tetap "seekitar".
        $tanpaAngkaAkhir = rtrim($normal, '0123456789!@#$');
        if ($tanpaAngkaAkhir !== '' && in_array($tanpaAngkaAkhir, self::DILARANG, true)) {
            $fail('Kata sandi ini hanya variasi dari kata yang mudah ditebak. Pilih yang lain.');

            return;
        }

        foreach (self::POLA as $pola => $pesan) {
            if (preg_match($pola, $value) === 1) {
                $fail($pesan);

                return;
            }
        }

        // Deret berurutan naik/turun sepanjang seluruh sandi.
        if ($this->berurutan($normal)) {
            $fail('Kata sandi tidak boleh berupa deretan karakter berurutan.');
        }
    }

    /** "abcdefghijkl" / "987654321012" — pola yang dicoba pertama kali penyerang. */
    private function berurutan(string $value): bool
    {
        $len = mb_strlen($value);

        if ($len < 4) {
            return false;
        }

        $naik = true;
        $turun = true;

        for ($i = 1; $i < $len; $i++) {
            $selisih = mb_ord(mb_substr($value, $i, 1)) - mb_ord(mb_substr($value, $i - 1, 1));

            if ($selisih !== 1) {
                $naik = false;
            }
            if ($selisih !== -1) {
                $turun = false;
            }
            if (! $naik && ! $turun) {
                return false;
            }
        }

        return $naik || $turun;
    }

    /** Dipakai test agar daftarnya tidak perlu disalin ulang. */
    public static function daftarDilarang(): array
    {
        return self::DILARANG;
    }
}
