<?php

namespace App\Http\Controllers\Api\V1\Admin;

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
        $query = User::query();

        if ($request->filled('verification_level')) {
            $query->where('verification_level', $request->integer('verification_level'));
        }

        if ($request->has('is_blocked')) {
            $query->where('is_blocked', $request->boolean('is_blocked'));
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

        DB::transaction(function () use ($user, $data): void {
            $blocked = (bool) $data['is_blocked'];

            $user->is_blocked     = $blocked;
            $user->blocked_reason = $blocked ? $data['reason'] : null;
            $user->blocked_at     = $blocked ? now() : null;
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
