<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Wallet
 */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'balance'         => (float) $this->balance,
            'total_earned'    => (float) $this->total_earned,
            'total_withdrawn' => (float) $this->total_withdrawn,
            'created_at'      => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
