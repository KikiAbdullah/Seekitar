<?php

namespace App\Models;

use App\Enums\OfferStatus;
use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc;

    protected $fillable = [
        'request_id', 'store_id', 'price', 'additional_cost',
        'additional_cost_note', 'estimation_time', 'estimated_hours',
        'notes', 'status', 'expires_at',
    ];

    protected $attributes = [
        'additional_cost' => 0,
        'status'          => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status'          => OfferStatus::class,
            'price'           => 'decimal:2',
            'additional_cost' => 'decimal:2',
            'expires_at'      => 'datetime',
        ];
    }

    /**
     * Yang mengikat adalah TOTAL, bukan price saja. Pengurutan "termurah"
     * memakai nilai ini agar penyedia yang mencantumkan ongkos tidak
     * dirugikan (DATABASE.md §4.6).
     */
    protected function totalAmount(): Attribute
    {
        return Attribute::get(
            fn () => (float) $this->price + (float) $this->additional_cost
        );
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(CustomerRequest::class, 'request_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isPending(): bool
    {
        return $this->status === OfferStatus::Pending && $this->expires_at->isFuture();
    }
}
