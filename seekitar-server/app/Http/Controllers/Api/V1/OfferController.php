<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\RequestStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOfferRequest;
use App\Http\Resources\OfferResource;
use App\Http\Resources\OrderResource;
use App\Models\CustomerRequest;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Store;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SettingService $settings) {}

    /** POST /requests/{customerRequest}/offers */
    public function store(StoreOfferRequest $request, CustomerRequest $customerRequest): JsonResponse
    {
        $store = Store::findOrFail($request->validated('store_id'));

        $this->authorize('createFor', [Offer::class, $store, $customerRequest]);

        // Satu toko satu penawaran per permintaan — kalau tidak, penyedia
        // bisa membanjiri daftar dan menenggelamkan pesaing.
        $exists = $customerRequest->offers()
            ->where('store_id', $store->id)
            ->whereIn('status', [OfferStatus::Pending, OfferStatus::Accepted])
            ->exists();

        if ($exists) {
            return $this->fail('Toko ini sudah mengirim penawaran untuk permintaan tersebut.', 422);
        }

        $offer = new Offer($request->safe()->except('store_id'));
        $offer->request_id = $customerRequest->id;
        $offer->store_id   = $store->id;
        $offer->status     = OfferStatus::Pending;

        // Penawaran tidak boleh hidup lebih lama dari permintaannya —
        // penawaran yang masih "aktif" pada permintaan mati membingungkan.
        $offerExpiry = now()->addHours($this->settings->int('offer_expiry_hours', 48));
        $offer->expires_at = $offerExpiry->min($customerRequest->expires_at);

        $offer->save();

        return $this->created(['offer' => new OfferResource($offer->load('store'))]);
    }

    /**
     * PATCH /offers/{offer}/accept
     *
     * Tiga hal terjadi sekaligus dan HARUS atomik: penawaran diterima,
     * penawaran lain ditolak, permintaan ditutup, dan pesanan terbentuk.
     * Kalau salah satu gagal di tengah, pembeli bisa punya dua pesanan untuk
     * satu kebutuhan — karena itu semuanya dibungkus satu transaksi.
     */
    public function accept(Offer $offer): JsonResponse
    {
        $this->authorize('accept', $offer);

        $order = DB::transaction(function () use ($offer): Order {
            // Kunci baris supaya dua permintaan bersamaan tidak sama-sama
            // lolos pemeriksaan status (race condition klasik double-accept).
            $request = CustomerRequest::whereKey($offer->request_id)->lockForUpdate()->firstOrFail();

            if ($request->status !== RequestStatus::Open) {
                abort(422, 'Permintaan sudah ditutup.');
            }

            $offer->refresh();
            if ($offer->status !== OfferStatus::Pending) {
                abort(422, 'Penawaran sudah tidak berlaku.');
            }

            $offer->status = OfferStatus::Accepted;
            $offer->save();

            // Semua penawaran lain otomatis ditolak (API §6.3).
            Offer::where('request_id', $request->id)
                ->whereKeyNot($offer->id)
                ->where('status', OfferStatus::Pending)
                ->update(['status' => OfferStatus::Rejected->value]);

            $request->status            = RequestStatus::Closed;
            $request->accepted_offer_id = $offer->id;
            $request->save();

            $store = $offer->store()->firstOrFail();

            return Order::create([
                'buyer_id'   => $request->user_id,
                'store_id'   => $store->id,
                'offer_id'   => $offer->id,
                'listing_id' => null,
                // Tipe diturunkan dari jenis usaha toko; penawaran tidak
                // membawa listing_type sendiri.
                'order_type'      => $this->orderTypeFor($store),
                'quantity'        => 1,
                'total_amount'    => (int) $offer->price + (int) $offer->additional_cost,
                'status'          => OrderStatus::MenungguKonfirmasi,
                'payment_method'  => PaymentMethod::Cod,
                'notes'           => $offer->notes,
            ]);
        });

        return $this->ok(['order' => new OrderResource($order)]);
    }

    /**
     * Tipe pesanan dari jenis usaha toko.
     *
     * `store_type` bisa berisi beberapa nilai; yang dipilih adalah yang
     * paling spesifik untuk transaksi. Default `service` karena mayoritas
     * permintaan di Seekitar berupa jasa (PRD §5.2).
     */
    private function orderTypeFor(Store $store): OrderType
    {
        $types = (array) $store->store_type;
        $values = array_map(
            static fn ($t) => $t instanceof \BackedEnum ? $t->value : (string) $t,
            $types,
        );

        return match (true) {
            in_array('rental', $values, true)   => OrderType::Rental,
            in_array('goods', $values, true)    => OrderType::Product,
            default                             => OrderType::Service,
        };
    }
}
