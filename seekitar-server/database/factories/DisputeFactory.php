<?php

namespace Database\Factories;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Dispute>
 *
 * SLA 1×24 jam (PRD §5.5) diukur dari `response_deadline`. Definisi
 * "terlambat" di seluruh aplikasi (Dispute::isOverdue, dasbor, lencana
 * sidebar) adalah: status open, `first_responded_at` NULL, dan tenggat lewat.
 * Factory ini menyediakan ketiga keadaan itu secara eksplisit supaya panel
 * admin punya data untuk semua jalurnya.
 */
class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    private const PENJELASAN = [
        'barang_tidak_sesuai' => [
            'Barang yang datang beda merek dengan yang di foto.',
            'Ukuran tidak sesuai pesanan, sudah dikonfirmasi sebelumnya.',
            'Kemasan rusak dan isinya sebagian tumpah.',
        ],
        'jasa_tidak_profesional' => [
            'Pekerjaan ditinggal setengah jalan dan tidak kembali.',
            'Hasil pengerjaan bocor lagi setelah dua hari.',
            'Teknisi datang tanpa peralatan yang dijanjikan.',
        ],
        'penyedia_tidak_responsif' => [
            'Sudah tiga hari tidak dibalas sejak pembayaran.',
            'Nomor tidak bisa dihubungi setelah pesanan dibuat.',
        ],
        'pembeli_fiktif' => [
            'Pembeli tidak ada di alamat saat barang diantar.',
            'Pesanan COD ditolak saat kurir sampai lokasi.',
        ],
        'lainnya' => [
            'Terjadi kesalahpahaman soal biaya tambahan di luar kesepakatan.',
            'Pesanan terkirim ke alamat yang salah karena kesalahan sistem.',
        ],
    ];

    public function definition(): array
    {
        $alasan = fake()->randomElement(DisputeReason::cases());

        return [
            'id'          => (string) Str::uuid7(),
            'order_id'    => Order::factory()->sengketa(),
            'reported_by' => User::factory(),
            'reason'      => $alasan,

            // Alasan 'lainnya' WAJIB disertai penjelasan (DATABASE.md §4.9).
            // Yang lain pun diberi penjelasan karena laporan tanpa konteks
            // tidak bisa ditindaklanjuti admin.
            'description' => fake()->randomElement(self::PENJELASAN[$alasan->value]),

            'status'             => DisputeStatus::Open,
            'response_deadline'  => now()->addHours(24),
            'first_responded_at' => null,
            'resolved_at'        => null,
        ];
    }

    /** Terbuka, masih dalam SLA. */
    public function terbuka(): static
    {
        return $this->state(fn () => [
            'status'             => DisputeStatus::Open,
            'response_deadline'  => now()->addHours(fake()->numberBetween(2, 24)),
            'first_responded_at' => null,
            'resolved_at'        => null,
        ]);
    }

    /**
     * SLA TERLAMPAUI — inilah yang menyalakan lencana merah di sidebar.
     *
     * Ketiga syaratnya disetel bersamaan; menyetel tenggat mundur saja tidak
     * cukup karena `isOverdue()` juga menuntut `first_responded_at` NULL.
     */
    public function lewatSla(): static
    {
        return $this->state(fn () => [
            'status'             => DisputeStatus::Open,
            'response_deadline'  => now()->subHours(fake()->numberBetween(1, 72)),
            'first_responded_at' => null,
            'resolved_at'        => null,
        ]);
    }

    /** Sudah direspons admin tapi belum ditutup — TIDAK terhitung lewat SLA. */
    public function direspons(): static
    {
        return $this->state(fn () => [
            'status'             => DisputeStatus::Open,
            'response_deadline'  => now()->subHours(fake()->numberBetween(1, 12)),
            'first_responded_at' => now()->subHours(fake()->numberBetween(1, 10)),
        ]);
    }

    public function selesai(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status'             => DisputeStatus::Resolved,
            'first_responded_at' => now()->subDays(fake()->numberBetween(1, 5)),
            'resolved_at'        => now()->subDays(fake()->numberBetween(0, 3)),
            'assigned_to'        => $admin?->id,
            'resolution_note'    => fake()->randomElement([
                'Penjual bersedia mengganti barang. Pesanan diteruskan sebagai selesai.',
                'Dana dikembalikan penuh ke pembeli, pesanan dibatalkan.',
                'Kedua pihak sepakat menyelesaikan secara damai dengan potongan harga.',
                'Laporan tidak terbukti setelah pemeriksaan bukti. Pesanan diteruskan.',
            ]),
        ]);
    }

    public function karena(DisputeReason $alasan): static
    {
        return $this->state(fn () => [
            'reason'      => $alasan,
            'description' => fake()->randomElement(self::PENJELASAN[$alasan->value]),
        ]);
    }
}
