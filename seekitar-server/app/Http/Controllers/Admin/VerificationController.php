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

/**
 * Antrian verifikasi — KTP pengguna & pengajuan toko.
 *
 * DUA HALAMAN, DUA IZIN. `verify-users` dan `verify-stores` adalah permission
 * terpisah (§6.2), jadi menggabungkannya dalam satu halaman akan memaksa admin
 * yang hanya punya salah satunya melihat data yang bukan haknya.
 *
 * SLA peninjauan 1×24 jam (PRD §5.3.2) — karena itu urutannya SELALU dari
 * pengajuan terlama, bukan terbaru: yang paling lama menunggu adalah yang
 * paling dekat melanggar SLA.
 */
class VerificationController extends Controller
{
    /** Antrian KTP pengguna. */
    public function users(): View
    {
        return view('admin.verifications.users', [
            'pending' => User::query()
                ->select(['id', 'name', 'phone', 'verification_level',
                          'ktp_submitted_at', 'ktp_rejected_reason', 'created_at'])
                ->whereNotNull('ktp_submitted_at')
                ->where('verification_level', VerificationLevel::Basic)
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
                ->with('user:id,name,phone')
                ->select(['id', 'user_id', 'name', 'regency', 'store_type', 'address',
                          'verification_status', 'created_at'])
                ->where('verification_status', VerificationStatus::Pending)
                ->orderBy('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function approveUser(User $user): RedirectResponse
    {
        $user->verification_level  = VerificationLevel::Verified;
        $user->ktp_rejected_reason = null;
        $user->save();

        return back()->with('success', "Verifikasi {$user->name} disetujui.");
    }

    /**
     * Menolak pengajuan KTP.
     *
     * Level TETAP di Basic — penolakan bukan penurunan peringkat, melainkan
     * kembalinya pengguna ke keadaan sebelum mengajukan. `ktp_submitted_at`
     * dikosongkan supaya barisnya keluar dari antrian dan pengguna bisa
     * mengirim ulang berkas.
     */
    public function rejectUser(RejectVerificationRequest $request, User $user): RedirectResponse
    {
        $user->ktp_rejected_reason = $request->reason();
        $user->ktp_submitted_at    = null;
        $user->save();

        return back()->with('success', "Verifikasi {$user->name} ditolak.");
    }

    public function approveStore(Store $store): RedirectResponse
    {
        $store->verification_status = VerificationStatus::Verified;
        $store->rejected_reason     = null;
        $store->verified_at         = now();
        $store->save();

        return back()->with('success', "Toko {$store->name} disetujui.");
    }

    public function rejectStore(RejectVerificationRequest $request, Store $store): RedirectResponse
    {
        $store->verification_status = VerificationStatus::Rejected;
        $store->rejected_reason     = $request->reason();
        $store->save();

        return back()->with('success', "Toko {$store->name} ditolak.");
    }
}
