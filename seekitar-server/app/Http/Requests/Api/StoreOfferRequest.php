<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengiriman penawaran (API §5.3).
 *
 * `price` adalah nilai pekerjaan/barang SAJA. Ongkos antar dan biaya material
 * masuk ke `additional_cost`, karena pengurutan "termurah" memakai jumlah
 * keduanya — memasukkan ongkos ke `price` membuat penyedia yang jujur kalah.
 */
class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid', 'exists:stores,id'],
            'price'    => ['required', 'numeric', 'min:0'],

            'additional_cost'      => ['sometimes', 'numeric', 'min:0'],
            // Wajib dijelaskan bila ada biaya tambahan — pembeli berhak tahu
            // ongkos itu untuk apa.
            'additional_cost_note' => ['required_with:additional_cost', 'nullable', 'string', 'max:150'],

            // Teks bebas yang dibaca pembeli.
            'estimation_time' => ['required', 'string', 'max:100'],
            // Bentuk numerik untuk pengurutan "tercepat" (DATABASE.md §4.6).
            'estimated_hours' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:8760'],

            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'additional_cost_note.required_with' => 'Jelaskan rincian biaya tambahan.',
            'estimation_time.required'           => 'Perkiraan waktu wajib diisi.',
            'estimated_hours.max'                => 'Perkiraan waktu maksimal 1 tahun.',
        ];
    }
}
