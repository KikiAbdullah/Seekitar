<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VerificationStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Http\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Verifikasi KTP pengguna & toko (API §10.2). */
class AdminVerificationController extends Controller
{
    use ApiResponse;

    /** GET /admin/verifications/pending */
    public function pending(): JsonResponse
    {
        $users = User::pendingVerification()    // definisi antrian TUNGGAL, sama dengan panel web
            ->withExists(['stores as has_verified_store' => fn ($q) => $q
                ->where('verification_status', VerificationStatus::Verified)])
            ->orderBy('ktp_submitted_at')       // SLA: yang paling lama menunggu didahulukan
            ->paginate($this->perPage());

        return $this->paginated($users, UserResource::class);
    }

    /**
     * POST /admin/verifications/users/{user}/approve
     *
     * Stempel persetujuan ADALAH kenaikan levelnya — sejak kolom
     * verification_level dihapus, "level 2" murni turunan dari stempel ini.
     * Tulis-sekali seperti panel web: tahap yang sudah terisi tidak ditimpa.
     */
    public function approveUser(Request $request, User $user): JsonResponse
    {
        $adminId = $request->user()->id;

        if ($user->verified1_at === null) {
            $user->verified1_by = $adminId;
            $user->verified1_at = now();
        }

        if ($user->verified2_at === null) {
            $user->verified2_by = $adminId;
            $user->verified2_at = now();
        }

        $user->ktp_rejected_reason = null;
        $user->save();

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

        $user->ktp_rejected_reason = $data['reason'];
        $user->ktp_submitted_at    = null;
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
        if ($store->verification_status !== VerificationStatus::Pending) {
            return $this->fail('Toko ini sudah diproses sebelumnya.', 422);
        }

        if (! ($store->owner?->canOpenStore() ?? false)) {
            return $this->fail('Pemilik toko belum terverifikasi (nomor HP + KTP).', 422);
        }

        // Foto dinilai dari kolom mentah: placeholder hiasan bukan bukti
        // unggahan pemilik, dan aksesor photo selalu mengembalikan URL.
        if (empty($store->getRawOriginal('photo'))) {
            return $this->fail('Foto toko belum diunggah pemilik.', 422);
        }

        $store->verification_status = VerificationStatus::Verified;
        $store->rejected_reason     = null;
        $store->verified_at         = now();
        $store->verified_by         = $request->user()->id;
        $store->save();

        return $this->ok(['store' => new StoreResource($store)]);
    }

    /** POST /admin/verifications/stores/{store}/reject */
    public function rejectStore(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $store->verification_status = VerificationStatus::Rejected;
        $store->rejected_reason     = $data['reason'];
        $store->save();

        return $this->ok(['store' => new StoreResource($store)]);
    }
}
