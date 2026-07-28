<?php

namespace Database\Factories;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Database\Factories\Support\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 *
 * TIGA ATURAN YANG TIDAK BOLEH DILANGGAR:
 *
 * 1. CHECK orders_shipping_chk — `delivery_method='delivery'` WAJIB punya
 *    `shipping_address`. Diantar tanpa alamat = paket tanpa tujuan.
 *
 * 2. OrderObserver::updating() memvalidasi SETIAP perubahan status lewat
 *    OrderStateMachine. Factory karenanya menyetel status saat PEMBUATAN
 *    (creating), bukan dengan mengubahnya setelah tersimpan — mengubah
 *    'menunggu_konfirmasi' langsung ke 'selesai' akan ditolak.
 *
 * 3. `order_number` diisi OrderObserver::creating() bila kosong, dengan
 *    urutan harian dari cache. Factory sengaja TIDAK mengisinya agar jalur
 *    penomoran resmi ikut teruji.
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $tipe    = fake()->randomElement(OrderType::cases());
        $kirim   = fake()->randomElement(DeliveryMethod::cases());
        $bayar   = fake()->randomElement(PaymentMethod::cases());
        $jumlah  = $tipe === OrderType::Product ? fake()->numberBetween(1, 5) : 1;
        $satuan  = round(fake()->numberBetween(25000, 1500000) / 500) * 500;

        return [
            'id'       => (string) Str::uuid7(),
            'buyer_id' => User::factory(),
            'store_id' => Store::factory()->terverifikasi(),

            'order_type'   => $tipe,
            'quantity'     => $jumlah,
            'total_amount' => $satuan * $jumlah,

            'status'          => OrderStatus::MenungguKonfirmasi,
            'payment_method'  => $bayar,
            'delivery_method' => $kirim,

            // CHECK orders_shipping_chk — alamat wajib bila diantar.
            'shipping_address' => $kirim === DeliveryMethod::Delivery
                ? fake('id_ID')->streetAddress().', Kec. '.Wilayah::namaKecamatan().', Kabupaten Pasuruan'
                : null,

            // Transfer butuh bukti sebelum penjual memproses.
            'payment_proof_url' => $bayar === PaymentMethod::Transfer
                ? 'payments/'.Str::uuid7().'.jpg'
                : null,
            'payment_confirmed_at' => null,

            'notes' => fake()->boolean(35)
                ? fake()->randomElement([
                    'Tolong dikirim sore hari.',
                    'Titip di pos satpam bila saya tidak di rumah.',
                    'Mohon dibungkus rapi.',
                    'Hubungi dulu sebelum berangkat.',
                ])
                : null,
        ];
    }

    /**
     * Titik tujuan antar.
     *
     * Hanya diisi untuk pesanan `delivery`; pesanan pickup tidak punya tujuan.
     * Tanpa kolom ini penjual tak bisa dinavigasikan — `shipping_address`
     * hanyalah teks bebas (DATABASE.md §4.7).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Order $order): void {
            if ($order->delivery_method === DeliveryMethod::Delivery
                && ! isset($order->getAttributes()['shipping_location'])) {
                [$lat, $lng] = Wilayah::acak();
                $order->setLocation($lat, $lng, 'shipping_location');
            }
        });
    }

    public function diantar(): static
    {
        return $this->state(fn () => [
            'delivery_method'  => DeliveryMethod::Delivery,
            'shipping_address' => fake('id_ID')->streetAddress()
                                  .', Kec. '.Wilayah::namaKecamatan().', Kabupaten Pasuruan',
        ]);
    }

    public function diambil(): static
    {
        return $this->state([
            'delivery_method'  => DeliveryMethod::Pickup,
            'shipping_address' => null,
        ]);
    }

    public function cod(): static
    {
        return $this->state([
            'payment_method'    => PaymentMethod::Cod,
            'payment_proof_url' => null,
        ]);
    }

    public function transfer(bool $sudahBayar = true): static
    {
        return $this->state(fn () => [
            'payment_method'       => PaymentMethod::Transfer,
            'payment_proof_url'    => 'payments/'.Str::uuid7().'.jpg',
            'payment_confirmed_at' => $sudahBayar ? now()->subHours(fake()->numberBetween(1, 48)) : null,
        ]);
    }

    public function menungguKonfirmasi(): static
    {
        return $this->state(['status' => OrderStatus::MenungguKonfirmasi]);
    }

    public function diproses(): static
    {
        return $this->state(['status' => OrderStatus::Diproses]);
    }

    public function dikirim(): static
    {
        return $this->state(['status' => OrderStatus::Dikirim]);
    }

    /**
     * Pesanan selesai.
     *
     * `completed_at` WAJIB terisi: `Order::acceptsReview()` memakainya untuk
     * jendela ulasan 7 hari, dan tanpa itu tidak ada ulasan yang sah.
     */
    public function selesai(?int $hariLalu = null): static
    {
        $hari = $hariLalu ?? fake()->numberBetween(0, 40);

        return $this->state(fn () => [
            'status'       => OrderStatus::Selesai,
            'completed_at' => now()->subDays($hari),
        ]);
    }

    /** Selesai DAN masih dalam jendela ulasan 7 hari. */
    public function selesaiBaru(): static
    {
        return $this->selesai(fake()->numberBetween(0, 5));
    }

    public function dibatalkan(): static
    {
        return $this->state(fn () => [
            'status'        => OrderStatus::Dibatalkan,
            'cancelled_at'  => now()->subDays(fake()->numberBetween(1, 30)),
            'cancel_reason' => fake()->randomElement([
                'Stok habis di toko.',
                'Pembeli membatalkan sebelum diproses.',
                'Alamat tidak terjangkau layanan antar.',
                'Pembeli tidak merespons konfirmasi.',
            ]),
        ]);
    }

    public function sengketa(): static
    {
        return $this->state(['status' => OrderStatus::Dispute]);
    }
}
