<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with(['participants.user', 'lastMessage.sender'])
            ->latest()
            ->paginate($this->perPage());

        return $this->paginated($conversations, ConversationResource::class);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $conversation->isParticipant($request->user()->id)) {
            return $this->fail('Percakapan tidak ditemukan.', 404);
        }

        $conversation->load(['participants.user', 'lastMessage.sender']);

        return $this->ok(new ConversationResource($conversation));
    }

    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        if ($request->input('participant_id') === $userId) {
            return $this->fail('Tidak bisa membuat percakapan dengan diri sendiri.', 422);
        }

        $validated = $request->validate([
            'order_id'       => ['nullable', 'exists:orders,id'],
            'participant_id' => ['required', 'exists:users,id'],
        ]);

        $existing = Conversation::where('order_id', $validated['order_id'])
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $validated['participant_id']))
            ->first();

        if ($existing) {
            $existing->load(['participants.user', 'lastMessage.sender']);

            return $this->ok(new ConversationResource($existing));
        }

        $conversation = DB::transaction(function () use ($validated, $userId): Conversation {
            $conversation = Conversation::create([
                'order_id' => $validated['order_id'] ?? null,
            ]);

            $conversation->participants()->createMany([
                ['user_id' => $userId],
                ['user_id' => $validated['participant_id']],
            ]);

            return $conversation;
        });

        $conversation->load(['participants.user']);

        return $this->created(new ConversationResource($conversation));
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $conversation->isParticipant($request->user()->id)) {
            return $this->fail('Percakapan tidak ditemukan.', 404);
        }

        $messages = $conversation->messages()
            ->with('sender')
            ->latest()
            ->paginate($this->perPage());

        $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->update(['last_read_at' => now()]);

        return $this->paginated($messages, MessageResource::class);
    }

    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $conversation->isParticipant($request->user()->id)) {
            return $this->fail('Percakapan tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'message'        => ['required_without:attachment_url', 'nullable', 'string'],
            'attachment_url' => ['required_without:message', 'nullable', 'string', 'url'],
            'message_type'   => ['nullable', 'string', Rule::in(['text', 'image', 'system'])],
        ]);

        $message = $conversation->messages()->create([
            'sender_id'      => $request->user()->id,
            'message'        => $validated['message'] ?? null,
            'message_type'   => $validated['message_type'] ?? 'text',
            'attachment_url' => $validated['attachment_url'] ?? null,
        ]);

        $message->load('sender');

        return $this->created(new MessageResource($message));
    }
}
