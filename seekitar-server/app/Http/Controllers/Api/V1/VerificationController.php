<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'nik'          => ['required', 'string', 'digits:16'],
            'address'      => ['required', 'string', 'max:255'],
            'latitude'     => ['required', 'numeric', 'between:-90,90'],
            'longitude'    => ['required', 'numeric', 'between:-180,180'],
        ]);

        $sudahTerverifikasi = $user->verified_at !== null;
        $ktpLama            = $user->ktp_image;
        $selfieLama         = $user->selfie_image;

        // Disk PRIVAT, bukan public: KTP adalah data pribadi (UU PDP) dan
        // tidak boleh bisa diakses lewat URL tebakan.
        $user->ktp_image    = $request->file('ktp_image')->store("ktp/{$user->id}", 'local');
        $user->selfie_image = $request->file('selfie_image')->store("ktp/{$user->id}", 'local');

        // Kolom `nik` bercast 'encrypted'; `nik_hash` yang membuat
        // duplikasi tetap terdeteksi, karena kolom terenkripsi tidak
        // bisa di-WHERE (DATABASE.md §4.1).
        $user->nik      = $request->input('nik');
        $user->nik_hash = hash_hmac('sha256', $request->input('nik'), config('app.key'));

        // Alamat domisili + titik GPS (dari aplikasi) yang diperiksa admin.
        $user->address = $request->input('address');
        $user->setLocation(
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
        );

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

    /**
     * Foto identitas MILIK SENDIRI (KTP / selfie) untuk ditampilkan di
     * aplikasi. Hanya pemilik akun yang bisa melihat fotonya sendiri —
     * data pribadi (UU PDP). `kind` dibatasi whereIn pada route.
     */
    public function myPhoto(Request $request, string $kind): StreamedResponse
    {
        $user = $request->user();

        $path = match ($kind) {
            'ktp'    => $user->ktp_image,
            'selfie' => $user->selfie_image,
        };

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $response = Storage::disk('local')->response($path);
        $response->headers->set('Content-Type', Storage::disk('local')->mimeType($path));
        $response->headers->set('Content-Disposition', 'inline');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
