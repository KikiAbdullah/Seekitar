<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Blokir pengguna — fitur keamanan & kenyamanan.
 *
 * TIDAK memakai tabel pivot: blokir adalah relasi satu arah yang sederhana
 * dan disimpan sebagai JSON array di kolom `blocked_user_ids` pada tabel
 * users agar query-nya O(1) tanpa join.
 */
class BlockController extends Controller
{
    use ApiResponse;

    /** GET /users/blocked */
    public function index(Request $request): JsonResponse
    {
        $ids = $this->getBlockedIds($request->user());

        if (empty($ids)) {
            return $this->ok([]);
        }

        $blocked = User::whereIn('id', $ids)
            ->select('id', 'name', 'phone', 'avatar_url')
            ->get()
            ->map(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->displayName(),
                'avatar_url' => $u->avatar_url,
            ]);

        return $this->ok($blocked);
    }

    /** POST /users/{user}/block */
    public function block(User $user): JsonResponse
    {
        $me = request()->user();

        if ($user->id === $me->id) {
            return $this->fail('Tidak bisa memblokir diri sendiri.', 422);
        }

        $ids = $this->getBlockedIds($me);

        if (in_array($user->id, $ids, true)) {
            return $this->ok(null, 'Pengguna sudah diblokir.');
        }

        $ids[] = $user->id;
        $this->saveBlockedIds($me, $ids);

        return $this->ok(null, 'Pengguna berhasil diblokir.');
    }

    /** DELETE /users/{user}/block */
    public function unblock(User $user): JsonResponse
    {
        $me = request()->user();

        $ids = $this->getBlockedIds($me);
        $ids = array_values(array_filter($ids, fn ($id) => $id !== $user->id));

        $this->saveBlockedIds($me, $ids);

        return $this->ok(null, 'Pengguna berhasil dibuka blokirnya.');
    }

    private function getBlockedIds($user): array
    {
        $raw = $user->blocked_user_ids;

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($decoded)) {
            return [];
        }

        // users.id adalah UUID — JANGAN di-cast ke int (mengubah semua jadi 0,
        // membuat unblock tak pernah cocok). Data lama yang terlanjur korup
        // (`0`, non-UUID) dibuang; akan hilang permanen saat saveBlockedIds.
        return array_values(array_filter(
            array_map('strval', $decoded),
            static fn (string $id) => preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $id,
            ) === 1,
        ));
    }

    private function saveBlockedIds($user, array $ids): void
    {
        $user->blocked_user_ids = json_encode(array_values(array_unique($ids)));
        $user->save();
    }
}
