<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/** Alasan laporan — nilainya dari API_DOCUMENTATION.md §9.1. */
enum DisputeReason: string
{
    use HasValues;

    case BarangTidakSesuai      = 'barang_tidak_sesuai';
    case JasaTidakProfesional   = 'jasa_tidak_profesional';
    case PenyediaTidakResponsif = 'penyedia_tidak_responsif';
    case PembeliFiktif          = 'pembeli_fiktif';
    case Lainnya                = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::BarangTidakSesuai      => 'Barang tidak sesuai',
            self::JasaTidakProfesional   => 'Jasa tidak selesai / tidak profesional',
            self::PenyediaTidakResponsif => 'Penyedia tidak responsif',
            self::PembeliFiktif          => 'Pembeli fiktif / tidak bayar',
            self::Lainnya                => 'Lainnya',
        };
    }

    /** Alasan 'lainnya' wajib disertai penjelasan (CHECK di DATABASE.md §4.9). */
    public function requiresDescription(): bool
    {
        return $this === self::Lainnya;
    }
}
