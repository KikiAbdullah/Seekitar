<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Models\Concerns\HasLocation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerRequest extends Model
{
    use HasFactory, HasLocation, HasUuids;

    protected $fillable = [
        'user_id', 'title', 'description', 'category_id',
        'budget_min', 'budget_max', 'images', 'radius_km',
        'required_date', 'expires_at', 'extended_at', 'extension_count',
        'status', 'accepted_offer_id',
    ];

    protected $attributes = [
        'radius_km'       => 15.00,
        'extension_count' => 0,
        'status'          => 'open',
    ];

    protected function casts(): array
    {
        return [
            'status'        => RequestStatus::class,
            'images'        => 'array',
            'budget_min'    => 'decimal:2',
            'budget_max'    => 'decimal:2',
            'radius_km'     => 'decimal:2',
            'required_date' => 'datetime',
            'expires_at'    => 'datetime',
            'extended_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'request_id');
    }

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'accepted_offer_id');
    }

    public function isOpen(): bool
    {
        return $this->status === RequestStatus::Open && $this->expires_at->isFuture();
    }

    /** Harga penawaran harus masuk anggaran, jika pembeli menetapkannya. */
    public function acceptsPrice(float $total): bool
    {
        if ($this->budget_min !== null && $total < (float) $this->budget_min) {
            return false;
        }
        if ($this->budget_max !== null && $total > (float) $this->budget_max) {
            return false;
        }
        return true;
    }
}
