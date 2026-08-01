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

    /**
     * Konteks ringkas untuk modal di tabel index.
     *
     * Modal menjadi SATU-SATUNYA cara melihat laporan dari tabel: tombol
     * Detail maupun Selesaikan membuka modal yang sama. Karena itu data
     * keputusan (pesanan, pembeli, toko, pelapor, SLA) DAN hasil mediasi
     * untuk laporan yang sudah selesai harus diangkut ke sini.
     */
    public function info(Dispute $dispute): JsonResponse
    {
        $dispute->load(['order.buyer', 'order.store', 'reporter', 'assignee']);

        $orderFinal = $dispute->order?->status?->value;

        return response()->json([
            'id'           => $dispute->id,
            'status'       => $dispute->status->value,
            'reason'       => $dispute->reason?->label(),
            'description'  => $dispute->description,
            'created_at'   => $dispute->created_at?->format('d M Y H:i'),
            'deadline'     => $dispute->response_deadline?->format('d M Y H:i'),
            'deadline_iso' => $dispute->response_deadline?->toIso8601String(),
            'deadline_past'=> $dispute->response_deadline?->isPast(),
            'overdue'      => $dispute->isOverdue(),
            'order'        => $dispute->order ? [
                'order_number' => $dispute->order->order_number,
                'total_amount' => 'Rp ' . number_format((float) $dispute->order->total_amount, 0, ',', '.'),
                'status'       => $dispute->order->status?->label(),
            ] : null,
            'buyer'        => $dispute->order?->buyer ? [
                'name'      => $dispute->order->buyer->name,
                'phone'     => $dispute->order->buyer->phone,
                'verified'  => (bool) $dispute->order->buyer->verified_at,
            ] : null,
            'store'        => $dispute->order?->store ? [
                'name'    => $dispute->order->store->name,
                'regency' => $dispute->order->store->regency,
            ] : null,
            'reporter'     => $dispute->reporter ? [
                'name'      => $dispute->reporter->name,
                'phone'     => $dispute->reporter->phone,
                'verified'  => (bool) $dispute->reporter->verified_at,
            ] : null,
            // Hasil mediasi — hanya terisi untuk laporan yang sudah selesai.
            'outcome'      => $dispute->status->value === 'resolved' ? [
                'assignee'   => $dispute->assignee?->name ?? 'Sistem',
                'resolved_at'=> $dispute->resolved_at?->format('d M Y H:i'),
                'decision'   => $orderFinal === 'selesai'
                    ? 'Dana Diteruskan ke Penjual (Selesai)'
                    : 'Dana Dikembalikan ke Pembeli (Dibatalkan)',
                'note'       => $dispute->resolution_note,
            ] : null,
        ]);
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
