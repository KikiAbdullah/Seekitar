<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\StoreStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Http\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use App\Services\VerifikasiTokoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Verifikasi KTP pengguna & toko (API §10.2). */
class AdminVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VerifikasiTokoService $verifikasiToko) {}

    /** GET /admin/verifications/pending */
    public function pending(): JsonResponse
    {
        $users = User::pendingVerification()    // definisi antrian TUNGGAL, sama dengan panel web
            ->withExists(['stores as has_verified_store' => fn ($q) => $q
                ->where('status', StoreStatus::Verified)])
            ->orderBy('ktp_submitted_at')       // SLA: yang paling lama menunggu didahulukan
            ->paginate($this->perPage());

        return $this->paginated($users, UserResource::class);
    }

    /**
     * POST /admin/verifications/users/{user}/approve
     *
     * SATU persetujuan, SATU stempel: admin menyatakan wajah, KTP, alamat,
     * dan koordinat sesuai — statusnya berangkat ke terverifikasi. Stempel
     * tulis-sekali seperti panel web: yang sudah terisi tidak pernah ditimpa.
     */
    public function approveUser(Request $request, User $user): JsonResponse
    {
        if ($user->verified_at === null) {
            $user->verified_by       = $request->user()->id;
            $user->verified_at       = now();
            $user->status            = \App\Enums\UserStatus::Terverifikasi;
            $user->rejected_at       = null;
            $user->rejected_by       = null;
            $user->rejected_reason   = null;
            $user->save();
        }

        return $this->ok(['user' => new UserResource($user)]);
    }

    /** POST /admin/verifications/users/{user}/reject */
    public function rejectUser(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            // Alasan WAJIB: tanpa itu pengguna tidak tahu apa yang harus
            // diperbaiki dan akan mengirim ulang berkas yang sama.
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->status          = \App\Enums\UserStatus::Ditolak;
        $user->rejected_by     = $request->user()->id;
        $user->rejected_at     = now();
        $user->rejected_reason = $data['reason'];
        $user->save();

        return $this->ok(['user' => new UserResource($user)]);
    }

    /**
     * POST /admin/verifications/stores/{store}/approve
     *
     * Syaratnya SAMA dengan panel web (DATABASE.md §4.2): antrian pending,
     * pemilik terverifikasi (no HP + KTP), dan foto benar-benar terunggah.
     * Aturan produk tidak boleh berubah hanya karena kanalnya API.
     */
    public function approveStore(Request $request, Store $store): JsonResponse
    {
        // Aturannya PERSIS panel web — service yang sama menjaga stempel
        // tulis-sekali & syaratnya (antrian, pemilik, foto mentah).
        return match ($this->verifikasiToko->setujui($store, $request->user()->id)) {
            'bukan-antrian'              => $this->fail('Toko ini sudah diproses sebelumnya.', 422),
            'pemilik-belum-terverifikasi' => $this->fail('Pemilik toko belum terverifikasi (nomor HP + KTP).', 422),
            'foto-belum-diunggah'        => $this->fail('Foto toko belum diunggah pemilik.', 422),
            default                      => $this->ok(['store' => new StoreResource($store->fresh())]),
        };
    }

    /** POST /admin/verifications/stores/{store}/reject */
    public function rejectStore(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $ditolak = $this->verifikasiToko->tolak($store, $request->user()->id, $data['reason']);

        return $ditolak
            ? $this->ok(['store' => new StoreResource($store->fresh())])
            : $this->fail('Toko ini sudah diproses sebelumnya.', 422);
    }
}
