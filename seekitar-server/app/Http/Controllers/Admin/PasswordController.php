<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Ubah kata sandi admin sendiri.
 *
 * Kata sandi default `password` dari seeder HARUS bisa diganti dari dalam
 * panel. Tanpa halaman ini satu-satunya cara mengganti sandi adalah lewat
 * tinker di server produksi — yang berarti dalam praktiknya tidak pernah
 * dilakukan, dan kredensial contoh di .env.example tetap berlaku selamanya.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Cast 'hashed' pada model User meng-hash otomatis; menghashnya lagi
        // di sini akan menghasilkan hash dari sebuah hash dan sandinya tidak
        // akan pernah cocok saat login.
        $user->password = $request->validated('password');
        $user->save();

        /*
         * Sesi WAJIB diperbarui setelah kata sandi berganti.
         *
         * Alasan mengganti sandi biasanya adalah kecurigaan sandi lama bocor.
         * Kalau sesi lain dibiarkan hidup, penyusup yang sudah masuk tetap
         * masuk — penggantian sandi jadi sekadar seremoni.
         *
         * logoutOtherDevices() menulis ulang password_hash di sesi ini dan
         * membatalkan sesi lain milik pengguna yang sama.
         */
        Auth::logoutOtherDevices($request->validated('password'));

        // Token API ikut dicabut: perangkat mobile yang memakai akun ini harus
        // masuk ulang.
        $user->tokens()->delete();

        logger()->channel('security')->notice('Kata sandi admin diubah', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return redirect()
            ->route('admin.password.edit')
            ->with('success', 'Kata sandi diperbarui. Sesi di perangkat lain telah dikeluarkan.');
    }
}
