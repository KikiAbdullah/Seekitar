<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VerificationStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    use ApiResponse;

    /** GET /admin/users */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            // verification_level di JSON adalah TURUNAN (bukan kolom):
            // EXISTS ini memasok accessor-nya tanpa satu query per baris.
            ->withExists(['stores as has_verified_store' => fn ($q) => $q
                ->where('verification_status', VerificationStatus::Verified)]);

        if ($request->filled('verification_level')) {
            $query->whereVerificationLevel($request->integer('verification_level'));
        }

        if ($request->has('is_blocked')) {
            // Kontrak API-nya dipertahankan, implementasinya pindah ke kolom
            // status: tidak ada lagi boolean terpisah yang bisa bertentangan
            // dengan stempel blokir.
            $request->boolean('is_blocked')
                ? $query->where('status', \App\Enums\UserStatus::Diblokir)
                : $query->whereNot('status', \App\Enums\UserStatus::Diblokir);
        }

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        return $this->paginated($query->latest()->paginate($this->perPage()), UserResource::class);
    }

    /**
     * PATCH /admin/users/{user}/block
     *
     * Memblokir WAJIB mencabut seluruh token: tanpa itu, sesi yang sudah
     * berjalan tetap bisa dipakai sampai tokennya kedaluwarsa sendiri
     * (API §10.3).
     */
    public function block(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'is_blocked' => ['required', 'boolean'],
            'reason'     => ['required_if:is_blocked,true', 'nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($user, $data, $request): void {
            $blocked = (bool) $data['is_blocked'];

            if ($blocked) {
                $user->status         = \App\Enums\UserStatus::Diblokir;
                $user->blocked_by     = $request->user()->id;
                $user->blocked_at     = now();
                $user->blocked_reason = $data['reason'];
            } else {
                // Kedudukan lamanya pulih kembali dari stempel verified_
                // yang tidak pernah dicabut blokir — bukan tebakan.
                $user->status         = $user->statusSebelumDiblokir();
                $user->blocked_by     = null;
                $user->blocked_at     = null;
                $user->blocked_reason = null;
            }
            $user->save();

            if ($blocked) {
                $user->tokens()->delete();
                // Toko miliknya ikut dinonaktifkan agar listingnya hilang
                // dari pencarian; pesanan berjalan sengaja TIDAK diusik
                // supaya pihak lawan tidak dirugikan.
                $user->stores()->update(['is_active' => false]);
            }
        });

        return $this->ok(['user' => new UserResource($user->fresh())]);
    }
}
