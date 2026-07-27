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
     * Transisi yang diizinkan: status asal => daftar status tujuan.
     *
     * @var array<string, array<int, OrderStatus>>
     */
    private const TRANSITIONS = [
        // Penjual menerima -> diproses; menolak, atau pembeli membatalkan.
        'menunggu_konfirmasi' => [OrderStatus::Diproses, OrderStatus::Dibatalkan, OrderStatus::Dispute],

        // Sudah dikerjakan. Pembeli TIDAK bisa lagi membatalkan sepihak
        // (PRD §5.4.1), tetapi pembatalan oleh penjual masih mungkin,
        // mis. stok ternyata habis.
        'diproses' => [OrderStatus::Dikirim, OrderStatus::Dibatalkan, OrderStatus::Dispute],

        // Barang di jalan / siap diambil / sedang disewa.
        'dikirim' => [OrderStatus::Selesai, OrderStatus::Dispute],

        // Status akhir — tidak ada jalan keluar.
        'selesai'    => [],
        'dibatalkan' => [],

        // Dispute membeku sampai admin memutuskan (API §9). Hasilnya hanya
        // dua: transaksi diteruskan sebagai selesai, atau dibatalkan.
        'dispute' => [OrderStatus::Selesai, OrderStatus::Dibatalkan],
    ];

    /** @return array<int, OrderStatus> */
    public function allowedFrom(OrderStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    public function canTransition(OrderStatus $from, OrderStatus $to): bool
    {
        // Menyetel status ke nilainya sendiri bukan transisi, dan bukan error:
        // `save()` pada model yang statusnya tidak berubah harus tetap lolos.
        if ($from === $to) {
            return true;
        }

        return in_array($to, $this->allowedFrom($from), true);
    }

    /**
     * @throws InvalidOrderTransitionException
     */
    public function assertCanTransition(OrderStatus $from, OrderStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw InvalidOrderTransitionException::between($from, $to, $this->allowedFrom($from));
        }
    }

    /**
     * Terapkan status baru berikut kolom waktu yang menyertainya.
     *
     * `completed_at` dan `cancelled_at` diisi di sini, bukan di controller,
     * supaya tidak ada jalur yang lupa mengisinya — kolom itu dipakai jendela
     * ulasan 7 hari (PRD §5.4.1) dan audit pembatalan.
     */
    public function transition(Order $order, OrderStatus $to, ?string $reason = null, ?string $byUserId = null): Order
    {
        $from = $order->status;

        $this->assertCanTransition($from, $to);

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
        return $this->allowedFrom($status) === [];
    }
}
