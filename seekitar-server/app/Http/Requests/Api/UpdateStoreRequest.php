<?php

namespace App\Http\Requests\Api;

use App\Enums\StoreType;
use App\Models\Store;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Pembaruan toko (API §3.4) — PATCH parsial: semua field opsional dan hanya
 * yang dikirim yang berubah. Bentuk datanya identik dengan pembuatan
 * (mewarisi validateOperatingHours); yang berubah:
 *
 * - `required` menjadi `sometimes` — PATCH membawa subset field.
 * - latitude/longitude wajib berpasangan atau tidak dikirim sama sekali.
 * - fulfilment dinilai dari GABUNGAN nilai baru & nilai lama, bukan dari
 *   default pembuatan.
 *
 * Tidak ada re-verifikasi otomatis setelah edit: field yang ditinjau admin
 * (foto, titik lokasi) boleh berganti — pemilik toko yang sudah terverifikasi
 * adalah identitas yang sudah terikat KTP, sehingga risikonya bukan anonimitas
 * melainkan penyalahgunaan akun oleh pihak lain, yang model ancamnya berbeda.
 */
class UpdateStoreRequest extends StoreStoreRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'min:3', 'max:100'],

            'store_type'   => ['sometimes', 'array', 'min:1'],
            'store_type.*' => ['required', Rule::enum(StoreType::class)],

            'category_ids'   => ['sometimes', 'array', 'min:1', 'max:10'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            // Pindahan titik toko hanya sah sebagai pasangan — satu koordinat
            // tanpa pasangannya menghasilkan lokasi yang separuh baru.
            'latitude'  => ['sometimes', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'address'   => ['sometimes', 'nullable', 'string', 'max:255'],

            'photo'     => ['sometimes', 'image', 'mimes:jpeg,png,webp', 'max:5120'],

            'service_radius_km' => ['sometimes', 'numeric', 'min:0.1', 'max:50'],

            'operating_hours' => ['sometimes', 'nullable', 'array'],
            'bank_account'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'npwp'            => ['sometimes', 'nullable', 'string', 'max:20'],

            'accepts_cod'     => ['sometimes', 'boolean'],
            'offers_delivery' => ['sometimes', 'boolean'],
            'allows_pickup'   => ['sometimes', 'boolean'],

            // Saklar "tutup sementara" milik pemilik. Menonaktifkan toko
            // ORANG LAIN tetap wewenang admin (StorePolicy::deactivate).
            'is_active'       => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Nilai efektif = kiriman baru bila ada, atau nilai lama bila field absen
     * — menilai hanya dari kiriman akan salah membaca PATCH "ganti nama saja"
     * sebagai toko tanpa cara pemenuhan.
     */
    protected function validateFulfilment(Validator $v): void
    {
        /** @var Store $store */
        $store = $this->route('store');

        $delivery = $this->has('offers_delivery') ? $this->boolean('offers_delivery') : $store->offers_delivery;
        $pickup   = $this->has('allows_pickup')   ? $this->boolean('allows_pickup')   : $store->allows_pickup;

        if (! $delivery && ! $pickup) {
            $v->errors()->add('allows_pickup', 'Toko harus melayani antar atau ambil di tempat.');
        }
    }
}
