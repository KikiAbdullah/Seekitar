<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Preferensi notifikasi pengguna — apa yang ingin diterima via push.
 */
class NotificationPreferenceController extends Controller
{
    use ApiResponse;

    /**
     * Daftar kanal notifikasi yang bisa di-toggle.
     * Key = id di JSON, Value = label untuk response.
     */
    private const CHANNELS = [
        'order_updates'    => 'Pembaruan pesanan',
        'new_offers'       => 'Penawaran baru',
        'chat_messages'    => 'Pesan chat',
        'request_matches'  => 'Permintaan yang cocok',
        'review_reminders' => 'Pengingat ulasan',
        'promotions'       => 'Promo & info',
    ];

    /** GET /notifications/preferences */
    public function show(Request $request): JsonResponse
    {
        $prefs = $this->getPreferences($request->user());

        $channels = [];
        foreach (self::CHANNELS as $key => $label) {
            $channels[] = [
                'key'     => $key,
                'label'   => $label,
                'enabled' => $prefs[$key] ?? true, // default: semua aktif
            ];
        }

        return $this->ok(['channels' => $channels]);
    }

    /** PATCH /notifications/preferences */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channels'                => ['required', 'array'],
            'channels.*.key'          => ['required', 'string', 'in:'.implode(',', array_keys(self::CHANNELS))],
            'channels.*.enabled'      => ['required', 'boolean'],
        ]);

        $prefs = $this->getPreferences($request->user());

        foreach ($data['channels'] as $ch) {
            $prefs[$ch['key']] = $ch['enabled'];
        }

        $this->savePreferences($request->user(), $prefs);

        return $this->show($request);
    }

    /** Baca preferensi dari kolom JSON `notification_prefs` di tabel users. */
    private function getPreferences($user): array
    {
        $raw = $user->notification_prefs;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }

    /** Simpan preferensi. */
    private function savePreferences($user, array $prefs): void
    {
        $user->notification_prefs = json_encode($prefs);
        $user->save();
    }
}
