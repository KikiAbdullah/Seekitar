<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Offer */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price      = (int) $this->price;
        $additional = (int) $this->additional_cost;

        return [
            'id'         => $this->id,
            'request_id' => $this->request_id,
            'store_id'   => $this->store_id,

            'price'                => $price,
            'additional_cost'      => $additional,
            'additional_cost_note' => $this->additional_cost_note,

            // Yang mengikat sebagai orders.total_amount adalah JUMLAH keduanya.
            // Dikirim eksplisit supaya klien tidak menghitung sendiri dan
            // berisiko berbeda dari server (API §5.3).
            'total_price' => $price + $additional,

            'estimation_time' => $this->estimation_time,
            'estimated_hours' => $this->estimated_hours,
            'notes'           => $this->notes,
            'status'          => $this->status?->value,

            'expires_at' => $this->expires_at?->format('Y-m-d\TH:i:s\Z'),
            'store'      => new StoreResource($this->whenLoaded('store')),
            'request'    => new CustomerRequestResource($this->whenLoaded('request')),
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
