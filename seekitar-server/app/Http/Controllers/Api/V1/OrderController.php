<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ReviewDirection;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\DisputeResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ReviewResource;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Review;
use App\Services\OrderStateMachine;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderStateMachine $states,
        private readonly SettingService $settings,
    ) {}

    /** GET /orders — pesanan sebagai pembeli ATAU sebagai penjual. */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $role   = $request->query('role', 'buyer');

        $query = Order::query()->with(['store', 'listing']);

        if ($role === 'seller') {
            $query->whereHas('store', fn ($q) => $q->where('user_id', $userId));
        } else {
            $query->where('buyer_id', $userId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->paginated($query->latest()->paginate($this->perPage()), OrderResource::class);
    }

    /** GET /orders/{order} */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return $this->ok(['order' => new OrderResource($order->load(['store', 'listing']))]);
    }

    /**
     * POST /orders — pesanan langsung dari katalog.
     *
     * Harga diambil dari LISTING, bukan dari klien: memercayai harga kiriman
     * berarti pembeli bisa menentukan harganya sendiri.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $idempotencyKey = $this->idempotencyKey($request);
        $buyerId = $request->user()->id;

        $existing = Order::where('buyer_id', $buyerId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
        if ($existing) {
            return $this->ok(['order' => new OrderResource($existing->load(['store', 'listing']))]);
        }

        try {
            $order = DB::transaction(function () use ($request, $idempotencyKey, $buyerId): Order {
            $existing = Order::where('buyer_id', $buyerId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            $listing = Listing::whereKey($request->validated('listing_id'))
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) ($request->validated('quantity') ?? 1);

            if ($listing->listing_type !== \App\Enums\ListingType::Service
                && $listing->stock_qty !== null
                && $listing->stock_qty < $quantity) {
                abort(422, 'Stok tidak mencukupi.');
            }

            $order = new Order($request->safe()->only([
                'payment_method', 'delivery_method', 'shipping_address', 'notes',
            ]));

            $order->buyer_id     = $buyerId;
            $order->idempotency_key = $idempotencyKey;
            $order->store_id     = $listing->store_id;
            $order->listing_id   = $listing->id;
            $order->order_type   = OrderType::fromListingType($listing->listing_type);
            $order->quantity     = $quantity;
            $order->total_amount = (int) $listing->price * $quantity;
            $order->status       = OrderStatus::MenungguKonfirmasi;

            if ($request->filled('shipping_latitude')) {
                $order->setLocation(
                    (float) $request->validated('shipping_latitude'),
                    (float) $request->validated('shipping_longitude'),
                    'shipping_location',
                );
            }

            $order->save();

            return $order;
        });
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            $order = Order::where('buyer_id', $buyerId)
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();
        }

        return $this->created(['order' => new OrderResource($order->load(['store', 'listing']))]);
    }

    private function idempotencyKey(Request $request): string
    {
        $key = (string) $request->header('Idempotency-Key');
        abort_unless(preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $key) === 1, 422,
            'Header Idempotency-Key wajib diisi (16–128 karakter aman).');

        return $key;
    }

    /**
     * PATCH /orders/{order}/status
     *
     * Sah tidaknya perpindahan diputuskan OrderStateMachine — controller
     * hanya menerjemahkan penolakannya menjadi 422 yang bisa dibaca klien.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorize('updateStatus', $order);

        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'reason' => ['required_if:status,dibatalkan', 'nullable', 'string', 'max:255'],
        ]);

        $to = OrderStatus::from($data['status']);
        $userId = $request->user()->id;

        if (! $this->states->canTransition($order->status, $to, $order->store?->user_id === $userId)) {
            // Transisi status tidak sah — konflik bisnis (state machine), bukan
            // validasi input. 409 Conflict supaya klien bisa bedakan dari
            // input tidak valid (422).
            return $this->fail(
                sprintf('Status tidak bisa diubah dari "%s" ke "%s".', $order->status->value, $to->value),
                409,
            );
        }

        $this->states->transition($order, $to, $data['reason'] ?? null, $userId);
        $order->save();

        return $this->ok(['order' => new OrderResource($order->fresh())]);
    }

    /** POST /orders/{order}/payment-proof */
    public function uploadPaymentProof(Request $request, Order $order): JsonResponse
    {
        $this->authorize('uploadPaymentProof', $order);

        $request->validate([
            'proof' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
        ]);

        // Bucket privat: bukti transfer memuat nomor rekening & nominal.
        $path = $request->file('proof')->store("payment-proofs/{$order->id}", 'local');

        $order->payment_proof_url = $path;
        $order->save();

        return $this->ok(['order' => new OrderResource($order->fresh())]);
    }

    /**
     * POST /orders/{order}/review
     *
     * Dua arah: pembeli menilai toko, penjual menilai pembeli. Yang
     * memengaruhi rating toko HANYA arah buyer_to_store (DATABASE.md §4.8).
     */
    public function review(Request $request, Order $order): JsonResponse
    {
        $this->authorize('review', $order);

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $user      = $request->user();
        $isBuyer   = $order->buyer_id === $user->id;
        $direction = $isBuyer ? ReviewDirection::BuyerToStore : ReviewDirection::StoreToBuyer;

        if ($order->reviews()->where('direction', $direction)->exists()) {
            // Ulasan ganda — 409 Conflict (duplikasi bisnis), bukan 422 validasi.
            return $this->fail('Anda sudah memberi ulasan untuk pesanan ini.', 409);
        }

        $review = Review::create([
            'order_id'    => $order->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $isBuyer ? $order->store->user_id : $order->buyer_id,
            // store_id HANYA untuk arah buyer_to_store — CHECK constraint
            // reviews_store_direction_chk menolak selain itu.
            'store_id'    => $isBuyer ? $order->store_id : null,
            'direction'   => $direction,
            'rating'      => $data['rating'],
            'comment'     => $data['comment'] ?? null,
        ]);

        return $this->created(['review' => new ReviewResource($review)]);
    }

    /** POST /orders/{order}/disputes */
    public function dispute(Request $request, Order $order): JsonResponse
    {
        $this->authorize('dispute', $order);

        $data = $request->validate([
            'reason'      => ['required', Rule::enum(\App\Enums\DisputeReason::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($order->disputes()->exists()) {
            // Dispute ganda — 409 Conflict (duplikasi bisnis).
            return $this->fail('Laporan untuk pesanan ini sudah ada.', 409);
        }

        $dispute = DB::transaction(function () use ($order, $data, $request): Dispute {
            $dispute = Dispute::create([
                'order_id'    => $order->id,
                'reported_by' => $request->user()->id,
                'reason'      => $data['reason'],
                'description' => $data['description'] ?? null,
                // SLA tanggapan admin, dari pengaturan (PRD §5.5).
                'response_deadline' => now()->addHours(
                    $this->settings->int('dispute_sla_hours', 24)
                ),
            ]);

            // Pesanan dibekukan: semua transisi lain diblokir sampai admin
            // memutuskan (API §9).
            $this->states->transition($order, OrderStatus::Dispute);
            $order->save();

            return $dispute;
        });

        return $this->created(['dispute' => new DisputeResource($dispute)]);
    }

    /**
     * GET /orders/{order}/payment-proof
     *
     * Sajikan bukti bayar dari disk privat (local) ke pembeli/penjual yang
     * terlibat dalam transaksi. Berkas tidak boleh diakses publik — route
     * ini adalah satu-satunya jalan untuk melihatnya dari aplikasi.
     */
    public function paymentProof(Order $order): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $order);

        $path = $order->getRawOriginal('payment_proof_url');

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $response = Storage::disk('local')->response($path);
        $response->headers->set('Content-Type', Storage::disk('local')->mimeType($path));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }
}
