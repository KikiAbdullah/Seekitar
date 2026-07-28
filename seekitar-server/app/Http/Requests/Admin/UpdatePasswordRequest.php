<?php

namespace App\Http\Requests\Admin;

use App\Rules\NotAWeakPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            /*
             * Sandi LAMA wajib, meski pengguna sudah masuk.
             *
             * Tanpa ini, siapa pun yang menemukan laptop admin dalam keadaan
             * terbuka bisa mengambil alih akunnya secara permanen dalam satu
             * klik. `current_password` memvalidasi terhadap guard yang sedang
             * aktif, bukan kolom di request.
             */
            'current_password' => ['required', 'string', 'current_password:web'],

            'password' => [
                'required', 'string', 'confirmed',
                /*
                 * Minimal 12 karakter — lebih tinggi dari default Laravel (8).
                 * Akun admin memegang data pribadi seluruh pengguna (UU PDP),
                 * jadi ambangnya memang harus berbeda dari akun biasa.
                 *
                 * ⚠️ `->uncompromised()` SENGAJA TIDAK DIPAKAI. Aturan itu
                 * memanggil api.pwnedpasswords.com dan GAGAL-TERBUKA: bila API
                 * tak terjangkau, sandi apa pun diluluskan tanpa peringatan.
                 * Perilaku itu diverifikasi langsung, bukan diasumsikan —
                 * lihat catatan lengkap di App\Rules\NotAWeakPassword.
                 */
                Password::min(12)->letters()->mixedCase()->numbers(),
                new NotAWeakPassword(),
                // Menyetel ulang ke sandi yang sama tidak menyelesaikan apa
                // pun bila alasan penggantiannya adalah kebocoran.
                'different:current_password',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'   => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.confirmed'          => 'Konfirmasi kata sandi tidak cocok.',
            'password.different'          => 'Kata sandi baru harus berbeda dari yang sekarang.',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'kata sandi saat ini',
            'password'         => 'kata sandi baru',
        ];
    }
}
