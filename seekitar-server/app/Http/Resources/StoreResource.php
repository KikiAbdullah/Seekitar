<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk publik entitas Store (API §3.2).
 *
 * @mixin \App\Models\Store
 */
class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            // Foto etalase — publik oleh desain, seperti avatar pengguna.
            'photo' => $this->photo,

            // Selalu array, meski satu nilai — klien tidak pernah melihat
            // bentuk SET comma-separated milik MySQL (API §3.1).
            'store_type'   => $this->store_type,
            'category_ids' => $this->category_ids,

            'regency' => $this->regency,
            'address' => $this->address,
            'location' => $this->coordinates(),

            'service_radius_km' => (float) $this->service_radius_km,

            // Sumber badge di UI (BRANDING-GUIDELINE §4.2).
            'accepts_cod'     => (bool) $this->accepts_cod,
            'offers_delivery' => (bool) $this->offers_delivery,
            'allows_pickup'   => (bool) $this->allows_pickup,

            'operating_hours' => $this->operating_hours,
            'rating_avg'      => (float) $this->rating_avg,
            'total_reviews'   => (int) $this->total_reviews,

            // Nama JSON DIPELAHANKAN untuk klien lama; sumbernya kini kolom
            // `status` (pending|verified|rejected|blocked).
            'verification_status' => $this->status?->value,
            'is_active'           => (bool) $this->is_active,

            // Hanya ada bila query memakai scope withDistance().
            'distance_km' => $this->whenNotNull(
                isset($this->distance_km) ? round((float) $this->distance_km, 2) : null
            ),

            // Rekening hanya ditampilkan ke pemiliknya; pembeli melihatnya
            // lewat detail pesanan saat metode bayar transfer. Atas namanya
            // ikut disertakan — transfer manual butuh kepastian itu.
            'bank_account' => $this->when(
                $request->user()?->id === $this->user_id,
                $this->bank_account
            ),
            'bank_account_name' => $this->when(
                $request->user()?->id === $this->user_id,
                $this->bank_account_name
            ),

            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
