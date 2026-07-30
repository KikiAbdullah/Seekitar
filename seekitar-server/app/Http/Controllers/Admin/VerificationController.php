<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectVerificationRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\VerifikasiTokoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Antrian verifikasi — pengguna (SATU verifikasi) & pengajuan toko.
 *
 * DUA HALAMAN, DUA IZIN. `verify-users` dan `verify-stores` adalah permission
 * terpisah (§6.2), jadi menggabungkannya dalam satu halaman akan memaksa admin
 * yang hanya punya salah satunya melihat data yang bukan haknya.
 *
 * Verifikasi pengguna SATU KLIK: admin meninjau wajah, KTP, alamat, dan
 * koordinat di dalam modal, lalu menekan Setujui — stempel verified_*
 * ditulis pasangan dan statusnya berangkat ke terverifikasi. Nomor HP tidak
 * ikut dinilai: OTP yang hanya dikirim ke nomornya sendiri sudah merupakan
 * bukti pemilikannya, lebih kuat daripada mata manusia.
 *
 * SLA peninjauan 1×24 jam (PRD §5.3.2) — karena itu urutannya SELALU dari
 * pengajuan terlama, bukan terbaru: yang paling lama menunggu adalah yang
 * paling dekat melanggar SLA.
 */
class VerificationController extends Controller
{
    public function __construct(private readonly VerifikasiTokoService $verifikasiToko) {}

    /**
     * Antrian verifikasi identitas pengguna.
     *
     * Definisi antriannya BUKAN di sini, melainkan scope
     * `User::pendingVerification()` — lencana sidebar memakai definisi yang
     * sama, dan dua sumber kebenaran akan membuat angkanya tidak cocok.
     */
    public function users(): View
    {
        return view('admin.verifications.users', [
            // Koordinat pemohon ikut dibaca: modal memeriksa titik domisili
            // terhadap alamatnya — kolom POINT mentah adalah WKB biner.
            // rejectedBy ikut dimuat: tabel & modal menampilkan konteks
            // "pengajuan ulang" tanpa N+1 di tiap baris.
            'pending' => User::query()
                ->withCoordinates()
                ->with(['rejectedBy:id,name'])
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
                // Antrian HANYA berisi toko yang pemiliknya SUDAH
                // terverifikasi identitas — definisi bersamanya hidup di
                // Store::scopePendingVerification (lencana sidebar memakai
                // definisi yang sama; dua sumber akan membuat angkanya
                // tidak cocok).
                // verified_at pemilik ikut dipilih: badge "pemilik layak"
                // di modal dibaca dari stempel itu.
                ->with(['owner:id,name,phone,verified_at,address'])
                ->pendingVerification()
                ->orderBy('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /**
     * SATU persetujuan = identitas pengguna terverifikasi.
     *
     * Admin sudah dipaksa mencentang SOP pemeriksaan (wajah ↔ KTP ↔ NIK ↔
     * alamat ↔ koordinat) di modal SEBELUM tombol Setuju terbuka — prinsip
     * "periksa dulu, baru setujui" dijaga checklist-gate.js, dan di sini
     * tinggal menulis hasilnya: stempel verified_* pasangan + status naik.
     *
     * Stempel yang SUDAH ada tidak pernah ditimpa (tulis-sekali); baris
     * dikunci dulu supaya dua admin yang menekan Setujui nyaris bersamaan
     * berakhir di kondisi yang sama. Pengguna yang diblokir atau berkasnya
     * sudah tidak antre tidak boleh naik lewat POST manual.
     */
    public function verifyUser(Request $request, User $user): RedirectResponse
    {
        $adminId = $request->user()->id;

        $disetujui = DB::transaction(function () use ($user, $adminId): bool {
            $antrian = User::lockForUpdate()->findOrFail($user->id);

            if ($antrian->isBlocked() || $antrian->verified_at !== null) {
                return false;
            }

            $antrian->verified_by     = $adminId;
            $antrian->verified_at     = now();
            $antrian->status          = UserStatus::Terverifikasi;
            $antrian->rejected_by     = null;
            $antrian->rejected_at     = null;
            $antrian->rejected_reason = null;
            $antrian->save();

            return true;
        });

        if (! $disetujui) {
            // Bisa terjadi bila admin lain lebih dulu memprosesnya —
            // bukan kesalahan, cukup diberi tahu.
            return back()->with('error', "Verifikasi {$user->name} sudah diproses sebelumnya — stempel tidak bisa ditimpa.");
        }

        return back()->with('success', "Identitas {$user->name} terverifikasi.");
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

        $response = Storage::disk('local')->response($path);
        $response->headers->set('Content-Type', Storage::disk('local')->mimeType($path));
        $response->headers->set('Content-Disposition', 'inline');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Menolak berkas identitas.
     *
     * Statusnya jatuh ke `ditolak` sehingga barisnya keluar dari antrian,
     * dengan jejak rejected_* yang tampil di aplikasi pengguna — tanpa itu
     * ia tidak tahu apa yang harus diperbaiki. KTP/selfie menunggu pengganti:
     * pengiriman ulang berkas membuka siklus baru (status kembali menunggu),
     * sementara jejak penolakan ini SENGAJA dipertahankan — di antrian ia
     * menjadi konteks "pengajuan ulang" bagi admin, dan baru dibersihkan
     * saat identitasnya akhirnya disetujui.
     */
    public function rejectUser(RejectVerificationRequest $request, User $user): RedirectResponse
    {
        $user->status          = UserStatus::Ditolak;
        $user->rejected_by     = $request->user()->id;
        $user->rejected_at     = now();
        $user->rejected_reason = $request->reason();
        $user->save();

        return back()->with('success', "Berkas {$user->name} ditolak — alasan terkirim ke aplikasinya.");
    }

    /**
     * Menyetujui pengajuan toko.
     *
     * Seluruh syarat & penulisan stempel hidup di VerifikasiTokoService —
     * ditulis SEKALI di sana karena aksi yang sama juga tersedia dari
     * tabel toko dan API admin; tiga salinan aturan pasti menyimpang.
     *
     * Begitu stempel persetujuan ditulis, pemilik OTOMATIS tampil sebagai
     * Level 3 · Usaha Terverifikasi — level adalah turunan dari toko
     * verified, tidak ada kolom level untuk ditulis lagi.
     */
    public function approveStore(Request $request, Store $store): RedirectResponse
    {
        return match ($this->verifikasiToko->setujui($store, $request->user()->id)) {
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
        $ditolak = $this->verifikasiToko->tolak($store, $request->user()->id, $request->reason());

        if (! $ditolak) {
            return back()->with('error',
                "Toko {$store->name} sudah diproses sebelumnya — tidak bisa ditolak lagi.");
        }

        return back()->with('success', "Toko {$store->name} ditolak.");
    }
}
