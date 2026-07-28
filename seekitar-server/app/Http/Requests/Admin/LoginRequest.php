<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // halaman login memang untuk tamu
    }

    protected function prepareForValidation(): void
    {
        // Email disimpan huruf kecil; tanpa ini "Admin@x.id" dan "admin@x.id"
        // dianggap dua akun berbeda oleh pencarian, padahal UNIQUE-nya
        // case-insensitive di collation utf8mb4_unicode_ci.
        $this->merge([
            'email' => is_string($this->input('email'))
                ? mb_strtolower(trim($this->input('email')))
                : $this->input('email'),
        ]);
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /** @return array{email: string, password: string} */
    public function credentials(): array
    {
        return [
            'email'    => (string) $this->validated('email'),
            'password' => (string) $this->validated('password'),
        ];
    }
}
