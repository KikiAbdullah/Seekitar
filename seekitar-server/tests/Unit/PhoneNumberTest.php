<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Normalisasi nomor telepon.
 *
 * Ini bukan sekadar kerapian format: tanpa normalisasi, satu orang bisa
 * membuat beberapa akun dari nomor yang sama karena `UNIQUE(users.phone)`
 * membandingkan string mentah (`Server_Implementation_Guide.md` §18A.6).
 */
class PhoneNumberTest extends TestCase
{
    /** @return array<string, array{0: ?string, 1: ?string}> */
    public static function inputs(): array
    {
        return [
            'awalan nol'         => ['08123456789', '628123456789'],
            'awalan +62'         => ['+628123456789', '628123456789'],
            'sudah 62'           => ['628123456789', '628123456789'],
            'tanpa awalan'       => ['8123456789', '628123456789'],
            'dengan spasi'       => ['0812 3456 789', '628123456789'],
            'dengan tanda hubung' => ['+62 812-3456-789', '628123456789'],
            'dengan kurung'      => ['(0812) 3456-789', '628123456789'],
            'null'               => [null, null],
            'string kosong'      => ['', null],
            'hanya spasi'        => ['   ', null],
            'bukan angka'        => ['abc', null],
        ];
    }

    #[DataProvider('inputs')]
    public function test_normalisasi(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public function test_semua_format_sama_menghasilkan_satu_nilai(): void
    {
        // Inti pencegahan akun ganda: empat cara menulis, satu hasil.
        $variants = ['08123456789', '+628123456789', '628123456789', '+62 812-3456-789'];

        $normalized = array_map(PhoneNumber::normalize(...), $variants);

        $this->assertCount(1, array_unique($normalized));
    }

    public function test_hasil_muat_di_kolom_database(): void
    {
        // users.phone VARCHAR(15)
        $this->assertLessThanOrEqual(
            PhoneNumber::MAX_LENGTH,
            strlen(PhoneNumber::normalize('08123456789012')),
        );
    }

    public function test_penyensoran_menyembunyikan_bagian_tengah(): void
    {
        $masked = PhoneNumber::mask('08123456789');

        $this->assertSame('+62 812-****-789', $masked);
        // Nomor telepon adalah data pribadi (UU PDP) — tidak boleh utuh di log.
        $this->assertStringNotContainsString('3456', $masked);
    }

    public function test_tampilan_ramah_baca(): void
    {
        $this->assertSame('+62 812-3456-789', PhoneNumber::forDisplay('08123456789'));
        $this->assertNull(PhoneNumber::forDisplay(null));
    }
}
