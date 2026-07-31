<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserAddress */
class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'label'           => $this->label,
            'address'         => $this->address,
            'latitude'        => $this->latitude ? (float) $this->latitude : null,
            'longitude'       => $this->longitude ? (float) $this->longitude : null,
            'regency'         => $this->regency,
            'regency_code'    => $this->regency_code,
            'recipient_name'  => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'is_default'      => (bool) $this->is_default,
            'created_at'      => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
