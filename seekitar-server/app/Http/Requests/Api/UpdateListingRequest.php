<?php

namespace App\Http\Requests\Api;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pembaruan listing (API §4.4) — PUT/PATCH parsial.
 *
 * `listing_type` sengaja DILARANG berubah: CHECK `listings_qty_slot_chk`
 * mengikat stok/slot pada tipe, sehingga memutar tipe berarti memutar makna
 * seluruh kolom lain (stok produk tiba-tiba dibaca sebagai "slot jasa?").
 * Penjual yang salah tipe menghapus listingnya dan membuat ulang.
 *
 * Aturan price/stock/slot mengikuti tipe YANG TERSIMPAN (bukan kiriman),
 * sama seperti StoreListingRequest mengikuti tipe kiriman.
 */
class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Listing $listing */
        $listing = $this->route('listing');
        $service = $listing->listing_type === ListingType::Service;

        return [
            'title'        => ['sometimes', 'string', 'min:5', 'max:200'],
            'description'  => ['sometimes', 'string', 'max:5000'],
            'listing_type' => ['prohibited'],

            // Jasa boleh menegokan harganya (null); produk & sewa tidak
            // boleh mengosongkan harga yang sudah ada.
            'price' => $service
                ? ['sometimes', 'nullable', 'numeric', 'min:0']
                : ['sometimes', 'required', 'numeric', 'min:0'],

            'stock_qty' => $service
                ? ['prohibited']
                : ['sometimes', 'integer', 'min:0'],

            'slot' => $service
                ? ['sometimes', 'integer', 'min:1']
                : ['prohibited'],

            'images'   => ['sometimes', 'array', 'min:1', 'max:5'],
            'images.*' => ['url', 'max:500'],

            // Penjual menandai terjual atau menyembunyikan dagangannya
            // sendiri — bukan status moderasi (itu jalur admin).
            'status' => ['sometimes', Rule::enum(ListingStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'listing_type.prohibited' => 'Tipe listing tidak dapat diubah — hapus dan buat ulang bila perlu.',
            'price.required'          => 'Harga tidak boleh dikosongkan untuk produk dan sewa.',
            'stock_qty.prohibited'    => 'Jasa tidak memakai stok — gunakan slot.',
            'slot.prohibited'         => 'Hanya jasa yang memakai slot.',
            'images.min'              => 'Minimal 1 foto.',
            'images.max'              => 'Maksimal 5 foto.',
        ];
    }
}
