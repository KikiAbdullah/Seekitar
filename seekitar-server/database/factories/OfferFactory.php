<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\CustomerRequest;
use App\Models\Offer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Offer>
 *
 * CHECK offers_expiry_chk: `expires_at > created_at`. Karena `created_at`
 * diisi Eloquent saat menyimpan (= sekarang), `expires_at` HARUS di masa
 * depan — termasuk untuk penawaran yang ingin digambarkan "sudah lewat".
 * Penawaran kedaluwarsa di Seekitar ditandai status `rejected`, bukan
 * tanggal mundur (DATABASE.md §4.6).
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    private const ESTIMASI = [
        ['Hari ini juga', 4],
        ['Besok pagi', 18],
        ['1–2 hari kerja', 36],
        ['2–3 hari kerja', 60],
        ['Sekitar 1 minggu', 168],
        ['Langsung datang < 2 jam', 2],
        ['Sore ini setelah jam 15.00', 8],
    ];

    public function definition(): array
    {
        [$teks, $jam] = fake()->randomElement(self::ESTIMASI);

        $harga = round(fake()->numberBetween(50000, 2500000) / 500) * 500;

        // Ongkos tambahan hanya pada sebagian penawaran; nilainya terpisah
        // dari `price` supaya pengurutan "termurah" memakai TOTAL dan tidak
        // menghukum penyedia yang mencantumkan ongkos secara jujur.
        $adaOngkos = fake()->boolean(35);

        return [
            'id'         => (string) Str::uuid7(),
            'request_id' => CustomerRequest::factory(),
            'store_id'   => Store::factory()->terverifikasi(),

            'price'                => $harga,
            'additional_cost'      => $adaOngkos ? round(fake()->numberBetween(10000, 100000) / 500) * 500 : 0,
            'additional_cost_note' => $adaOngkos
                ? fake()->randomElement(['Ongkos kirim', 'Biaya transportasi teknisi', 'Biaya bongkar pasang'])
                : null,

            'estimation_time' => $teks,
            'estimated_hours' => $jam,
            'notes'           => fake()->boolean(60) ? $this->catatan() : null,

            'status' => OfferStatus::Pending,

            // WAJIB di masa depan — lihat catatan CHECK di docblock kelas.
            'expires_at' => now()->addHours(48),
        ];
    }

    public function menunggu(): static
    {
        return $this->state(fn () => [
            'status'     => OfferStatus::Pending,
            'expires_at' => now()->addHours(fake()->numberBetween(2, 48)),
        ]);
    }

    public function diterima(): static
    {
        return $this->state(['status' => OfferStatus::Accepted]);
    }

    public function ditolak(): static
    {
        return $this->state(['status' => OfferStatus::Rejected]);
    }

    /**
     * Penawaran yang sudah lewat waktu.
     *
     * Ditandai `rejected`, BUKAN dengan `expires_at` mundur: CHECK
     * offers_expiry_chk menolak baris yang kedaluwarsa sebelum dibuat, dan
     * ENUM offers.status memang tidak punya nilai 'expired'.
     */
    public function lewatWaktu(): static
    {
        return $this->state(fn () => [
            'status'     => OfferStatus::Rejected,
            'expires_at' => now()->addMinutes(1),
            'notes'      => 'Penawaran tidak direspons hingga batas waktu.',
        ]);
    }

    public function denganHarga(int $harga, int $ongkos = 0): static
    {
        return $this->state([
            'price'           => $harga,
            'additional_cost' => $ongkos,
        ]);
    }

    private function catatan(): string
    {
        return fake()->randomElement([
            'Sudah termasuk material dasar. Garansi pengerjaan 7 hari.',
            'Harga bisa nego setelah survei lokasi.',
            'Bisa dikerjakan akhir pekan tanpa biaya tambahan.',
            'Sudah biasa menangani pekerjaan serupa di area Bangil.',
            'Pembayaran bisa 50% di muka, sisanya setelah selesai.',
            'Termasuk pembersihan setelah pekerjaan selesai.',
        ]);
    }
}
