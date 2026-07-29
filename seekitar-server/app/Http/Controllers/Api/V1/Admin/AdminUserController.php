<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\StoreStatus;
use App\Enums\UserStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\VerifikasiTokoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VerifikasiTokoService $verifikasiToko) {}

    /** GET /admin/users */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            // verification_level di JSON adalah TURUNAN (bukan kolom):
            // EXISTS ini memasok accessor-nya tanpa satu query per baris.
            ->withExists(['stores as has_verified_store' => fn ($q) => $q
                ->where('status', StoreStatus::Verified)]);

        if ($request->filled('verification_level')) {
            $query->whereVerificationLevel($request->integer('verification_level'));
        }

        if ($request->has('is_blocked')) {
            // Kontrak API-nya dipertahankan, implementasinya pindah ke kolom
            // status: tidak ada lagi boolean terpisah yang bisa bertentangan
            // dengan stempel blokir.
            $request->boolean('is_blocked')
                ? $query->where('status', UserStatus::Diblokir)
                : $query->whereNot('status', UserStatus::Diblokir);
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
     * berjalan tetap bisa dipakai sampai tokennya kedaluwarsa sendiri.
     * Tokonya ikut DIBLOKIR bersamanya — pesanan berjalan sengaja tidak
     * diusik supaya pihak lawan tidak dirugikan.
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
                $user->status         = UserStatus::Diblokir;
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
            }

            // Cascade yang sama dengan panel web: kedudukan tokonya diseret
            // ke blocked beserta jejaknya, dan pulih saat blokir dicabut.
            $this->verifikasiToko->seretBersamaPemilik(
                $user, $blocked, $request->user()->id, $data['reason'] ?? null
            );
        });

        return $this->ok(['user' => new UserResource($user->fresh())]);
    }
}
