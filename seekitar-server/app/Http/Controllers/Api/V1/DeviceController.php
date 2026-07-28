<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pendaftaran perangkat untuk push notification (API §2.7).
 */
class DeviceController extends Controller
{
    use ApiResponse;

    /** POST /auth/fcm-token */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
            'device_id' => ['required', 'string', 'max:100'],
            'platform'  => ['required', 'in:android,ios'],
        ]);

        // Kunci pencarian device_id SAJA, bukan (user_id, device_id).
        // Satu ponsel hanya boleh terikat ke satu akun: kalau pengguna B
        // masuk di ponsel bekas pengguna A, baris lama harus DITIMPA —
        // kalau tidak, notifikasi milik A tetap mendarat di ponsel B
        // (DATABASE.md §4.9a).
        UserDevice::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'user_id'      => $request->user()->id,
                'fcm_token'    => $data['fcm_token'],
                'platform'     => $data['platform'],
                'last_used_at' => now(),
            ],
        );

        return $this->ok(null, 'Perangkat terdaftar.');
    }

    /** DELETE /auth/fcm-token — dipanggil saat logout. */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
        ]);

        UserDevice::where('device_id', $data['device_id'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return $this->ok(null, 'Perangkat dilepas.');
    }
}
