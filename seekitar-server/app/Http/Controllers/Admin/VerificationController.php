<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectVerificationRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Antrian verifikasi — pengguna (3 tahap) & pengajuan toko.
 *
 * DUA HALAMAN, DUA IZIN. `verify-users` dan `verify-stores` adalah permission
 * terpisah (§6.2), jadi menggabungkannya dalam satu halaman akan memaksa admin
 * yang hanya punya salah satunya melihat data yang bukan haknya.
 *
 * Verifikasi pengguna DUA TAHAP, masing-masing di-stempel admin+waktu:
 *   1. nomor HP   2. KTP & NIK  (→ level Verified)
 * Tiap klik tombol "Verifikasi" hanya menyelesaikan satu tahap — admin dipaksa
 * memeriksa ulang berkas antar-tahap.
 *
 * SLA peninjauan 1×24 jam (PRD §5.3.2) — karena itu urutannya SELALU dari
 * pengajuan terlama, bukan terbaru: yang paling lama menunggu adalah yang
 * paling dekat melanggar SLA.
 */
class VerificationController extends Controller
{
    /**
     * Antrian verifikasi pengguna (semua tahap dalam satu tabel).
     *
     * Definisi antriannya BUKAN di sini, melainkan scope
     * `User::pendingVerification()` — lencana sidebar memakai definisi yang
     * sama, dan dua sumber kebenaran akan membuat angkanya tidak cocok.
     */
    public function users(): View
    {
        return view('admin.verifications.users', [
            'pending' => User::query()
                // Nama admin pemverifikasi tiap tahap ikut dimuat — kolom
                // tabel menampilkannya, dan N+1 di tabel 20 baris tidak perlu.
                ->with(['verified1By:id,name', 'verified2By:id,name'])
                ->pendingVerification()
                ->orderBy('ktp_submitted_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /** Antrian pengajuan toko. */
    public function stores(): View
    {
        return view('admin.verifications.stores', [
            'pending' => Store::query()
                // Relasi pemilik toko bernama owner(), bukan user().
                // verified2_at ikut dipilih: syaratnya kini dibaca dari
                // stempel KTP (kolom level sudah dihapus).
                ->with(['owner:id,name,phone,verified2_at'])
                // latitude/longitude untuk peta kecil pada modal — kolom
                // POINT mentah berupa biner WKB yang tidak berguna di Blade.
                ->withCoordinates()
                ->where('verification_status', VerificationStatus::Pending)
                ->orderBy('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /**
     * Memverifikasi SATU tahap berikutnya (1 → 2).
     *
     * Tahapnya ditentukan SERVER dari data terkini, bukan dari tombol yang
     * kebetulan diklik: dua admin bisa membuka baris yang sama, dan klik
     * kedua tidak boleh menimpa stempel yang sudah ada. Karena itu baris
     * dikunci (lockForUpdate) di dalam transaksi sebelum dibaca ulang.
     */
    public function verifyUser(Request $request, User $user): RedirectResponse
    {
        $adminId = $request->user()->id;

        $tahap = DB::transaction(function () use ($user, $adminId): ?int {
            $antrian = User::lockForUpdate()->findOrFail($user->id);

            $tahap = $antrian->nextVerificationStep();
            if ($tahap === null) {
                return null;
            }

            if ($tahap === 1) {
                $antrian->verified1_by = $adminId;
                $antrian->verified1_at = now();
            } else {
                // Stempel tahap 2 ADALAH kenaikan levelnya: sejak kolom
                // verification_level dihapus, "level 2" murni turunan dari
                // verified2_at — menulis keduanya akan membuka peluang
                // dua sumber kebenaran berbeda pendapat.
                $antrian->verified2_by        = $adminId;
                $antrian->verified2_at        = now();
                $antrian->ktp_rejected_reason = null;
            }

            $antrian->save();

            return $tahap;
        });

        if ($tahap === null) {
            // Bisa terjadi bila admin lain lebih dulu menyelesaikan tahap
            // terakhirnya — bukan kesalahan, cukup diberi tahu.
            return back()->with('error', "Tidak ada tahap yang menunggu untuk {$user->name}.");
        }

        return back()->with('success',
            "Verifikasi tahap {$tahap} (".User::verificationStepLabel($tahap).") {$user->name} berhasil.");
    }

    /**
     * Berkas privat antrian pengguna: KTP atau selfie.
     *
     * Disimpan di disk privat dan TIDAK pernah dipetakan ke URL publik.
     * Satu-satunya jalan melihatnya adalah route berizin `verify-users`
     * ini, dan `kind` sudah dibatasi whereIn pada route — parameter bebas
     * tidak bisa dipakai mengintip kolom lain. no-store: berkas identitas
     * tidak boleh tersangkut di cache perantara.
     */
    public function media(User $user, string $kind): StreamedResponse
    {
        $path = match ($kind) {
            'ktp'    => $user->ktp_image,
            'selfie' => $user->selfie_image,
        };

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')
            ->response($path)
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Menolak pengajuan KTP.
     *
     * `ktp_submitted_at` dikosongkan supaya barisnya keluar dari antrian dan
     * pengguna bisa mengirim ulang berkas. Stempel tahap yang SUDAH lolos
     * tidak dicabut: tahap 1 memeriksa nomor HP yang tetap valid meski foto
     * KTP-nya ditolak — pengguna hanya mengulang dari tahap yang gagal.
     */
    public function rejectUser(RejectVerificationRequest $request, User $user): RedirectResponse
    {
        $user->ktp_rejected_reason = $request->reason();
        $user->ktp_submitted_at    = null;
        $user->save();

        return back()->with('success', "Verifikasi {$user->name} ditolak.");
    }

    /**
     * Menyetujui pengajuan toko.
     *
     * SYARAT PERSETUJUAN (aturan 2): (1) pemilik SUDAH terverifikasi —
     * nomor HP + KTP, dibaca dari stempel verified2_at-nya; (2) berkas
     * tokonya memenuhi syarat — minimal foto etalase benar-benar terunggah
     * (getRawOriginal: placeholder hiasan bukan bukti). Keduanya dicek
     * ULANG di sini, bukan cukup mengandalkan StorePolicy::create saat
     * pengajuan: verifikasi pengguna bisa dicabut keadaannya SETELAH toko
     * masuk antrian, dan persetujuan tidak boleh mengesahkan toko yang
     * syaratnya sudah gugur.
     *
     * Begitu stempel persetujuan ditulis, pemilik OTOMATIS tampil sebagai
     * Level 3 · Usaha Terverifikasi — level adalah turunan dari toko
     * verified, tidak ada kolom level untuk ditulis lagi.
     */
    public function approveStore(Request $request, Store $store): RedirectResponse
    {
        $hasil = DB::transaction(function () use ($store, $request): string {
            // Baris dikunci sebelum ditulis: dua admin bisa menekan Setujui
            // nyaris bersamaan, dan keduanya harus berakhir di kondisi yang
            // sama — bukan saling menimpa stempel.
            $toko = Store::lockForUpdate()->findOrFail($store->id);

            // Stempel persetujuan tulis-sekali: antrian memang hanya memuat
            // toko pending, tapi POST manual/dobel tidak boleh menimpa
            // verified_by/verified_at yang sudah tercatat.
            if ($toko->verification_status !== VerificationStatus::Pending) {
                return 'bukan-antrian';
            }

            $pemilik = User::lockForUpdate()->find($toko->user_id);

            // Syarat (1): pemilik terverifikasi (no HP + KTP). NULL pun
            // ditolak — toko yatim tidak layak disahkan apa pun sebabnya.
            if ($pemilik === null || ! $pemilik->canOpenStore()) {
                return 'pemilik-belum-terverifikasi';
            }

            // Syarat (2): berkas toko memenuhi syarat — foto etalase yang
            // dinilai admin harus foto yang BENAR-BENAR diunggah pemilik.
            // Aksesor photo menjatuhkan nilai kosong ke placeholder hiasan,
            // jadi di sini wajib membaca kolom mentahnya.
            if (empty($toko->getRawOriginal('photo'))) {
                return 'foto-belum-diunggah';
            }

            $toko->verification_status = VerificationStatus::Verified;
            $toko->rejected_reason     = null;
            $toko->verified_at         = now();
            $toko->verified_by         = $request->user()->id;
            $toko->save();

            return 'disetujui';
        });

        return match ($hasil) {
            'bukan-antrian' => back()->with('error',
                "Toko {$store->name} sudah diproses sebelumnya — stempel persetujuan tidak bisa ditimpa."),
            'pemilik-belum-terverifikasi' => back()->with('error',
                "Toko {$store->name} belum bisa diverifikasi: pemiliknya belum terverifikasi (nomor HP + KTP). Selesaikan dulu di antrian Verifikasi Pengguna."),
            'foto-belum-diunggah' => back()->with('error',
                "Toko {$store->name} belum bisa diverifikasi: foto toko belum diunggah pemilik. Tolak pengajuannya agar pemilik memperbaiki."),
            default => back()->with('success', "Toko {$store->name} disetujui."),
        };
    }

    public function rejectStore(RejectVerificationRequest $request, Store $store): RedirectResponse
    {
        $ditolak = DB::transaction(function () use ($request, $store): bool {
            $toko = Store::lockForUpdate()->findOrFail($store->id);

            // Penolakan hanya sah dari antrian — sebagaimana persetujuan.
            // Menolak toko yang SUDAH disetujui akan mengubah statusnya
            // sambil menyisakan stempel verified_*: keadaan kontradiktif
            // yang tidak punya makna alur (pencabutan belum jadi fitur).
            if ($toko->verification_status !== VerificationStatus::Pending) {
                return false;
            }

            $toko->verification_status = VerificationStatus::Rejected;
            $toko->rejected_reason     = $request->reason();
            $toko->save();

            return true;
        });

        if (! $ditolak) {
            return back()->with('error',
                "Toko {$store->name} sudah diproses sebelumnya — tidak bisa ditolak lagi.");
        }

        return back()->with('success', "Toko {$store->name} ditolak.");
    }
}
