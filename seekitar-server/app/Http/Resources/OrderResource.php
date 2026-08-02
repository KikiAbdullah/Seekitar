<?php

namespace App\Http\Resources;

use App\Enums\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'order_number' => $this->order_number,

            'buyer_id'   => $this->buyer_id,
            'store_id'   => $this->store_id,
            'offer_id'   => $this->offer_id,
            'listing_id' => $this->listing_id,

            'order_type'   => $this->order_type?->value,
            'quantity'     => (int) $this->quantity,
            'total_amount' => (int) $this->total_amount,

            'status' => $this->status?->value,
            // Label UI diturunkan dari status + tipe + metode antar; tidak ada
            // nilai ENUM terpisah untuk "Siap Diambil" / "Disewa" (PRD §5.4).
            'status_label' => $this->status?->contextualLabel(
                $this->order_type,
                $this->delivery_method,
            ),

            'payment_method'  => $this->payment_method?->value,
            'delivery_method' => $this->delivery_method?->value,

            // Rekening penjual — HANYA untuk pembeli, dan hanya saat metode
            // bayar transfer. Pembeli butuh nomor ini untuk melakukan
            // transfer; pembeli COD atau pemilik toko tidak melihatnya.
            'bank_account' => $this->when(
                $request->user()?->id === $this->buyer_id
                    && $this->payment_method === PaymentMethod::Transfer,
                $this->store?->bank_account,
            ),
            'bank_account_name' => $this->when(
                $request->user()?->id === $this->buyer_id
                    && $this->payment_method === PaymentMethod::Transfer,
                $this->store?->bank_account_name,
            ),

            'shipping_address'    => $this->shipping_address,
            // Nilai MENTAH, bukan accessor (accessor mengembalikan placeholder
            // saat kosong). Klien butuh membedakan "belum ada bukti" (null)
            // dari "sudah diunggah" untuk menampilkan aksi unggah.
            'payment_proof_url'   => $this->getRawOriginal('payment_proof_url'),
            'payment_confirmed_at' => $this->payment_confirmed_at?->format('Y-m-d\TH:i:s\Z'),

            'notes' => $this->notes,

            'completed_at'  => $this->completed_at?->format('Y-m-d\TH:i:s\Z'),
            'cancelled_at'  => $this->cancelled_at?->format('Y-m-d\TH:i:s\Z'),
            'cancel_reason' => $this->cancel_reason,

            'can_review' => $this->acceptsReview(),

            'store'   => new StoreResource($this->whenLoaded('store')),
            'listing' => new ListingResource($this->whenLoaded('listing')),

            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
