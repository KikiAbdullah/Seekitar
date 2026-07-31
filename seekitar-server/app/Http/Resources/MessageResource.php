<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id'       => $this->sender_id,
            'sender_name'     => $this->sender?->name,
            'message'         => $this->message,
            'message_type'    => $this->message_type,
            'attachment_url'  => $this->attachment_url,
            'created_at'      => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
