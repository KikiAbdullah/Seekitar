<?php

namespace Database\Factories;

use App\Enums\ReviewDirection;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Review>
 *
 * CHECK reviews_store_direction_chk (DATABASE.md §4.8):
 *   direction='buyer_to_store' -> store_id WAJIB terisi
 *   direction='store_to_buyer' -> store_id WAJIB NULL
 *
 * Inilah yang mencegah penilaian penjual terhadap pembeli ikut menaikkan
 * rating toko. Karena itu `direction` dan `store_id` tidak pernah diacak
 * terpisah di factory ini.
 *
 * UNIQUE (order_id, direction): satu ulasan per ARAH per pesanan — bukan satu
 * per pesanan, karena PRD §5.5 mewajibkan penilaian dua arah.
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /** Komentar pembeli, dikelompokkan per rentang rating. */
    private const KOMENTAR_KE_TOKO = [
        'tinggi' => [
            'Barang sesuai deskripsi, pengiriman cepat. Recommended!',
            'Pelayanan ramah, harga bersaing. Pasti order lagi.',
            'Tukangnya datang tepat waktu dan hasilnya rapi.',
            'Responsif dibalas cepat, prosesnya gampang.',
            'Kualitas bagus, packing aman. Terima kasih.',
            'Sudah langganan di sini, tidak pernah mengecewakan.',
        ],
        'sedang' => [
            'Barang oke, tapi pengiriman agak lama dari perkiraan.',
            'Cukup memuaskan, hanya saja balasan chat lambat.',
            'Hasil kerjanya bagus, sayang datangnya telat 2 jam.',
            'Sesuai harga. Tidak istimewa tapi tidak mengecewakan.',
        ],
        'rendah' => [
            'Barang tidak sesuai foto, agak kecewa.',
            'Sudah bayar tapi konfirmasi lama sekali.',
            'Pengerjaan kurang rapi, harus dipanggil ulang.',
            'Penjual sulit dihubungi setelah transaksi.',
        ],
    ];

    /** Komentar penjual tentang pembeli. */
    private const KOMENTAR_KE_PEMBELI = [
        'Pembeli komunikatif, pembayaran lancar. Terima kasih.',
        'Alamat jelas dan mudah ditemukan.',
        'Ramah dan kooperatif saat pengerjaan.',
        'Pembayaran tepat waktu, semoga langganan.',
        'Sedikit sulit dihubungi saat pengantaran.',
    ];

    public function definition(): array
    {
        // Mayoritas ulasan positif — sebaran realistis marketplace lokal,
        // sekaligus membuat rating_avg tidak terpaku di angka 3.
        $rating = fake()->randomElement([5, 5, 5, 5, 4, 4, 4, 3, 2, 1]);

        return [
            'id'          => (string) Str::uuid7(),
            'order_id'    => Order::factory()->selesai(),
            'reviewer_id' => User::factory(),
            'reviewee_id' => User::factory(),
            'store_id'    => Store::factory(),
            'direction'   => ReviewDirection::BuyerToStore,
            'rating'      => $rating,
            'comment'     => $this->komentarToko($rating),
            'created_at'  => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    /**
     * Ulasan pembeli untuk toko — SATU-SATUNYA arah yang memengaruhi rating.
     */
    public function keToko(Store $store, User $pembeli, User $pemilik): static
    {
        return $this->state(fn (array $atribut) => [
            'direction'   => ReviewDirection::BuyerToStore,
            'store_id'    => $store->id,
            'reviewer_id' => $pembeli->id,
            'reviewee_id' => $pemilik->id,
            'comment'     => $this->komentarToko((int) ($atribut['rating'] ?? 5)),
        ]);
    }

    /**
     * Ulasan penjual untuk pembeli.
     *
     * `store_id` WAJIB NULL di sini — CHECK reviews_store_direction_chk
     * menolak baris store_to_buyer yang membawa store_id.
     */
    public function kePembeli(User $pemilik, User $pembeli): static
    {
        return $this->state(fn () => [
            'direction'   => ReviewDirection::StoreToBuyer,
            'store_id'    => null,
            'reviewer_id' => $pemilik->id,
            'reviewee_id' => $pembeli->id,
            'comment'     => fake()->randomElement(self::KOMENTAR_KE_PEMBELI),
        ]);
    }

    public function bintang(int $rating): static
    {
        return $this->state(fn () => [
            'rating'  => $rating,
            'comment' => $this->komentarToko($rating),
        ]);
    }

    /** Ulasan tanpa komentar — kolomnya NULL-able dan alurnya harus terwakili. */
    public function tanpaKomentar(): static
    {
        return $this->state(['comment' => null]);
    }

    private function komentarToko(int $rating): string
    {
        $kelompok = match (true) {
            $rating >= 4 => 'tinggi',
            $rating == 3 => 'sedang',
            default      => 'rendah',
        };

        return fake()->randomElement(self::KOMENTAR_KE_TOKO[$kelompok]);
    }
}
