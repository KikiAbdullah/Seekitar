<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderStateMachine;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

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
        private readonly CacheRepository $cache,
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
     * Memakai kontrak cache (atomic increment), bukan facade Redis, supaya
     * kelas ini tetap bisa diuji tanpa server Redis. TTL 48 jam: cukup untuk
     * melewati pergantian hari, tanpa menumpuk kunci selamanya.
     */
    private function generateNumber(): string
    {
        $date = now()->format('Ymd');
        $key  = "order_seq:$date";

        // add() hanya berhasil bila kunci belum ada — inilah yang membuat
        // penyetelan awal aman dari balapan antar-proses.
        $this->cache->add($key, 0, now()->addHours(48));
        $seq = $this->cache->increment($key);

        return sprintf('SKT-%s-%04d', $date, $seq);
    }
}
