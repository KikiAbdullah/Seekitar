<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Dispute */
class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'order_id'    => $this->order_id,
            'reported_by' => $this->reported_by,
            'reason'      => $this->reason?->value,
            'description' => $this->description,
            'status'      => $this->status?->value,

            // SLA tanggapan admin 1x24 jam (PRD §5.5).
            'response_deadline'  => $this->response_deadline?->format('Y-m-d\TH:i:s\Z'),
            'first_responded_at' => $this->first_responded_at?->format('Y-m-d\TH:i:s\Z'),
            'resolution_note'    => $this->resolution_note,
            'resolved_at'        => $this->resolved_at?->format('Y-m-d\TH:i:s\Z'),

            'order'      => new OrderResource($this->whenLoaded('order')),
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
