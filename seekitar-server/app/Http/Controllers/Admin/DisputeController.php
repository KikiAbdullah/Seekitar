<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\DisputesDataTable;
use App\Enums\DisputeStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Services\OrderStateMachine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DisputeController extends Controller
{
    public function __construct(private readonly OrderStateMachine $states) {}

    public function index(): View
    {
        return view('admin.disputes.index');
    }

    public function data(Request $request, DisputesDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function show(Dispute $dispute): View
    {
        // Konteks penuh untuk memutuskan: pesanan beserta kedua pihaknya,
        // pelapor, dan admin yang menangani — semuanya satu perjalanan.
        $dispute->load(['order.buyer', 'order.store', 'reporter', 'assignee']);

        return view('admin.disputes.show', compact('dispute'));
    }

    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $data = $request->validate([
            'resolution'      => ['required', Rule::in(['selesai', 'dibatalkan'])],
            'resolution_note' => ['required', 'string', 'max:2000'],
        ]);

        if ($dispute->status === DisputeStatus::Resolved) {
            return back()->with('error', 'Laporan sudah diselesaikan.');
        }

        DB::transaction(function () use ($dispute, $data, $request): void {
            $dispute->status             = DisputeStatus::Resolved;
            $dispute->resolution_note    = $data['resolution_note'];
            $dispute->resolved_at        = now();
            $dispute->assigned_to        = $request->user()->id;
            $dispute->first_responded_at ??= now();
            $dispute->save();

            $order = $dispute->order()->firstOrFail();
            $this->states->transition($order, OrderStatus::from($data['resolution']),
                $data['resolution'] === 'dibatalkan' ? $data['resolution_note'] : null,
                $request->user()->id);
            $order->save();
        });

        return redirect()
            ->route('admin.disputes.index')
            ->with('success', 'Laporan diselesaikan.');
    }
}
