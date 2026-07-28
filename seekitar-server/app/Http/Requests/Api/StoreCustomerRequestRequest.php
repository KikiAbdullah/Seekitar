<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pembuatan permintaan pembeli (API §5.1).
 */
class StoreCustomerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],

            'budget_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            // gte, bukan gt: anggaran pasti (min = max) itu sah.
            'budget_max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:budget_min'],

            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],

            // Radius SIAR permintaan — default 15 km (DATABASE.md §4.5).
            // Berbeda dari `stores.service_radius_km` yang default 5 km.
            'radius_km' => ['sometimes', 'numeric', 'min:1', 'max:25'],

            'images'   => ['sometimes', 'nullable', 'array', 'max:3'],
            'images.*' => ['url', 'max:500'],

            'required_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'budget_max.gte'          => 'Anggaran maksimum tidak boleh lebih kecil dari minimum.',
            'images.max'              => 'Maksimal 3 foto pendukung.',
            'radius_km.max'           => 'Radius siar maksimal 25 km.',
            'required_date.after_or_equal' => 'Tanggal dibutuhkan tidak boleh di masa lalu.',
        ];
    }
}
