<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login panel admin — email + kata sandi (Server_Implementation_Guide §18A.5).
 *
 * Berbeda dari aplikasi mobile yang memakai OTP WhatsApp: panel ini dibuka di
 * browser desktop, sering tanpa ponsel di tangan. Lihat komentar kolom
 * email/password pada migrasi `extend_users_table` untuk alasan lengkapnya.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $remember = $request->boolean('remember');

        if (! Auth::attempt($request->credentials(), $remember)) {
            // Pesan sengaja TIDAK membedakan "email tidak ada" dari "kata
            // sandi salah". Membedakannya memberi tahu penyerang email mana
            // yang terdaftar — itu enumerasi akun.
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        $user = Auth::user();

        // Pemeriksaan peran dilakukan SETELAH kredensial benar, dan kalau
        // gagal sesinya langsung dibuang: pengguna biasa yang entah bagaimana
        // punya kata sandi tetap tidak boleh masuk panel.
        if (! $user->canAccessAdminPanel()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun ini tidak memiliki akses ke panel admin.',
            ]);
        }

        // WAJIB: tanpa regenerate, id sesi sebelum login tetap berlaku
        // sesudahnya — celah session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
