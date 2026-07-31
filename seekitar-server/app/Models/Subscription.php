<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc;

    protected $fillable = [
        'user_id', 'store_id', 'plan', 'status',
        'amount', 'starts_at', 'ends_at', 'payment_ref', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'    => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active')->where('ends_at', '>', now());
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            'pro_monthly'   => 'Pro Bulanan',
            'boost_listing' => 'Boost Listing',
            default         => $this->plan,
        };
    }
}
