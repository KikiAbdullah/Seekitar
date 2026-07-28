<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route ini hanya menyentuh akun sendiri; middleware `auth` sudah
        // memastikan ada yang masuk.
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Konsisten dengan LoginRequest: email disimpan huruf kecil supaya
        // "Admin@x.id" dan "admin@x.id" tidak menjadi dua akun berbeda.
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                // ignore() akun sendiri — tanpa itu, menyimpan tanpa mengubah
                // email akan ditolak karena bentrok dengan dirinya sendiri.
                // Kolom `email` NULL-able dan hanya dimiliki akun admin,
                // sehingga baris pengguna biasa tidak ikut bertabrakan.
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ];
    }
}
