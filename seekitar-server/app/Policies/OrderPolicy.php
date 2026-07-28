<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Hanya dua pihak pada transaksi yang boleh melihat pesanan. */
    public function view(User $user, Order $order): bool
    {
        return $this->isParty($user, $order);
    }

    /**
     * Transisi status: hanya pihak terkait. SAH atau tidaknya perpindahan
     * itu sendiri diputuskan `OrderStateMachine`, bukan di sini — Policy
     * menjawab "siapa", state machine menjawab "boleh ke mana".
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $this->isParty($user, $order);
    }

    /** Bukti transfer diunggah pembeli. */
    public function uploadPaymentProof(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id;
    }

    public function dispute(User $user, Order $order): bool
    {
        return $this->isParty($user, $order);
    }

    /** Ulasan hanya oleh pihak terkait, pada pesanan selesai & masih dalam jendela. */
    public function review(User $user, Order $order): bool
    {
        return $this->isParty($user, $order) && $order->acceptsReview();
    }

    private function isParty(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id
            || $order->store?->user_id === $user->id;
    }
}
