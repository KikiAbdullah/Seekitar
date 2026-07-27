<?php

namespace App\Enums;

/**
 * Status pesanan — nilainya sama persis dengan ENUM orders.status
 * (DATABASE.md §4.7). Label alur jasa/sewa seperti "Dijadwalkan" atau
 * "Dikembalikan" adalah tampilan turunan, bukan nilai baru (PRD §5.4).
 */
enum OrderStatus: string
{
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case Diproses           = 'diproses';
    case Dikirim            = 'dikirim';
    case Selesai            = 'selesai';
    case Dibatalkan         = 'dibatalkan';
    case Dispute            = 'dispute';

    public function label(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'Menunggu Konfirmasi',
            self::Diproses           => 'Diproses',
            self::Dikirim            => 'Dikirim / Siap Diambil',
            self::Selesai            => 'Selesai',
            self::Dibatalkan         => 'Dibatalkan',
            self::Dispute            => 'Dispute',
        };
    }

    /**
     * Label yang bergantung konteks pesanan (PRD §5.4).
     * "dikirim" tampil berbeda untuk pickup vs delivery.
     */
    public function contextualLabel(OrderType $type, DeliveryMethod $delivery): string
    {
        // Urutan penting: match(true) mengambil kecocokan PERTAMA.
        // Cabang rental harus diperiksa sebelum cabang pickup, karena
        // sewa yang diambil sendiri tetap berlabel "Disewa".
        return match (true) {
            $this === self::Dikirim && $type === OrderType::Rental          => 'Disewa',
            $this === self::Dikirim && $delivery === DeliveryMethod::Pickup => 'Siap Diambil',
            $this === self::Diproses && $type === OrderType::Service        => 'Dalam Pengerjaan',
            default => $this->label(),
        };
    }

    /** Status akhir tidak boleh berubah lagi. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Selesai, self::Dibatalkan], true);
    }

    /** Warna badge sesuai BRANDING-GUIDELINE.md §3.5.3. */
    public function color(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => '#F5B83D',
            self::Diproses, self::Dikirim => '#2563EB',
            self::Selesai    => '#16A34A',
            self::Dibatalkan => '#9CA3AF',
            self::Dispute    => '#DC2626',
        };
    }
}
