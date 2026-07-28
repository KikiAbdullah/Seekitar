<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DisputeStatus;
use App\Enums\OrderStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use App\Services\OrderStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminDisputeController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OrderStateMachine $states) {}

    /** GET /admin/disputes */
    public function index(Request $request): JsonResponse
    {
        $query = Dispute::with('order');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Yang paling dekat melewati SLA didahulukan.
        $query->orderBy('response_deadline');

        return $this->paginated($query->paginate($this->perPage()), DisputeResource::class);
    }

    /**
     * PATCH /admin/disputes/{dispute}/resolve
     *
     * Keputusan admin melepas pesanan dari status beku: diteruskan sebagai
     * selesai, atau dibatalkan.
     */
    public function resolve(Request $request, Dispute $dispute): JsonResponse
    {
        $data = $request->validate([
            'resolution'      => ['required', Rule::in(['selesai', 'dibatalkan'])],
            'resolution_note' => ['required', 'string', 'max:2000'],
        ]);

        if ($dispute->status === DisputeStatus::Resolved) {
            return $this->fail('Laporan sudah diselesaikan.', 422);
        }

        DB::transaction(function () use ($dispute, $data, $request): void {
            $dispute->status             = DisputeStatus::Resolved;
            $dispute->resolution_note    = $data['resolution_note'];
            $dispute->resolved_at        = now();
            $dispute->assigned_to        = $request->user()->id;
            $dispute->first_responded_at ??= now();
            $dispute->save();

            $order = $dispute->order()->firstOrFail();

            $this->states->transition(
                $order,
                OrderStatus::from($data['resolution']),
                $data['resolution'] === 'dibatalkan' ? $data['resolution_note'] : null,
                $request->user()->id,
            );
            $order->save();
        });

        return $this->ok(['dispute' => new DisputeResource($dispute->fresh()->load('order'))]);
    }
}
