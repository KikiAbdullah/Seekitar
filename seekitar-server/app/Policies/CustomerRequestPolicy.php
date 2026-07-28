<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\CustomerRequest;
use App\Models\User;

class CustomerRequestPolicy
{
    public function update(User $user, CustomerRequest $request): bool
    {
        return $request->user_id === $user->id;
    }

    /**
     * Perpanjangan hanya oleh pemilik, hanya saat masih `open`, dan hanya
     * selama kuota perpanjangan belum habis (DATABASE.md §4.5).
     */
    public function extend(User $user, CustomerRequest $request): bool
    {
        return $request->user_id === $user->id
            && $request->status === RequestStatus::Open;
    }

    public function delete(User $user, CustomerRequest $request): bool
    {
        return $request->user_id === $user->id
            || $user->can('manage-requests');
    }
}
