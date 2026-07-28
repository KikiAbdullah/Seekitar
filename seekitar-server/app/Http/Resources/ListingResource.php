<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Listing */
class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'store_id'     => $this->store_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'listing_type' => $this->listing_type?->value,

            // Rupiah tanpa desimal (API §12.3).
            'price'     => $this->price === null ? null : (int) $this->price,
            'stock_qty' => $this->stock_qty,
            'slot'      => $this->slot,

            'images' => $this->images ?? [],
            'status' => $this->status?->value,

            'store'       => new StoreResource($this->whenLoaded('store')),
            'distance_km' => $this->whenNotNull(
                isset($this->distance_km) ? round((float) $this->distance_km, 2) : null
            ),

            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
