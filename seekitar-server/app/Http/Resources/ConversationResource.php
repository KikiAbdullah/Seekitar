<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()->id;
        $myParticipation = $this->participants->first(fn ($p) => $p->user_id === $userId);

        return [
            'id'           => $this->id,
            'order_id'     => $this->order_id,
            'participants' => ConversationParticipantResource::collection($this->whenLoaded('participants')),
            'last_message' => new MessageResource($this->whenLoaded('lastMessage')),
            'unread_count' => $myParticipation
                ? $this->messages()
                    ->when($myParticipation->last_read_at, fn ($q, $date) => $q->where('created_at', '>', $date))
                    ->count()
                : 0,
            'created_at'   => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
            'updated_at'   => $this->updated_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
