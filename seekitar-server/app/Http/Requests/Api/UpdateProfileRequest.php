<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Aturan dari API_DOCUMENTATION.md §2.4.
     *
     * `phone` sengaja TIDAK ada di sini: nomor adalah identitas akun dan
     * hanya bisa berubah lewat alur OTP, bukan pembaruan profil biasa.
     */
    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'min:3', 'max:100'],
            'avatar' => [
                'sometimes', 'image', 'mimes:jpeg,jpg,png', 'max:2048',
                'dimensions:min_width=200,min_height=200',
            ],
            // Wajib berpasangan — satu koordinat saja tidak menentukan titik.
            'latitude'  => ['required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'numeric', 'between:-180,180'],
            'address'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min'             => 'Nama minimal 3 karakter.',
            'avatar.image'         => 'Avatar harus berupa gambar.',
            'avatar.max'           => 'Ukuran avatar maksimal 2 MB.',
            'avatar.dimensions'    => 'Dimensi gambar minimal 200x200 piksel.',
            'latitude.required_with'  => 'Latitude wajib diisi bersama longitude.',
            'longitude.required_with' => 'Longitude wajib diisi bersama latitude.',
            'latitude.between'     => 'Latitude harus antara -90 dan 90.',
            'longitude.between'    => 'Longitude harus antara -180 dan 180.',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->filled('latitude') && $this->filled('longitude');
    }
}
