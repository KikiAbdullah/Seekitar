<?php

namespace App\Http\Requests\Api;

use App\Enums\ListingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pembuatan listing (API §4.1).
 *
 * Aturan `price`, `stock_qty`, dan `slot` mencerminkan CHECK constraint
 * `listings_price_required_chk` & `listings_qty_slot_chk` (DATABASE.md §4.4).
 * Divalidasi di sini juga supaya pengguna mendapat 422 yang menjelaskan
 * kesalahannya, bukan 500 dari engine.
 */
class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'store_id'     => ['required', 'uuid', 'exists:stores,id'],
            'title'        => ['required', 'string', 'min:5', 'max:200'],
            'description'  => ['required', 'string', 'max:5000'],
            'listing_type' => ['required', Rule::enum(ListingType::class)],

            // Wajib untuk product & rental; jasa boleh "mulai dari" / null.
            'price' => [
                'nullable', 'numeric', 'min:0',
                Rule::requiredIf(fn () => in_array($this->input('listing_type'), ['product', 'rental'], true)),
            ],

            // Stok HANYA untuk barang & sewa — jasa memakai slot.
            'stock_qty' => [
                'required_if:listing_type,product,rental',
                'prohibited_unless:listing_type,product,rental',
                'integer', 'min:0',
            ],

            // Slot HANYA untuk jasa: kapasitas per hari, bukan jadwal.
            'slot' => [
                'required_if:listing_type,service',
                'prohibited_unless:listing_type,service',
                'integer', 'min:1',
            ],

            'images'   => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required'            => 'Harga wajib diisi untuk produk dan sewa.',
            'stock_qty.required_if'     => 'Stok wajib diisi untuk produk dan sewa.',
            'stock_qty.prohibited_unless' => 'Jasa tidak memakai stok — gunakan slot.',
            'slot.required_if'          => 'Slot (kapasitas harian) wajib diisi untuk jasa.',
            'slot.prohibited_unless'    => 'Hanya jasa yang memakai slot.',
            'images.min'                => 'Minimal 1 foto.',
            'images.max'                => 'Maksimal 5 foto.',
        ];
    }
}
