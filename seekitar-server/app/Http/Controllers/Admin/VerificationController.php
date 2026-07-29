<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationLevel;
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
                // verification_level pemilik ikut dimuat: syarat mengajukan
                // toko adalah KTP level 2, dan admin perlu memastikannya.
                ->with('owner:id,name,phone,verification_level')
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
                $antrian->verified2_by        = $adminId;
                $antrian->verified2_at        = now();
                $antrian->verification_level  = VerificationLevel::Verified;
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
     * Menyetujui pengajuan toko SEKALIGUS menaikkan pemiliknya ke Level 3.
     *
     * Level 3 "Usaha Terverifikasi" artinya usahanya lolos tinjauan — persis
     * yang disahkan klik ini (foto tempat usaha + koordinat, PRD §5.3.2).
     * Tanpa kenaikan otomatis di sini TIDAK ADA alur apa pun yang menghasilkan
     * Level 3: antrian pengguna berhenti di Level 2, sehingga lencana Pro di
     * halaman pengguna tidak pernah benar-benar muncul dari data.
     *
     * Kenaikannya SATU ARAH — penolakan toko sesudahnya tidak menurunkan
     * level. Alasannya: Pro yang diberikan manual (Sunting Pengguna) tak bisa
     * dibedakan dari Pro hasil persetujuan toko tanpa kolom penanda tambahan,
     * jadi pencabutan dibiarkan sebagai keputusan manusia, bukan heuristik
     * mesin yang berisiko mencabut lencana yang salah.
     */
    public function approveStore(Request $request, Store $store): RedirectResponse
    {
        $pemilikNaik = DB::transaction(function () use ($store, $request): bool {
            // Baris dikunci sebelum ditulis: dua admin bisa menekan Setujui
            // nyaris bersamaan, dan keduanya harus berakhir di kondisi yang
            // sama — bukan saling menimpa stempel.
            $toko = Store::lockForUpdate()->findOrFail($store->id);

            $toko->verification_status = VerificationStatus::Verified;
            $toko->rejected_reason     = null;
            $toko->verified_at         = now();
            $toko->verified_by         = $request->user()->id;
            $toko->save();

            $pemilik = User::lockForUpdate()->find($toko->user_id);

            // !== Pro berarti levelnya 1 atau 2 (rentangnya memang hanya
            // 1–3), jadi penulisan ini tidak pernah menurunkan siapa pun.
            if ($pemilik !== null && $pemilik->verification_level !== VerificationLevel::Pro) {
                $pemilik->verification_level = VerificationLevel::Pro;
                $pemilik->save();

                return true;
            }

            return false;
        });

        $pesan = "Toko {$store->name} disetujui.";
        if ($pemilikNaik) {
            $pesan .= ' Pemilik naik ke Level 3 · Usaha Terverifikasi.';
        }

        return back()->with('success', $pesan);
    }

    public function rejectStore(RejectVerificationRequest $request, Store $store): RedirectResponse
    {
        $store->verification_status = VerificationStatus::Rejected;
        $store->rejected_reason     = $request->reason();
        $store->save();

        return back()->with('success', "Toko {$store->name} ditolak.");
    }
}
