<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Concerns\SerializesDatesAsUtc;
use App\Support\PlaceholderImg;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc, SoftDeletes;

    protected $fillable = [
        'store_id', 'title', 'description', 'listing_type',
        'price', 'stock_qty', 'slot', 'images', 'status',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'listing_type' => ListingType::class,
            'status'       => ListingStatus::class,
            'images'       => 'array',
            'price'        => 'decimal:2',
        ];
    }

    /*
     * Galeri tidak pernah kosong: tanpa unggahan ia berisi SATU gambar
     * placeholder berseed id listing (PlaceholderImg). Accessor get ini
     * berjalan alih-alih cast 'array' (aksesor didahulukan), sehingga ia
     * mendekode kolom mentah JSON-nya sendiri; pada set, cast tetap aktif
     * dan menyimpan JSON seperti biasa. Blade/API menerima array apa pun
     * isinya — $images[0] tidak pernah meledak.
     */
    protected function images(): Attribute
    {
        return Attribute::get(function (array|string|null $value) {
            $images = is_string($value) ? json_decode($value, true) : $value;

            return $images ?: [PlaceholderImg::url('listing-'.$this->getKey(), 800, 600)];
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === ListingStatus::Active && $this->deleted_at === null;
    }
}
