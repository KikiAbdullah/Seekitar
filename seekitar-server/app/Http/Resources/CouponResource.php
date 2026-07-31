<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Coupon */
class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'type'             => $this->type,
            'value'            => (float) $this->value,
            'min_order_amount' => $this->min_order_amount !== null ? (float) $this->min_order_amount : null,
            'max_discount'     => $this->max_discount !== null ? (float) $this->max_discount : null,
            'description'      => $this->description,
            'is_active'        => $this->is_active,
            'starts_at'        => $this->starts_at?->format('Y-m-d\TH:i:s\Z'),
            'expires_at'       => $this->expires_at?->format('Y-m-d\TH:i:s\Z'),
            'used_count'       => (int) $this->used_count,
            'usage_limit'      => $this->usage_limit,
        ];
    }
}
