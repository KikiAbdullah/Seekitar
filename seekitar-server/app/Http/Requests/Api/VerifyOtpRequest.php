<?php

namespace App\Http\Requests\Api;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nomor dinormalkan dengan aturan yang SAMA seperti saat OTP dibuat.
     * Kalau berbeda, kunci cache tidak akan cocok dan kode yang benar pun
     * dianggap salah.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => PhoneNumber::normalize($this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^62[0-9]{8,13}$/', 'max:'.PhoneNumber::MAX_LENGTH],
            'otp'   => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Format nomor tidak valid. Contoh: 081234567890.',
            'otp.digits'  => 'Kode OTP harus 6 digit.',
        ];
    }

    public function phone(): string
    {
        return (string) $this->validated('phone');
    }

    public function otp(): string
    {
        return (string) $this->validated('otp');
    }
}
