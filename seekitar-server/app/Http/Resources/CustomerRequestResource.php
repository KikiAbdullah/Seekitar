<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Permintaan pembeli.
 *
 * ⚠️ Data pembeli DISARING sebelum penawaran diterima (PRD §5.2.3): penyedia
 * hanya melihat nama disingkat dan lokasi kasar. Nama lengkap, nomor telepon,
 * dan titik persis baru dibuka setelah penawarannya dipilih.
 *
 * @mixin \App\Models\CustomerRequest
 */
class CustomerRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->user_id;

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'category_id' => $this->category_id,

            'budget_min' => $this->budget_min === null ? null : (int) $this->budget_min,
            'budget_max' => $this->budget_max === null ? null : (int) $this->budget_max,

            'images'    => $this->images ?? [],
            'radius_km' => (float) $this->radius_km,
            'status'    => $this->status?->value,

            'required_date'   => $this->required_date?->format('Y-m-d\TH:i:s\Z'),
            'expires_at'      => $this->expires_at?->format('Y-m-d\TH:i:s\Z'),
            'extended_at'     => $this->extended_at?->format('Y-m-d\TH:i:s\Z'),
            'extension_count' => (int) $this->extension_count,

            'offers_count' => $this->whenCounted('offers'),

            // Pemilik melihat titik persisnya; penyedia tidak.
            'location' => $isOwner ? $this->coordinates() : null,

            'buyer' => $this->when(
                $this->relationLoaded('user'),
                fn () => $isOwner
                    ? new UserResource($this->user)
                    : ['name' => $this->user?->displayName()],
            ),

            'distance_km' => $this->whenNotNull(
                isset($this->distance_km) ? round((float) $this->distance_km, 2) : null
            ),

            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
