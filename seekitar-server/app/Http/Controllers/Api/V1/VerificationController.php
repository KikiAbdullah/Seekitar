<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pengajuan & PERUBAHAN berkas verifikasi KTP (API §2.5).
 */
class VerificationController extends Controller
{
    use ApiResponse;

    public function uploadKtp(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'ktp_image'    => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'selfie_image' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'nik'          => ['sometimes', 'nullable', 'digits:16'],
        ]);

        $sudahTerverifikasi = $user->verified_at !== null;
        $ktpLama            = $user->ktp_image;
        $selfieLama         = $user->selfie_image;

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

        $user->ktp_submitted_at = now();
        $user->status           = UserStatus::Menunggu;

        // Jejak penolakan lama SENGAJA dipertahankan (dan tampil di antrian):
        // itulah satu-satunya cara admin tahu harus memeriksa ulang apa.
        // Baru dibersihkan saat admin menyetujui siklus ini.

        // Aturan 5: perubahan dari sisi PENGGUNA wajib ditinjau ulang —
        // akun yang sebelumnya sudah lulus kehilangan stempel persetujuannya
        // saat mengganti berkas, dan antrian admin terbuka lagi. (Kebalikannya:
        // perubahan lewat admin justru SENGAJA tidak menyentuh stempel.)
        if ($sudahTerverifikasi) {
            $user->verified_at = null;
            $user->verified_by = null;
        }

        $user->save();

        // Berkas lama dibuang SETELAH save berhasil — kalau dihapus duluan
        // dan save gagal, pengguna kehilangan satu-satunya salinan berkasnya.
        if ($ktpLama) {
            Storage::disk('local')->delete($ktpLama);
        }
        if ($selfieLama) {
            Storage::disk('local')->delete($selfieLama);
        }

        return $this->ok(
            ['user' => new UserResource($user->fresh())],
            $sudahTerverifikasi
                ? 'Berkas baru diterima — identitas Anda kembali menunggu peninjauan admin.'
                : 'Berkas verifikasi diterima. Ditinjau maksimal 1x24 jam.',
        );
    }
}
