<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Services\OrderStateMachine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Matriks transisi lengkap — termasuk yang harus DITOLAK.
 *
 * `Server_Implementation_Guide.md` §20.2 secara khusus meminta test ini
 * mencakup transisi tidak sah, bukan hanya jalur bahagia.
 */
class OrderStateMachineTest extends TestCase
{
    private OrderStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new OrderStateMachine();
    }

    /** @return array<string, array{0: OrderStatus, 1: OrderStatus, 2: bool}> */
    public static function transitions(): array
    {
        $menunggu = OrderStatus::MenungguKonfirmasi;
        $proses   = OrderStatus::Diproses;
        $kirim    = OrderStatus::Dikirim;
        $selesai  = OrderStatus::Selesai;
        $batal    = OrderStatus::Dibatalkan;
        $dispute  = OrderStatus::Dispute;

        return [
            // Jalur normal
            'menunggu -> diproses'   => [$menunggu, $proses, true],
            'diproses -> dikirim'    => [$proses, $kirim, true],
            'dikirim -> selesai'     => [$kirim, $selesai, true],

            // Pembatalan
            'menunggu -> dibatalkan' => [$menunggu, $batal, true],
            'diproses -> dibatalkan' => [$proses, $batal, true],

            // Dispute bisa diangkat dari tahap mana pun yang masih berjalan
            'menunggu -> dispute'    => [$menunggu, $dispute, true],
            'diproses -> dispute'    => [$proses, $dispute, true],
            'dikirim -> dispute'     => [$kirim, $dispute, true],

            // Penyelesaian dispute oleh admin
            'dispute -> selesai'     => [$dispute, $selesai, true],
            'dispute -> dibatalkan'  => [$dispute, $batal, true],

            // ─── yang HARUS ditolak ───
            // Melompati tahap
            'menunggu -> dikirim'    => [$menunggu, $kirim, false],
            'menunggu -> selesai'    => [$menunggu, $selesai, false],
            'diproses -> selesai'    => [$proses, $selesai, false],

            // Mundur
            'diproses -> menunggu'   => [$proses, $menunggu, false],
            'dikirim -> diproses'    => [$kirim, $proses, false],

            // Status akhir tidak boleh berubah
            'selesai -> dibatalkan'  => [$selesai, $batal, false],
            'selesai -> dispute'     => [$selesai, $dispute, false],
            'dibatalkan -> diproses' => [$batal, $proses, false],
            'dibatalkan -> selesai'  => [$batal, $selesai, false],

            // Sudah dikirim, pembatalan sepihak tidak lagi boleh
            'dikirim -> dibatalkan'  => [$kirim, $batal, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_matriks_transisi(OrderStatus $from, OrderStatus $to, bool $allowed): void
    {
        $this->assertSame(
            $allowed,
            $this->sm->canTransition($from, $to),
            sprintf('%s -> %s seharusnya %s', $from->value, $to->value, $allowed ? 'boleh' : 'ditolak'),
        );
    }

    public function test_status_yang_sama_selalu_boleh(): void
    {
        // Menyimpan model tanpa mengubah status bukan transisi.
        foreach (OrderStatus::cases() as $status) {
            $this->assertTrue($this->sm->canTransition($status, $status), $status->value);
        }
    }

    public function test_transisi_tidak_sah_melempar_exception_yang_menjelaskan(): void
    {
        $this->expectException(InvalidOrderTransitionException::class);
        // Pesannya harus menyebut apa yang SEBENARNYA diizinkan.
        $this->expectExceptionMessageMatches('/tidak diizinkan.*diproses/s');

        $this->sm->assertCanTransition(OrderStatus::MenungguKonfirmasi, OrderStatus::Selesai);
    }

    public function test_status_akhir_tidak_punya_jalan_keluar(): void
    {
        $this->assertTrue($this->sm->isFinal(OrderStatus::Selesai));
        $this->assertTrue($this->sm->isFinal(OrderStatus::Dibatalkan));

        $this->assertFalse($this->sm->isFinal(OrderStatus::Dispute));
        $this->assertFalse($this->sm->isFinal(OrderStatus::MenungguKonfirmasi));
    }

    public function test_setiap_status_terdaftar_di_matriks(): void
    {
        // Menambah nilai ENUM tanpa mendefinisikan transisinya akan membuat
        // pesanan tersangkut — allowedFrom() mengembalikan array kosong dan
        // status itu diam-diam menjadi status akhir.
        foreach (OrderStatus::cases() as $status) {
            if (in_array($status, [OrderStatus::Selesai, OrderStatus::Dibatalkan], true)) {
                continue;   // memang sengaja final
            }

            $this->assertNotEmpty(
                $this->sm->allowedFrom($status),
                "Status '{$status->value}' tidak punya transisi keluar — pesanan akan tersangkut."
            );
        }
    }
}
