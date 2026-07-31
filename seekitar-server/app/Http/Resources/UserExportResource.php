<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Struktur data ekspor portabilitas (UU PDP pasal 8).
 *
 * @mixin \App\Models\User
 */
class UserExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'profil' => [
                'nama'      => $this->name,
                'email'     => $this->email,
                'telepon'   => $this->phone,
                'bergabung' => $this->created_at?->format('Y-m-d'),
            ],
            'toko' => $this->relationLoaded('stores')
                ? $this->stores->map(fn ($s) => [
                    'nama'    => $s->name,
                    'alamat'  => $s->address,
                    'status'  => $s->status?->label(),
                    'listing' => $s->relationLoaded('listings') ? $s->listings->count() : 0,
                ])
                : [],
            'permintaan' => $this->relationLoaded('customerRequests')
                ? $this->customerRequests->map(fn ($r) => [
                    'judul'  => $r->title,
                    'status' => $r->status?->label(),
                    'dibuat' => $r->created_at?->format('Y-m-d'),
                ])
                : [],
            'pesanan' => $this->relationLoaded('orders')
                ? $this->orders->map(fn ($o) => [
                    'nomor'   => $o->order_number,
                    'total'   => (int) $o->total_amount,
                    'status'  => $o->status?->label(),
                    'dibuat'  => $o->created_at?->format('Y-m-d'),
                ])
                : [],
        ];
    }
}
