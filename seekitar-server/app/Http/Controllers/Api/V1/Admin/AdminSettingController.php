<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Pengaturan sistem — hanya super-admin (API §10.5). */
class AdminSettingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SettingService $settings) {}

    /** GET /admin/settings */
    public function index(): JsonResponse
    {
        return $this->ok(['settings' => Setting::orderBy('group')->orderBy('key')->get()]);
    }

    /** POST /admin/settings */
    public function update(Request $request): JsonResponse
    {
        $keys = Setting::pluck('key')->all();

        $data = $request->validate([
            'settings'   => ['required', 'array', 'min:1'],
            // Kunci yang tidak dikenal ditolak: salah ketik akan diam-diam
            // membuat baris baru yang tidak pernah dibaca kode mana pun.
            'settings.*' => ['nullable'],
        ]);

        $unknown = array_diff(array_keys($data['settings']), $keys);

        if ($unknown !== []) {
            return $this->fail('Kunci pengaturan tidak dikenal: '.implode(', ', $unknown), 422);
        }

        $this->settings->set($data['settings']);

        return $this->ok(['settings' => Setting::orderBy('group')->orderBy('key')->get()]);
    }
}
