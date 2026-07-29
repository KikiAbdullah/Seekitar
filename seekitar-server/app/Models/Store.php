<?php

namespace App\Models;

use App\Enums\StoreType;
use App\Enums\VerificationStatus;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\SerializesDatesAsUtc;
use App\Support\PlaceholderImg;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, HasLocation, HasUuids, SerializesDatesAsUtc, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'photo', 'regency', 'regency_code', 'store_type', 'category_ids',
        'address', 'service_radius_km', 'accepts_cod', 'offers_delivery',
        'allows_pickup', 'operating_hours', 'npwp', 'bank_account',
        'is_active', 'verification_status', 'rejected_reason', 'verified_at', 'verified_by',
    ];

    protected $hidden = ['npwp'];

    /** Default kolom agar instance baru konsisten dengan skema. */
    protected $attributes = [
        'service_radius_km'   => 5.00,
        'accepts_cod'         => true,
        'offers_delivery'     => false,
        'allows_pickup'       => true,
        'rating_avg'          => 0,
        'total_reviews'       => 0,
        'is_active'           => true,
        'verification_status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'category_ids'        => 'array',
            'operating_hours'     => 'array',
            'service_radius_km'   => 'decimal:2',
            'rating_avg'          => 'decimal:2',
            'accepts_cod'         => 'boolean',
            'offers_delivery'     => 'boolean',
            'allows_pickup'       => 'boolean',
            'is_active'           => 'boolean',
            'verification_status' => VerificationStatus::class,
            'verified_at'         => 'datetime',
        ];
    }

    /**
     * store_type disimpan sebagai SET (MySQL) / string dipisah koma,
     * tetapi API selalu memakai array (API_DOCUMENTATION.md §3.1).
     */
    protected function storeType(): Attribute
    {
        return Attribute::make(
            get: fn (?string $v) => $v ? array_map(
                fn ($t) => StoreType::from($t), explode(',', $v)
            ) : [],
            set: fn (array $v) => implode(',', array_map(
                fn ($t) => $t instanceof StoreType ? $t->value : $t, $v
            )),
        );
    }

    /*
     * Foto etalase selalu punya URL: tanpa unggahan, jatuh ke placeholder
     * berseed id toko (lihat PlaceholderImg). Blade & API tinggal memakai
     * $store->photo tanpa cabang "ada/tidak ada" di tiap layar.
     * Filter "sudah/tidak ada foto" tidak ada di fitur mana pun, sehingga
     * tidak ada logika yang rusak oleh fallback ini.
     */
    protected function photo(): Attribute
    {
        return Attribute::get(
            fn (?string $v) => $v ?: PlaceholderImg::url('toko-'.$this->getKey(), 600, 400)
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Admin yang menyetujui toko ini (pasangan verified_at). */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Hanya ulasan buyer_to_store yang menghitung rating (DATABASE.md §4.8). */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)
            ->where('direction', \App\Enums\ReviewDirection::BuyerToStore->value);
    }

    public function isVisible(): bool
    {
        return $this->is_active
            && $this->verification_status === VerificationStatus::Verified;
    }

    public function handles(\App\Enums\ListingType $type): bool
    {
        foreach ($this->store_type as $st) {
            if ($st->allowsListingType($type)) {
                return true;
            }
        }
        return false;
    }
}
