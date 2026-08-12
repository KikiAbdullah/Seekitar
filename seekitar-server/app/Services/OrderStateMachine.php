<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;

/**
 * Penjaga transisi status pesanan.
 *
 * SATU sumber kebenaran untuk pertanyaan "boleh tidak pindah dari A ke B".
 * Aturan ini dipanggil dari Policy, controller, DAN `OrderObserver` sebagai
 * jaring pengaman terakhir (`Server_Implementation_Guide.md` §13.2) — kalau
 * tersebar, salah satu jalur pasti ketinggalan saat aturan berubah.
 *
 * Matriks ini sengaja TIDAK bercabang per `order_type`. PRD §5.4 menegaskan
 * `orders.status` hanya punya enam nilai; label seperti "Dijadwalkan" atau
 * "Dikembalikan" adalah tampilan turunan (lihat `OrderStatus::contextualLabel`).
 * Menambah cabang per tipe akan menggandakan jumlah transisi yang harus diuji
 * tanpa menambah informasi apa pun.
 */
final class OrderStateMachine
{
    /**
     * Transisi yang diizinkan untuk PENJUAL: status asal => daftar status tujuan.
     *
     * @var array<string, array<int, OrderStatus>>
     */
    private const SELLER_TRANSITIONS = [
        'menunggu_konfirmasi' => [OrderStatus::Diproses, OrderStatus::Dibatalkan, OrderStatus::Dispute],
        'diproses' => [OrderStatus::Dikirim, OrderStatus::Dibatalkan, OrderStatus::Dispute],
        'dikirim' => [OrderStatus::Dispute],
        'selesai'    => [],
        'dibatalkan' => [],
        'dispute' => [OrderStatus::Selesai, OrderStatus::Dibatalkan],
    ];

    /**
     * Transisi yang diizinkan untuk PEMBELI: status asal => daftar status tujuan.
     *
     * @var array<string, array<int, OrderStatus>>
     */
    private const BUYER_TRANSITIONS = [
        'menunggu_konfirmasi' => [OrderStatus::Dibatalkan, OrderStatus::Dispute],
        'diproses' => [OrderStatus::Dispute],
        'dikirim' => [OrderStatus::Selesai, OrderStatus::Dispute],
        'selesai'    => [],
        'dibatalkan' => [],
        'dispute' => [],
    ];

    /** @return array<int, OrderStatus> */
    public function allowedFrom(OrderStatus $from, bool $isSeller): array
    {
        $transitions = $isSeller ? self::SELLER_TRANSITIONS : self::BUYER_TRANSITIONS;
        return $transitions[$from->value] ?? [];
    }

    public function canTransition(OrderStatus $from, OrderStatus $to, bool $isSeller): bool
    {
        // Menyetel status ke nilainya sendiri bukan transisi, dan bukan error:
        // `save()` pada model yang statusnya tidak berubah harus tetap lolos.
        if ($from === $to) {
            return true;
        }

        return in_array($to, $this->allowedFrom($from, $isSeller), true);
    }

    /**
     * @throws InvalidOrderTransitionException
     */
    public function assertCanTransition(OrderStatus $from, OrderStatus $to, bool $isSeller): void
    {
        if (! $this->canTransition($from, $to, $isSeller)) {
            throw InvalidOrderTransitionException::between($from, $to, $this->allowedFrom($from, $isSeller));
        }
    }

    /**
     * Terapkan status baru berikut kolom waktu yang menyertainya.
     *
     * `completed_at` dan `cancelled_at` diisi di sini, bukan di controller,
     * supaya tidak ada jalur yang lupa mengisinya — kolom itu dipakai jendela
     * ulasan 7 hari (PRD §5.4.1) dan audit pembatalan.
     */
    public function transition(Order $order, OrderStatus $to, ?string $reason = null, ?string $byUserId = null, bool $isAdmin = false): Order
    {
        $from = $order->status;
        $isSeller = $isAdmin || ($byUserId !== null && $order->store?->user_id === $byUserId);

        $this->assertCanTransition($from, $to, $isSeller);

        if ($from === $to) {
            return $order;
        }

        $order->status = $to;

        if ($to === OrderStatus::Selesai) {
            $order->completed_at ??= now();
        }

        if ($to === OrderStatus::Dibatalkan) {
            $order->cancelled_at ??= now();
            $order->cancel_reason = $reason;
            $order->cancelled_by  = $byUserId;
        }

        return $order;
    }

    /** Status akhir: dipakai UI untuk menyembunyikan tombol aksi. */
    public function isFinal(OrderStatus $status): bool
    {
        return $this->allowedFrom($status, true) === [] && $this->allowedFrom($status, false) === [];
    }
}
