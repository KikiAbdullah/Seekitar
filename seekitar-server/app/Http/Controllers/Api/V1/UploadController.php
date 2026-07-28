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
        $name = Str::uuid()->toString().'.'.$file->extension();

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
}
