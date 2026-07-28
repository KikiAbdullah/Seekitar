<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'order_id'  => $this->order_id,
            'store_id'  => $this->store_id,
            'direction' => $this->direction?->value,
            'rating'    => (int) $this->rating,
            'comment'   => $this->comment,

            'reviewer' => $this->when(
                $this->relationLoaded('reviewer'),
                fn () => ['name' => $this->reviewer?->displayName()],
            ),

            // Ulasan tidak punya updated_at — sekali kirim, permanen (API §8).
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
