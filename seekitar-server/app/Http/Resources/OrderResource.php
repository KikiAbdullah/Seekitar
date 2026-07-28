<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'order_number' => $this->order_number,

            'buyer_id'   => $this->buyer_id,
            'store_id'   => $this->store_id,
            'offer_id'   => $this->offer_id,
            'listing_id' => $this->listing_id,

            'order_type'   => $this->order_type?->value,
            'quantity'     => (int) $this->quantity,
            'total_amount' => (int) $this->total_amount,

            'status' => $this->status?->value,
            // Label UI diturunkan dari status + tipe + metode antar; tidak ada
            // nilai ENUM terpisah untuk "Siap Diambil" / "Disewa" (PRD §5.4).
            'status_label' => $this->status?->contextualLabel(
                $this->order_type,
                $this->delivery_method,
            ),

            'payment_method'  => $this->payment_method?->value,
            'delivery_method' => $this->delivery_method?->value,

            'shipping_address'    => $this->shipping_address,
            'payment_proof_url'   => $this->payment_proof_url,
            'payment_confirmed_at' => $this->payment_confirmed_at?->format('Y-m-d\TH:i:s\Z'),

            'notes' => $this->notes,

            'completed_at'  => $this->completed_at?->format('Y-m-d\TH:i:s\Z'),
            'cancelled_at'  => $this->cancelled_at?->format('Y-m-d\TH:i:s\Z'),
            'cancel_reason' => $this->cancel_reason,

            'can_review' => $this->acceptsReview(),

            'store'   => new StoreResource($this->whenLoaded('store')),
            'listing' => new ListingResource($this->whenLoaded('listing')),

            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
