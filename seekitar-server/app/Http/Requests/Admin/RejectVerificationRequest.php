<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alasan penolakan verifikasi (KTP maupun toko).
 *
 * Alasan WAJIB dan tidak boleh kosong: tanpa itu pengaju tidak tahu apa yang
 * harus diperbaiki dan akan mengirim ulang berkas yang sama persis — antrian
 * bertambah tanpa satu pun masalah terselesaikan.
 *
 * Dipakai empat route sekaligus (tolak pengguna & tolak toko), sehingga
 * aturannya tidak bisa menyimpang antar-tempat.
 */
class RejectVerificationRequest extends FormRequest
{
    /**
     * Otorisasi ditegakkan middleware `permission:verify-users` /
     * `permission:verify-stores` pada route. Mengulangnya di sini hanya
     * menciptakan dua sumber kebenaran yang bisa berbeda.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            // 10 karakter: menahan "salah" atau "tidak jelas" yang sama tidak
            // membantunya dengan tidak memberi alasan sama sekali.
            'reason.min'      => 'Alasan terlalu singkat — jelaskan apa yang harus diperbaiki (minimal 10 karakter).',
            'reason.max'      => 'Alasan maksimal 500 karakter.',
        ];
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
