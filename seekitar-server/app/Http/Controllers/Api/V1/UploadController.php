<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Unggah gambar SEBELUM entitasnya dibuat (API §4.0).
 *
 * Memisahkan unggahan dari penyimpanan data membuat pengguna bisa mengunggah
 * foto sambil masih mengisi formulir, dan kegagalan pada satu foto tidak
 * membatalkan seluruh isian.
 */
class UploadController extends Controller
{
    use ApiResponse;

    /** Berkas sementara dibersihkan job terjadwal bila tidak jadi dipakai. */
    private const TEMP_TTL_HOURS = 24;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file'    => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'purpose' => ['required', 'in:listing,request,payment_proof'],
        ]);

        $file    = $request->file('file');
        $purpose = $data['purpose'];

        // Nama acak, BUKAN nama asli dari klien: nama unggahan bisa memuat
        // path traversal ("../") atau ekstensi ganda ("x.php.jpg").
        // Ekstensi diambil dari KONTEN berkas (guessExtension → MIME yang
        // di-sniff), bukan $file->extension() yang mengikuti nama klien —
        // mencegah gambar palsu disimpan sebagai {uuid}.php lalu dieksekusi
        // web server (RCE).
        $name = Str::uuid()->toString().'.'.$this->safeImageExtension($file);

        // Bukti transfer memuat nomor rekening & nominal, jadi masuk disk
        // privat. Foto listing/permintaan memang untuk ditampilkan publik.
        $disk = $purpose === 'payment_proof' ? 'local' : 'public';
        $path = $file->storeAs("tmp/{$purpose}", $name, $disk);

        return $this->created([
            'url'        => $disk === 'public' ? Storage::disk('public')->url($path) : $path,
            'path'       => $path,
            'expires_at' => now()->addHours(self::TEMP_TTL_HOURS)->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * Ekstensi aman untuk gambar upload.
     *
     * `guessExtension()` menurunkan ekstensi dari MIME konten (finfo), bukan
     * dari nama berkas klien. Selalu dipaksa ke whitelist jpg/png/webp;
     * bila hasil sniffing aneh (validasi `image`+`mimes` seharusnya mencegah
     * ini), jatuh ke `png` daripada menyimpan ekstensi berbahaya.
     */
    private function safeImageExtension(\Illuminate\Http\UploadedFile $file): string
    {
        $ext = strtolower($file->guessExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        return in_array($ext, $allowed, true) ? $ext : 'png';
    }

    /**
     * DELETE /uploads/images — hapus berkas sementara yang TIDAK jadi dipakai
     * (mis. satu unggahan gagal di tengah daftar foto, atau entitas batal dibuat).
     *
     * Keamanan: hanya path di dalam direktori `tmp/` yang boleh dihapus, dan
     * path traversal (`..`) ditolak — mencegah penghapusan file di luar area
     * upload lewat parameter dari klien.
     */
    public function destroy(Request $request): JsonResponse
    {
        $path = (string) $request->input('path');

        $isTemp = str_starts_with($path, 'tmp/') && ! str_contains($path, '..');

        if (! $isTemp) {
            return $this->fail('Path tidak valid.', 422);
        }

        // Best-effort: kalau file tidak ada, anggap sudah terhapus.
        foreach (['public', 'local'] as $disk) {
            Storage::disk($disk)->delete($path);
        }

        return $this->ok(null, 'Berkas dihapus.');
    }
}
