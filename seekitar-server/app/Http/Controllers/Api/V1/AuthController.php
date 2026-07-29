<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RequestOtpRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\WhatsAppGateway;
use App\Services\OtpService;
use App\Exceptions\OtpDeliveryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Autentikasi berbasis OTP WhatsApp (API §2).
 *
 * Seekitar tidak memakai kata sandi sama sekali — OTP adalah satu-satunya
 * faktor, sehingga penanganannya di sini bersifat kritis.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /** Umur token: 30 hari, dalam detik (API §2.2). */
    private const TOKEN_TTL_SECONDS = 2592000;

    public function __construct(
        private readonly OtpService $otp,
        private readonly WhatsAppGateway $whatsapp,
    ) {}

    /** POST /auth/request-otp — publik. */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $code  = $this->otp->generate($phone);

        try {
            $this->whatsapp->sendOtp($phone, $code);
        } catch (OtpDeliveryException $e) {
            // Kode dibuang supaya pengguna bisa meminta ulang tanpa menunggu
            // TTL habis — kegagalan ini bukan salah pengguna.
            $this->otp->forget($phone);

            report($e);

            return $this->fail('Gagal mengirim OTP. Coba lagi sesaat lagi.', 503);
        }

        return $this->ok(null, 'OTP telah dikirim ke WhatsApp Anda.');
    }

    /**
     * POST /auth/verify-otp — publik.
     *
     * Akun dibuat otomatis pada verifikasi pertama: tidak ada layar
     * "daftar" terpisah di Seekitar (PRD §5.3.1).
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->phone();

        if (! $this->otp->verify($phone, $request->otp())) {
            return $this->fail('Kode OTP salah atau sudah kedaluwarsa.', 422, [
                'otp' => ['Kode OTP salah atau sudah kedaluwarsa.'],
            ]);
        }

        /** @var array{0: User, 1: bool} $result */
        $result = DB::transaction(function () use ($phone): array {
            /*
             * withTrashed() WAJIB di sini: kolom phone unik di basis data,
             * dan akun yang di-soft-delete tetap memegang nomornya. Tanpa
             * ini, User::create() untuk nomor tersebut meledak dengan
             * IntegrityConstraintViolation (HTTP 500) — padahal jawaban
             * yang benar adalah memulihkan akunnya.
             */
            $user = User::withTrashed()->where('phone', $phone)->first();

            if ($user) {
                // Akun yang kembali lewat OTP dipulihkan; kalau ia memang
                // diblokir, gerbang is_blocked di bawah tetap menahannya.
                if ($user->trashed()) {
                    $user->restore();
                }

                return [$user, false];
            }

            return [User::create(['phone' => $phone]), true];
        });

        [$user, $isNew] = $result;

        /*
         * OTP yang cocok ADALAH bukti kepemilikan nomor — stempel tahap 1
         * ditulis sistem di sini (verified1_by sengaja NULL). Meminta admin
         * "memverifikasi" nomor HP secara visual tidak menambah sinyal apa
         * pun; antrian pengguna jadi hanya meninjau yang benar-benar butuh
         * mata manusia: berkas KTP.
         * Tulis-sekali: login OTP berikutnya tidak mengubah apa pun.
         */
        if ($user->verified1_at === null) {
            $user->verified1_at = now();
            $user->save();
        }

        if ($user->is_blocked) {
            return $this->fail('Akun Anda diblokir. Hubungi dukungan Seekitar.', 423);
        }

        $token = $user->createToken('mobile', ['*'], now()->addSeconds(self::TOKEN_TTL_SECONDS));

        return $this->ok([
            'token'       => $token->plainTextToken,
            'token_type'  => 'Bearer',
            'expires_in'  => self::TOKEN_TTL_SECONDS,
            'is_new_user' => $isNew,
            'user'        => new UserResource($user),
        ]);
    }

    /** GET /auth/me */
    public function me(Request $request): JsonResponse
    {
        return $this->ok(['user' => new UserResource($request->user())]);
    }

    /**
     * POST /auth/phone/request-otp — kirim OTP ke NOMOR BARU (ganti HP).
     *
     * Nomor adalah kredensial masuk satu-satunya, jadi pergantian TIDAK
     * boleh berjalan hanya karena sesi kebetulan aktif: pemilik baru harus
     * membuktikan memegang nomor tujuan lewat OTP (aturan 5 — perubahan
     * dari sisi pengguna wajib verifikasi ulang).
     */
    public function requestPhoneChangeOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->phone();

        // Termasuk akun ter-soft-delete: kolom phone unik tanpa memandang
        // deleted_at — memeriksa yang hidup saja menyiapkan 500 saat
        // pergantian benar-benar disimpan.
        if (User::withTrashed()->where('phone', $phone)->exists()) {
            return $this->fail('Nomor ini sudah dipakai akun lain.', 422, [
                'phone' => ['Nomor ini sudah dipakai akun lain.'],
            ]);
        }

        $code = $this->otp->generate($phone);

        try {
            $this->whatsapp->sendOtp($phone, $code);
        } catch (OtpDeliveryException $e) {
            $this->otp->forget($phone);
            report($e);

            return $this->fail('Gagal mengirim OTP. Coba lagi sesaat lagi.', 503);
        }

        return $this->ok(null, 'OTP telah dikirim ke nomor baru Anda.');
    }

    /**
     * POST /auth/phone/verify-otp — nomor DIGANTI hanya setelah OTP-nya cocok.
     *
     * Stempel tahap 1 ditulis ulang oleh sistem (& adminnya dikosongkan):
     * stempel lama dibuat atas bukti nomor LAMA, dan membiarkannya menggantung
     * akan membuat jejak audit berbohong tentang nomor yang sekarang.
     */
    public function verifyPhoneChangeOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->phone();

        if (! $this->otp->verify($phone, $request->otp())) {
            return $this->fail('Kode OTP salah atau sudah kedaluwarsa.', 422, [
                'otp' => ['Kode OTP salah atau sudah kedaluwarsa.'],
            ]);
        }

        $tersedia = ! User::withTrashed()
            ->where('phone', $phone)
            ->whereKeyNot($request->user()->id)
            ->exists();

        if (! $tersedia) {
            // Lompat validasi: nomornya didaftarkan akun lain DI SELA
            // menunggu OTP — OTP yang cocok tidak boleh merampas nomor orang.
            return $this->fail('Nomor ini baru saja dipakai akun lain.', 422);
        }

        $user = $request->user();
        $user->phone         = $phone;
        $user->verified1_at  = now();    // OTP barusan = bukti nomor terkini
        $user->verified1_by  = null;     // NULL berarti diverifikasi sistem
        $user->save();

        return $this->ok(
            ['user' => new UserResource($user->fresh())],
            'Nomor WhatsApp berhasil diganti.',
        );
    }

    /**
     * PATCH /auth/profile
     *
     * ⚠️ Endpoint ini TIDAK boleh memakai middleware `profile.complete` —
     * pengguna baru justru datang ke sini untuk melengkapi profilnya.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'address']));

        if ($request->hasFile('avatar')) {
            // Avatar bersifat publik; KTP tidak (lihat uploadKtp).
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = Storage::disk('public')->url($path);
        }

        if ($request->hasCoordinates()) {
            $user->setLocation(
                (float) $request->validated('latitude'),
                (float) $request->validated('longitude'),
            );
        }

        $user->save();

        return $this->ok(['user' => new UserResource($user->fresh())]);
    }

    /** POST /auth/logout — mencabut token yang sedang dipakai saja. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->ok(null, 'Berhasil keluar.');
    }
}
