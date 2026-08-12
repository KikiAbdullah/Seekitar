<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WalletTransaction
 */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'amount'         => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after'  => (float) $this->balance_after,
            'description'    => $this->description,
            'reference'      => $this->reference,
            'status'         => $this->status,
            'created_at'     => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
