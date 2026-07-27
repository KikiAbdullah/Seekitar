<?php

namespace App\Services;

use App\Support\PhoneNumber;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Hash;

/**
 * Pembuatan & verifikasi kode OTP.
 *
 * Aturan dari `Server_Implementation_Guide.md` §15.2:
 *   - kode disimpan **di-hash**, tidak pernah plaintext
 *   - TTL 5 menit
 *   - maksimal 5 percobaan verifikasi
 *
 * KENAPA DI-HASH. Redis bukan brankas: dump memori, replika, atau `MONITOR`
 * bisa membocorkan isinya. OTP plaintext yang bocor setara dengan kata sandi
 * yang bocor, karena OTP adalah SATU-SATUNYA faktor autentikasi di Seekitar
 * (tidak ada kata sandi sama sekali — DATABASE.md §4.1).
 *
 * KENAPA MEMAKAI CACHE REPOSITORY, BUKAN FACADE Redis. `Redis::setex()`
 * mengunci implementasi ke satu driver dan membuat unit test mustahil tanpa
 * server Redis. Kontrak cache Laravel memberi API yang sama dan bisa
 * ditukar dengan array store saat testing.
 */
class OtpService
{
    /** TTL kode, dalam detik. */
    public const TTL_SECONDS = 300;

    /** Batas percobaan verifikasi sebelum kode dibuang. */
    public const MAX_ATTEMPTS = 5;

    private const CODE_LENGTH = 6;

    public function __construct(private readonly CacheRepository $cache) {}

    /**
     * Buat kode baru dan simpan hash-nya.
     *
     * Mengembalikan kode PLAINTEXT — satu-satunya kesempatan memilikinya,
     * karena yang tersimpan hanya hash. Nilai ini langsung diserahkan ke
     * gateway WhatsApp dan tidak boleh di-log.
     */
    public function generate(string $phone): string
    {
        $phone = $this->normalize($phone);
        $code  = $this->randomCode();

        $this->cache->put($this->codeKey($phone), Hash::make($code), self::TTL_SECONDS);
        $this->cache->put($this->attemptKey($phone), 0, self::TTL_SECONDS);

        return $code;
    }

    /**
     * Verifikasi kode. Sekali berhasil, kode langsung dibuang.
     *
     * Percobaan dihitung agar kode 6 digit tidak bisa ditebak paksa: tanpa
     * batas, seluruh 1.000.000 kemungkinan bisa dicoba dalam masa 5 menit.
     */
    public function verify(string $phone, string $code): bool
    {
        $phone = $this->normalize($phone);
        $hash  = $this->cache->get($this->codeKey($phone));

        if ($hash === null) {
            return false;   // tidak pernah dibuat, atau sudah kedaluwarsa
        }

        if ($this->attemptsLeft($phone) <= 0) {
            $this->forget($phone);

            return false;
        }

        if (! Hash::check($code, $hash)) {
            $this->cache->increment($this->attemptKey($phone));

            return false;
        }

        // Sukses: buang agar kode yang sama tidak bisa dipakai dua kali.
        $this->forget($phone);

        return true;
    }

    public function attemptsLeft(string $phone): int
    {
        $used = (int) $this->cache->get($this->attemptKey($this->normalize($phone)), 0);

        return max(0, self::MAX_ATTEMPTS - $used);
    }

    /** Ada kode aktif? Dipakai agar tidak mengirim ulang sebelum kedaluwarsa. */
    public function hasActiveCode(string $phone): bool
    {
        return $this->cache->get($this->codeKey($this->normalize($phone))) !== null;
    }

    public function forget(string $phone): void
    {
        $phone = $this->normalize($phone);

        $this->cache->forget($this->codeKey($phone));
        $this->cache->forget($this->attemptKey($phone));
    }

    /**
     * Kode acak kriptografis.
     *
     * `random_int()`, bukan `rand()`/`mt_rand()` — generator biasa bisa
     * diprediksi bila penyerang tahu seed-nya. `str_pad` menjaga panjang
     * tetap 6 digit, termasuk untuk angka seperti 000123.
     */
    private function randomCode(): string
    {
        return str_pad(
            (string) random_int(0, 10 ** self::CODE_LENGTH - 1),
            self::CODE_LENGTH,
            '0',
            STR_PAD_LEFT,
        );
    }

    /**
     * Kunci cache selalu memakai nomor yang sudah dinormalkan.
     *
     * Kalau tidak, OTP yang diminta untuk `08123...` tidak akan ditemukan
     * saat diverifikasi sebagai `628123...` — padahal itu nomor yang sama.
     */
    private function normalize(string $phone): string
    {
        return PhoneNumber::normalize($phone) ?? $phone;
    }

    private function codeKey(string $phone): string
    {
        return "otp:{$phone}";
    }

    private function attemptKey(string $phone): string
    {
        return "otp:{$phone}:attempts";
    }
}
