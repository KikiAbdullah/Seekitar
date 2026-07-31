<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc;

    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount', 'max_discount',
        'usage_limit', 'usage_per_user', 'used_count', 'is_active',
        'starts_at', 'expires_at', 'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'starts_at'       => 'datetime',
            'expires_at'      => 'datetime',
            'value'           => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount'    => 'decimal:2',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function canUse(User $user): bool
    {
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        $count = $this->usages()->where('user_id', $user->id)->count();

        return $count < $this->usage_per_user;
    }

    public function calculateDiscount(float $orderTotal): float
    {
        if ($this->min_order_amount !== null && $orderTotal < $this->min_order_amount) {
            return 0.0;
        }

        $discount = match ($this->type) {
            'percentage' => $orderTotal * ($this->value / 100),
            'fixed'      => $this->value,
            default      => 0.0,
        };

        if ($this->type === 'percentage' && $this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $orderTotal), 2);
    }

    public function apply(Order $order, User $user): float
    {
        $discount = $this->calculateDiscount((float) $order->total_amount);

        $this->increment('used_count');

        $this->usages()->create([
            'order_id'        => $order->id,
            'user_id'         => $user->id,
            'discount_amount' => $discount,
        ]);

        return $discount;
    }
}
