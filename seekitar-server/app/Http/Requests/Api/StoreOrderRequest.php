<?php

namespace App\Http\Requests\Api;

use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pembuatan pesanan dari katalog (API §7.1).
 *
 * Pesanan yang lahir dari penawaran TIDAK dibuat di sini — ia terbentuk
 * otomatis di dalam transaksi `PATCH /offers/{id}/accept`. Karena itu
 * `offer_id` dilarang di endpoint ini.
 */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // `prohibits` menegakkan XOR: mengirim offer_id di sini selalu 422.
            'listing_id' => ['required', 'prohibits:offer_id', 'uuid', 'exists:listings,id'],
            'quantity'   => ['sometimes', 'integer', 'min:1', 'max:1000'],

            'payment_method'  => ['required', Rule::enum(PaymentMethod::class)],
            'delivery_method' => ['required', Rule::enum(DeliveryMethod::class)],

            // Sejalan dengan CHECK `orders_shipping_chk`: diantar tanpa alamat
            // sama dengan paket tanpa tujuan.
            'shipping_address' => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:500'],
            'shipping_latitude'  => ['required_with:shipping_longitude', 'nullable', 'numeric', 'between:-90,90'],
            'shipping_longitude' => ['required_with:shipping_latitude', 'nullable', 'numeric', 'between:-180,180'],

            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'listing_id.prohibits'        => 'Pesanan dari penawaran dibuat lewat endpoint terima penawaran.',
            'shipping_address.required_if' => 'Alamat pengiriman wajib diisi untuk metode antar.',
        ];
    }
}
