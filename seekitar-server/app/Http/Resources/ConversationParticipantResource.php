<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ConversationParticipant */
class ConversationParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->user_id,
            'name'       => $this->user?->name,
            'avatar_url' => $this->user?->avatar_url,
            'phone'      => $this->user?->phone,
        ];
    }
}
