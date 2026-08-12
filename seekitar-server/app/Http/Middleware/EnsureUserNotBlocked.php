<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak permintaan dari user yang DIBLOKIR.
 *
 * JWT bersifat stateless dan hidup hingga 30 hari — server tidak bisa
 * mencabut token yang sudah terbit. Middleware ini memeriksa status user di
 * SETIAP request yang terautentikasi, jadi begitu admin memblokir, akses
 * langsung berhenti (423 Locked) tanpa menunggu token kedaluwarsa.
 */
class EnsureUserNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda diblokir. Hubungi dukungan Seekitar.',
                'errors'  => null,
            ], 423);
        }

        return $next($request);
    }
}
