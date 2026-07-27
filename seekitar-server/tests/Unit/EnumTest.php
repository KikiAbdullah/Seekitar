<?php

namespace Tests\Unit;

use App\Enums\DeliveryMethod;
use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ReviewDirection;
use App\Enums\StoreType;
use App\Enums\VerificationLevel;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_order_status_cocok_dengan_skema_database(): void
    {
        $this->assertSame(
            ['menunggu_konfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan', 'dispute'],
            array_map(fn ($c) => $c->value, OrderStatus::cases()),
            'orders.status di DATABASE.md hanya punya 6 nilai ini'
        );
    }

    public function test_order_type_identik_dengan_listing_type(): void
    {
        // Nilainya disalin langsung saat pesanan dibuat dari listing.
        $this->assertSame(
            array_map(fn ($c) => $c->value, ListingType::cases()),
            array_map(fn ($c) => $c->value, OrderType::cases())
        );

        $this->assertSame(
            OrderType::Product,
            OrderType::fromListingType(ListingType::Product)
        );
    }

    public function test_store_type_memakai_bentuk_jamak(): void
    {
        // SET menampung kombinasi, karena itu jamak — berbeda dari ListingType.
        $this->assertSame(['goods', 'services', 'rental'],
            array_map(fn ($c) => $c->value, StoreType::cases()));
    }

    public function test_label_status_bergantung_konteks_pesanan(): void
    {
        $this->assertSame('Siap Diambil',
            OrderStatus::Dikirim->contextualLabel(OrderType::Product, DeliveryMethod::Pickup));

        $this->assertSame('Dikirim / Siap Diambil',
            OrderStatus::Dikirim->contextualLabel(OrderType::Product, DeliveryMethod::Delivery));

        // Sewa yang diambil sendiri tetap "Disewa" — cabang rental harus
        // diperiksa sebelum cabang pickup.
        $this->assertSame('Disewa',
            OrderStatus::Dikirim->contextualLabel(OrderType::Rental, DeliveryMethod::Pickup));

        $this->assertSame('Dalam Pengerjaan',
            OrderStatus::Diproses->contextualLabel(OrderType::Service, DeliveryMethod::Pickup));
    }

    public function test_verification_level_hanya_1_sampai_3(): void
    {
        $this->assertCount(3, VerificationLevel::cases());
        $this->assertNull(VerificationLevel::tryFrom(0), 'tidak ada level 0');
        $this->assertNull(VerificationLevel::tryFrom(4), 'level 4 masuk Fase 2');

        $this->assertFalse(VerificationLevel::Basic->canOpenStore());
        $this->assertTrue(VerificationLevel::Verified->canOpenStore());
    }

    public function test_status_final_tidak_bisa_berubah(): void
    {
        $this->assertTrue(OrderStatus::Selesai->isFinal());
        $this->assertTrue(OrderStatus::Dibatalkan->isFinal());
        $this->assertFalse(OrderStatus::Diproses->isFinal());
    }

    public function test_hanya_ulasan_pembeli_yang_mengubah_rating_toko(): void
    {
        $this->assertTrue(ReviewDirection::BuyerToStore->affectsStoreRating());
        $this->assertFalse(ReviewDirection::StoreToBuyer->affectsStoreRating());
    }

    public function test_nilai_liar_ditolak(): void
    {
        // Label UI bukan nilai ENUM (PRD §5.4).
        $this->assertNull(OrderStatus::tryFrom('siap_diambil'));
        $this->assertNull(OrderStatus::tryFrom('dijadwalkan'));
        $this->assertNull(OrderStatus::tryFrom('dikembalikan'));

        // 'goods' milik StoreType, bukan OrderType.
        $this->assertNull(OrderType::tryFrom('goods'));
    }
}
