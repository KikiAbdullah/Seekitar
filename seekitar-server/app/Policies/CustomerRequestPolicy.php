<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\CustomerRequest;
use App\Models\User;

class CustomerRequestPolicy
{
    public function view(User $user, CustomerRequest $request): bool
    {
        return $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CustomerRequest $request): bool
    {
        return $request->user_id === $user->id
            && $request->status !== RequestStatus::Closed;
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
            && $request->status !== RequestStatus::Closed;
    }
}
