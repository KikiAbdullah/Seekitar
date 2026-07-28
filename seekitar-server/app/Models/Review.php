<?php

namespace App\Models;

use App\Enums\ReviewDirection;
use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc;

    /**
     * Ulasan tidak bisa disunting setelah dikirim (API_DOCUMENTATION.md §8),
     * jadi tabelnya sengaja tanpa kolom `updated_at`. Tanpa baris ini Eloquent
     * akan mencoba mengisinya dan query gagal.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id', 'reviewer_id', 'reviewee_id', 'store_id',
        'direction', 'rating', 'comment',
    ];

    protected function casts(): array
    {
        return [
            'direction' => ReviewDirection::class,
            'rating'    => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function affectsStoreRating(): bool
    {
        return $this->direction->affectsStoreRating();
    }
}
