<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan pemanggil benar-benar pemilik toko pada route.
 *
 * Tanpa ini, mengganti `{store}` di URL dengan id toko orang lain akan
 * berhasil — kelas kerentanan IDOR yang paling umum.
 *
 * 404, bukan 403, saat toko bukan miliknya: membalas 403 memberi tahu
 * penyerang bahwa id tersebut ada. Bagi bukan-pemilik, toko itu memang
 * seolah tidak ada.
 */
class EnsureStoreOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user  = $request->user();
        $store = $request->route('store');

        if (! $store instanceof Store) {
            $store = Store::find($store);
        }

        if (! $user || ! $store || $store->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Toko tidak ditemukan.',
                'errors'  => null,
            ], 404);
        }

        return $next($request);
    }
}
