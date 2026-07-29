<?php

namespace App\Services;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * SATU-SATUNYA tempat kedudukan toko berubah (panel web & API admin).
 *
 * KENAPA SERVICE, BUKAN TIGA SALINAN
 * ----------------------------------
 * Logika ini semula hidup tiga kali: antrian verifikasi web, aksi cepat
 * di tabel toko, dan endpoint API admin. Tiga salinan aturan yang sama
 * PASTI menyimpang — dan sudah pernah: aksi cepat di index menyetujui
 * toko tanpa memeriksa ulang syarat, padahal antrian memeriksanya.
 * Di sini aturannya ditulis sekali dan dipanggil semua kanal.
 *
 * SYARAT PERSETUJUAN (tidak bisa ditawar):
 *   1. Tokonya BENAR-BENAR antre (status pending) — stempel tulis-sekali
 *      tidak pernah ditimpa;
 *   2. Pemiliknya SUDAH terverifikasi identitasnya (canOpenStore) — dan
 *      diperiksa ULANG saat ini juga, bukan cukup saat pengajuan: orang
 *      bisa diblokir selama tokonya menunggu;
 *   3. Foto tampak depan benar-benar terunggah — dibaca dari kolom mentah
 *      (getRawOriginal), bukan aksesor yang menjatuhkannya ke placeholder.
 */
class VerifikasiTokoService
{
    /**
     * Hasil persetujuan:
     *  - 'disetujui'                  — stempel verified_* ditulis
     *  - 'bukan-antrian'              — sudah diproses sebelumnya
     *  - 'pemilik-belum-terverifikasi'— syarat (2) gugur
     *  - 'foto-belum-diunggah'        — syarat (3) gugur
     */
    public function setujui(Store $store, int|string $adminId): string
    {
        return DB::transaction(function () use ($store, $adminId): string {
            // Baris dikunci sebelum ditulis: dua admin bisa menekan Setujui
            // nyaris bersamaan, dan keduanya harus berakhir di kondisi yang
            // sama — bukan saling menimpa stempel.
            $toko = Store::lockForUpdate()->findOrFail($store->getKey());

            if ($toko->status !== StoreStatus::Pending) {
                return 'bukan-antrian';
            }

            // Pemilik dikunci juga: ia bisa sedang DIPROSES blokir persis
            // saat ini — tanpa kunci, toko bisa disetujui sesaat sebelum
            // blokir pemilik menyeretnya, menyisakan keadaan kontradiktif.
            $pemilik = User::lockForUpdate()->find($toko->user_id);

            // NULL pun ditolak — toko yatim tidak layak disahkan apa pun
            // sebabnya.
            if ($pemilik === null || ! $pemilik->canOpenStore()) {
                return 'pemilik-belum-terverifikasi';
            }

            if (empty($toko->getRawOriginal('photo'))) {
                return 'foto-belum-diunggah';
            }

            $toko->status          = StoreStatus::Verified;
            $toko->verified_at     = now();
            $toko->verified_by     = $adminId;
            // Jejak penolakan dibersihkan di sini — di tabel ia tetap
            // tampak sebagai "pengajuan ulang" sampai detik ini.
            $toko->rejected_at     = null;
            $toko->rejected_by     = null;
            $toko->rejected_reason = null;
            $toko->save();

            return 'disetujui';
        });
    }

    /**
     * Menolak pengajuan — hanya sah dari antrian.
     *
     * Menolak toko yang SUDAH disetujui akan menyisakan stempel verified_*
     * pada kedudukan rejected: keadaan kontradiktif tanpa makna alur
     * (pencabutan belum jadi fitur). Alasan WAJIB sudah divalidasi pemanggil
     * — tanpa itu pemilik mengajukan ulang berkas yang sama.
     *
     * @return bool true bila penolakan ditulis, false bila bukan antrian lagi
     */
    public function tolak(Store $store, int|string $adminId, string $reason): bool
    {
        return DB::transaction(function () use ($store, $adminId, $reason): bool {
            $toko = Store::lockForUpdate()->findOrFail($store->getKey());

            if ($toko->status !== StoreStatus::Pending) {
                return false;
            }

            $toko->status          = StoreStatus::Rejected;
            $toko->rejected_at     = now();
            $toko->rejected_by     = $adminId;
            $toko->rejected_reason = $reason;
            $toko->save();

            return true;
        });
    }

    /**
     * Menyeret SEMUA toko seseorang ke kedudukan blocked — atau
     * mengembalikannya.
     *
     * Dipanggil dari blokir pengguna (web & API): orang yang bermasalah
     * membuat tokonya ikut bermasalah. `is_active` sengaja TIDAK diusik —
     * itu saklar operasional milik toko, dan pengaktifan pasca-blokir
     * adalah keputusan terpisah yang layak ditinjau ulang.
     *
     * @return int berapa toko yang ikut berubah
     */
    public function seretBersamaPemilik(User $pemilik, bool $memblokir, ?int $adminId = null, ?string $alasan = null): int
    {
        $berubah = 0;

        // Per baris, bukan update massal: kedudukan pulih diturunkan PER
        // TOKO dari jejaknya sendiri (verified_at / rejected_at), dan
        // cascadeOnUpdate massal tidak bisa menjalankan derivasi itu.
        foreach ($pemilik->stores()->get() as $toko) {
            if ($memblokir && $toko->status !== StoreStatus::Blocked) {
                $toko->status         = StoreStatus::Blocked;
                $toko->blocked_at     = now();
                $toko->blocked_by     = $adminId;
                $toko->blocked_reason = $alasan !== null && $alasan !== ''
                    ? "Pemilik diblokir — {$alasan}"
                    : 'Pemilik diblokir.';
                $toko->save();
                $berubah++;
            } elseif (! $memblokir && $toko->status === StoreStatus::Blocked) {
                $toko->status         = $toko->statusSebelumDiblokir();
                $toko->blocked_at     = null;
                $toko->blocked_by     = null;
                $toko->blocked_reason = null;
                $toko->save();
                $berubah++;
            }
        }

        return $berubah;
    }
}
