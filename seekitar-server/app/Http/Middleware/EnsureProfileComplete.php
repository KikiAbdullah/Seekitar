<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak aksi transaksional sampai nama & lokasi terisi.
 *
 * `users.location` sengaja NULL-able (DATABASE.md §4.1) karena baris user
 * harus ada lebih dulu pada alur OTP — lokasi baru diisi setelahnya. Karena
 * itu kewajibannya ditegakkan di sini, bukan lewat constraint database.
 *
 * ⚠️ JANGAN pasang middleware ini pada `PATCH /auth/profile`. Kalau dipasang,
 * pengguna baru terkunci: ia butuh melengkapi profil, tetapi endpoint untuk
 * melakukannya justru menolaknya.
 */
class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isProfileComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi profil (nama & lokasi) terlebih dahulu.',
                'errors'  => ['profile' => ['incomplete']],
            ], 403);
        }

        return $next($request);
    }
}
