<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VerificationLevel;
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
        $users = User::whereNotNull('ktp_submitted_at')
            ->where('verification_level', VerificationLevel::Basic)
            ->orderBy('ktp_submitted_at')       // SLA: yang paling lama menunggu didahulukan
            ->paginate($this->perPage());

        return $this->paginated($users, UserResource::class);
    }

    /** POST /admin/verifications/users/{user}/approve */
    public function approveUser(User $user): JsonResponse
    {
        $user->verification_level  = VerificationLevel::Verified;
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

    /** POST /admin/verifications/stores/{store}/approve */
    public function approveStore(Store $store): JsonResponse
    {
        $store->verification_status = VerificationStatus::Verified;
        $store->rejected_reason     = null;
        $store->verified_at         = now();
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
