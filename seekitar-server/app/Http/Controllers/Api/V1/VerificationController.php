<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VerificationLevel;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengajuan verifikasi KTP — Level 1 → Level 2 (API §2.5).
 */
class VerificationController extends Controller
{
    use ApiResponse;

    public function uploadKtp(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->verification_level !== VerificationLevel::Basic) {
            return $this->fail('Akun Anda sudah terverifikasi.', 422);
        }

        $request->validate([
            'ktp_image'    => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'selfie_image' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'nik'          => ['sometimes', 'nullable', 'digits:16'],
        ]);

        // Disk PRIVAT, bukan public: KTP adalah data pribadi (UU PDP) dan
        // tidak boleh bisa diakses lewat URL tebakan.
        $user->ktp_image    = $request->file('ktp_image')->store("ktp/{$user->id}", 'local');
        $user->selfie_image = $request->file('selfie_image')->store("ktp/{$user->id}", 'local');

        if ($request->filled('nik')) {
            // Kolom `nik` bercast 'encrypted'; `nik_hash` yang membuat
            // duplikasi tetap terdeteksi, karena kolom terenkripsi tidak
            // bisa di-WHERE (DATABASE.md §4.1).
            $user->nik      = $request->input('nik');
            $user->nik_hash = hash_hmac('sha256', $request->input('nik'), config('app.key'));
        }

        $user->ktp_submitted_at    = now();
        $user->ktp_rejected_reason = null;
        $user->save();

        return $this->ok(
            ['user' => new UserResource($user->fresh())],
            'Berkas verifikasi diterima. Ditinjau maksimal 1x24 jam.',
        );
    }
}
