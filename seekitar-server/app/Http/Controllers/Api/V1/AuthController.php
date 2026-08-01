<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RequestOtpRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\UserExportResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\WhatsAppGateway;
use App\Services\OtpService;
use App\Services\PrivacyService;
use App\Exceptions\OtpDeliveryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Autentikasi berbasis OTP WhatsApp + JWT (API §2).
 *
 * Seekitar tidak memakai kata sandi sama sekali — OTP adalah satu-satunya
 * faktor. Setelah OTP cocok, JWT diterbitkan sebagai bearer token untuk
 * seluruh request API selanjutnya.
 *
 * Token JWT tidak disimpan di database (stateless): verifikasi dilakukan
 * semata-mata dari signature + expiry payload. Refresh mencabut token lama
 * lewat blacklist dan menerbitkan yang baru.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /** Umur token JWT: 30 hari, dalam menit (config/jwt.php). */
    private const TOKEN_TTL_MINUTES = 43200;

    public function __construct(
        private readonly OtpService $otp,
        private readonly WhatsAppGateway $whatsapp,
        private readonly PrivacyService $privacy,
    ) {}

    /** POST /auth/request-otp — publik. */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $code  = $this->otp->generate($phone);

        try {
            $this->whatsapp->sendOtp($phone, $code);
        } catch (OtpDeliveryException $e) {
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
     *
     * Setelah OTP cocok, JWT diterbitkan lewat `auth('api')->login()` —
     * token tidak disimpan di DB, signature-nya diverifikasi tiap request.
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
            $user = User::withTrashed()->where('phone', $phone)->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }

                return [$user, false];
            }

            return [User::create(['phone' => $phone]), true];
        });

        [$user, $isNew] = $result;

        if ($user->isBlocked()) {
            return $this->fail('Akun Anda diblokir. Hubungi dukungan Seekitar.', 423);
        }

        // JWT stateless — tidak ada baris di personal_access_tokens.
        $token = auth('api')->login($user);

        return $this->ok([
            'token'       => $token,
            'token_type'  => 'Bearer',
            'expires_in'  => self::TOKEN_TTL_MINUTES * 60,
            'is_new_user' => $isNew,
            'user'        => new UserResource($user),
        ]);
    }

    /** GET /auth/me — user dari JWT payload (tanpa query DB ulang). */
    public function me(Request $request): JsonResponse
    {
        return $this->ok(['user' => new UserResource($request->user())]);
    }

    /**
     * POST /auth/phone/request-otp — kirim OTP ke NOMOR BARU (ganti HP).
     */
    public function requestPhoneChangeOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->phone();

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
            return $this->fail('Nomor ini baru saja dipakai akun lain.', 422);
        }

        $user = $request->user();
        $user->phone = $phone;
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
            $user->avatar_url = $request->file('avatar')->store('avatars', 'public');
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

    /**
     * POST /auth/logout — blacklist token JWT yang sedang dipakai.
     *
     * Berbeda dari Sanctum (hapus baris DB): JWT tidak punya tabel token,
     * jadi logout dilakukan dengan mem-blacklist token saat ini. Token
     * yang di-blacklist tidak bisa dipakai lagi meski belum expired.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            auth('api')->logout();

            return $this->ok(null, 'Berhasil keluar.');
        } catch (JWTException $e) {
            // Token sudah expired atau invalid — logout tetap sukses.
            return $this->ok(null, 'Berhasil keluar.');
        }
    }

    /**
     * POST /auth/refresh — perpanjang token JWT tanpa login ulang.
     *
     * Token lama di-blacklist, token baru diterbitkan. Hanya bisa dipakai
     * saat token saat ini masih valid (guard JWT memeriksa signature +
     * expiry + blacklist).
     *
     * Refresh window: 2 minggu sejak token pertama diterbitkan
     * (config/jwt.php refresh_ttl). Setelah itu wajib login ulang.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $newToken = auth('api')->refresh();

            return $this->ok([
                'token'      => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => self::TOKEN_TTL_MINUTES * 60,
            ]);
        } catch (JWTException $e) {
            return $this->fail('Token tidak dapat diperpanjang. Silakan login ulang.', 401);
        }
    }

    /**
     * GET /auth/export-data — portabilitas data (UU PDP pasal 8).
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load([
            'stores.listings', 'stores.offers',
            'customerRequests', 'orders' => fn ($q) => $q->with('store', 'listing', 'reviews'),
        ]);

        return $this->ok([
            'exported_at' => now()->toIso8601ZuluString(),
            'data'        => new UserExportResource($user),
        ]);
    }

    /**
     * DELETE /auth/account — hak dilupakan (right to erasure).
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->privacy->anonymizeUser($user);

        // Blacklist token setelah anonimisasi — akun sudah tidak bisa dipakai.
        try {
            auth('api')->logout();
        } catch (JWTException) {
            // Tidak masalah jika token sudah invalid.
        }

        return $this->ok(null, 'Akun Anda telah dianonimkan sesuai permintaan.');
    }
}
