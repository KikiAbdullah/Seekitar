<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OtpDeliveryException;
use App\Http\Controllers\Controller;
use App\Services\Contracts\WhatsAppGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Panel admin — Gateway WhatsApp (Baileys).
 *
 * Menampilkan status koneksi, QR code untuk scan, tombol cabut sesi, dan
 * form uji kirim. Hanya bermakna bila WHATSAPP_DRIVER=baileys; driver lain
 * menampilkan pesan bahwa fitur ini tidak aktif.
 */
class WhatsAppController extends Controller
{
    public function __construct(private readonly WhatsAppGateway $whatsapp) {}

    public function index(): View
    {
        return view('admin.whatsapp.index', [
            'driver'    => (string) config('whatsapp.driver', 'log'),
            'isBaileys' => $this->isBaileys(),
            'status'    => $this->isBaileys() ? $this->statusCached() : null,
        ]);
    }

    /** GET /admin/whatsapp/status — JSON untuk polling UI. */
    public function status(): JsonResponse
    {
        if (! $this->isBaileys()) {
            return response()->json([
                'success' => false,
                'message' => "Driver WhatsApp aktif: {$this->driverLabel()}. Tidak ada status Baileys.",
            ]);
        }

        try {
            return response()->json(['success' => true, 'data' => $this->statusCached()]);
        } catch (OtpDeliveryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gateway Baileys tidak terjangkau: '.$e->getMessage(),
            ], 503);
        }
    }

    /** GET /admin/whatsapp/qr — QR code (data URL PNG) untuk scan. */
    public function qr(): JsonResponse
    {
        if (! $this->isBaileys()) {
            return response()->json(['success' => false, 'message' => 'Driver WhatsApp bukan Baileys.']);
        }

        try {
            /** @var \App\Services\WhatsApp\BaileysGateway $baileys */
            $baileys = $this->whatsapp;

            return response()->json([
                'success' => true,
                'data'    => ['qr' => $baileys->qr(), 'online' => $this->statusCached()['online']],
            ]);
        } catch (OtpDeliveryException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }
    }

    /** POST /admin/whatsapp/logout — cabut sesi, minta scan ulang. */
    public function logout(): JsonResponse
    {
        if (! $this->isBaileys()) {
            return response()->json(['success' => false, 'message' => 'Driver WhatsApp bukan Baileys.']);
        }

        try {
            /** @var \App\Services\WhatsApp\BaileysGateway $baileys */
            $baileys = $this->whatsapp;
            $baileys->logout();
            Cache::forget('whatsapp.status');

            return response()->json(['success' => true, 'message' => 'Sesi WhatsApp dicabut. Scan QR untuk menyambung ulang.']);
        } catch (OtpDeliveryException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }
    }

    /** POST /admin/whatsapp/reset — hapus sesi & minta QR BARU (atasi QR macet). */
    public function reset(): JsonResponse
    {
        if (! $this->isBaileys()) {
            return response()->json(['success' => false, 'message' => 'Driver WhatsApp bukan Baileys.']);
        }

        try {
            /** @var \App\Services\WhatsApp\BaileysGateway $baileys */
            $baileys = $this->whatsapp;
            $baileys->resetSession();
            Cache::forget('whatsapp.status');

            return response()->json(['success' => true, 'message' => 'Sesi di-reset. QR baru akan segera muncul — scan ulang.']);
        } catch (OtpDeliveryException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }
    }

    /** POST /admin/whatsapp/send-test — uji kirim pesan. */
    public function sendTest(Request $request): JsonResponse
    {
        if (! $this->isBaileys()) {
            return response()->json(['success' => false, 'message' => 'Driver WhatsApp bukan Baileys.']);
        }

        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9+]{8,16}$/'],
            'text'  => ['required', 'string', 'max:500'],
        ]);

        try {
            /** @var \App\Services\WhatsApp\BaileysGateway $baileys */
            $baileys = $this->whatsapp;
            $baileys->send($data['phone'], $data['text']);

            return response()->json(['success' => true, 'message' => 'Pesan terkirim.']);
        } catch (OtpDeliveryException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }
    }

    private function isBaileys(): bool
    {
        return strtolower((string) config('whatsapp.driver', 'log')) === 'baileys'
            && $this->whatsapp instanceof \App\Services\WhatsApp\BaileysGateway;
    }

    private function driverLabel(): string
    {
        return match (strtolower((string) config('whatsapp.driver', 'log'))) {
            'baileys' => 'Baileys',
            'email'   => 'Email',
            'kirimwa' => 'Kirim WA',
            default   => 'Log',
        };
    }

    /** Status dengan cache singkat — polling UI tidak membanjiri service Node. */
    private function statusCached(): array
    {
        return Cache::remember('whatsapp.status', 3, function (): array {
            /** @var \App\Services\WhatsApp\BaileysGateway $baileys */
            $baileys = $this->whatsapp;

            return $baileys->status();
        });
    }
}
