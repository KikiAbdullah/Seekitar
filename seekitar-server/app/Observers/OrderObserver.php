<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderStateMachine;
use Illuminate\Support\Facades\DB;

/**
 * Nomor pesanan & penjaga transisi.
 *
 * Observer, bukan controller: pesanan bisa berubah lewat tinker, seeder, job,
 * atau panel admin. Aturan yang hanya dipasang di controller akan terlewat.
 */
class OrderObserver
{
    public function __construct(
        private readonly OrderStateMachine $states,
    ) {}

    public function creating(Order $order): void
    {
        $order->order_number ??= $this->generateNumber();
    }

    public function updating(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        $original = $order->getOriginal('status');
        $from     = $original instanceof OrderStatus
            ? $original
            : OrderStatus::from((string) $original);

        // Jaring pengaman TERAKHIR: transisi tidak sah ditolak dari jalur
        // mana pun, bukan hanya lewat API.
        // Observer tidak punya konteks user, jadi cek apakah transisi valid untuk SELLER ATAU BUYER.
        $allowedForSeller = $this->states->allowedFrom($from, true);
        $allowedForBuyer  = $this->states->allowedFrom($from, false);
        $allAllowed = array_merge($allowedForSeller, $allowedForBuyer);

        if (! in_array($order->status, $allAllowed, true) && $from !== $order->status) {
            throw new \App\Exceptions\InvalidOrderTransitionException(
                sprintf('Transisi dari "%s" ke "%s" tidak diizinkan.', $from->value, $order->status->value)
            );
        }

        match ($order->status) {
            OrderStatus::Selesai    => $order->completed_at ??= now(),
            OrderStatus::Dibatalkan => $order->cancelled_at ??= now(),
            default                 => null,
        };
    }

    /**
     * `SKT-YYYYMMDD-NNNN`, urutan direset harian (DATABASE.md §4.7).
     *
     * UUID tidak mungkin dibacakan lewat telepon; nomor inilah yang disebut
     * pengguna saat menghubungi dukungan.
     *
     * Urutan disimpan di tabel `order_sequences`, bukan cache. Cache bisa
     * direset/ganti backend sehingga counteernya lepas dari baris yang sudah
     * tersimpan dan memicu duplicate key (riwayat: Redis cache baru diaktifkan
     * → nomor mulai dari 1 → 1062 `orders_order_number_unique` → pesanan gagal).
     *
     * `INSERT ... ON DUPLICATE KEY UPDATE ... LAST_INSERT_ID(seq + 1)`
     * melakukan kenaikan secara atomik di tingkat database, jadi aman untuk
     * proses paralel (worker queue, beberapa request bersamaan).
     */
    private function generateNumber(): string
    {
        $date = now()->format('Y-m-d');

        DB::statement(
            'INSERT INTO order_sequences (`date`, `seq`) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE `seq` = LAST_INSERT_ID(`seq` + 1)',
            [$date],
        );
        $seq = (int) DB::selectOne('SELECT LAST_INSERT_ID() AS `seq`')->seq;

        return sprintf('SKT-%s-%04d', str_replace('-', '', $date), $seq);
    }
}
