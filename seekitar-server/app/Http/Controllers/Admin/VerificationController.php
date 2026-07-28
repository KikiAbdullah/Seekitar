<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationLevel;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Antrian verifikasi KTP (SLA 1x24 jam, PRD §5.3.2). */
class VerificationController extends Controller
{
    public function index(): View
    {
        return view('admin.verifications.index', [
            'pending' => User::whereNotNull('ktp_submitted_at')
                ->where('verification_level', VerificationLevel::Basic)
                // Yang paling lama menunggu didahulukan — itulah yang
                // paling dekat melewati SLA.
                ->orderBy('ktp_submitted_at')
                ->paginate(20),
        ]);
    }

    public function approveUser(User $user): RedirectResponse
    {
        $user->verification_level  = VerificationLevel::Verified;
        $user->ktp_rejected_reason = null;
        $user->save();

        return back()->with('success', "Verifikasi {$user->name} disetujui.");
    }

    public function rejectUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            // Wajib: tanpa alasan, pengguna akan mengirim ulang berkas
            // yang sama persis.
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->ktp_rejected_reason = $data['reason'];
        $user->ktp_submitted_at    = null;
        $user->save();

        return back()->with('success', "Verifikasi {$user->name} ditolak.");
    }
}
