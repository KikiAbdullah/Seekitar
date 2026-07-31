<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advertisement extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc;

    protected $fillable = [
        'title', 'description', 'image_url', 'link_url',
        'position', 'price_per_day', 'buyer_id',
        'status', 'starts_at', 'ends_at',
        'impression_count', 'click_count',
    ];

    protected function casts(): array
    {
        return [
            'price_per_day'     => 'decimal:2',
            'starts_at'         => 'datetime',
            'ends_at'           => 'datetime',
            'impression_count'  => 'integer',
            'click_count'       => 'integer',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function positionLabel(): string
    {
        return match ($this->position) {
            'feed'     => 'Feed Beranda',
            'sidebar'  => 'Sidebar',
            'search'   => 'Hasil Pencarian',
            'category' => 'Halaman Kategori',
            default    => $this->position,
        };
    }
}
