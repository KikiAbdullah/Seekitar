<?php

namespace App\Http\Requests\Api;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // publik — belum ada pengguna saat OTP diminta
    }

    /**
     * Normalisasi SEBELUM validasi.
     *
     * Inilah yang mencegah akun ganda: `08123...`, `+62812...`, dan
     * `62812...` adalah nomor yang sama, tetapi `UNIQUE(users.phone)`
     * membandingkan string mentah (Server_Implementation_Guide §18A.6).
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => PhoneNumber::normalize($this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^62[0-9]{8,13}$/', 'max:'.PhoneNumber::MAX_LENGTH],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex'    => 'Format nomor tidak valid. Contoh: 081234567890.',
        ];
    }

    public function phone(): string
    {
        return (string) $this->validated('phone');
    }
}
