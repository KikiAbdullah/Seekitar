<?php

namespace App\Models;

use App\Enums\StoreStatus;
use App\Enums\StoreType;
use App\Models\Concerns\SerializesDatesAsUtc;
use App\Support\Jarak;
use App\Support\PlaceholderImg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Toko / lapak penyedia.
 *
 * Lokasinya `latitude` + `longitude` — kolom DECIMAL biasa, BUKAN POINT
 * (alasan lengkapnya di migrasi). Karena itu Store tidak lagi memakai
 * trait HasLocation: pencarian radius = scopeWithinBox (SQL, Eloquent
 * murni) lalu Jarak::haversineKm (PHP), dan GeoJSON dibaca langsung dari
 * kolomnya tanpa fungsi spasial apa pun.
 */
class Store extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'photo', 'regency', 'regency_code', 'store_type', 'category_ids',
        'address', 'latitude', 'longitude', 'service_radius_km', 'accepts_cod',
        'offers_delivery', 'allows_pickup', 'operating_hours', 'npwp',
        'bank_account', 'bank_account_name',
        'is_active', 'status', 'verified_at', 'verified_by',
        'rejected_at', 'rejected_by', 'rejected_reason',
        'blocked_at', 'blocked_by', 'blocked_reason',
    ];

    protected $hidden = ['npwp'];

    /** Default kolom agar instance baru konsisten dengan skema. */
    protected $attributes = [
        'service_radius_km' => 5.00,
        'accepts_cod'       => true,
        'offers_delivery'   => false,
        'allows_pickup'     => true,
        'rating_avg'        => 0,
        'total_reviews'     => 0,
        'is_active'         => true,
        'status'            => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'category_ids'      => 'array',
            'operating_hours'   => 'array',
            // decimal:8 — 8 angka di belakang koma (≈1,1 mm), dicetak apa
            // adanya oleh cast supaya tidak dibulatkan diam-diam di JSON.
            'latitude'          => 'decimal:8',
            'longitude'         => 'decimal:8',
            'service_radius_km' => 'decimal:2',
            'rating_avg'        => 'decimal:2',
            'accepts_cod'       => 'boolean',
            'offers_delivery'   => 'boolean',
            'allows_pickup'     => 'boolean',
            'is_active'         => 'boolean',
            'status'            => StoreStatus::class,
            'verified_at'       => 'datetime',
            'rejected_at'       => 'datetime',
            'blocked_at'        => 'datetime',
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
     *
     * PENGECUALIAN konteks VERIFIKASI (antrian admin): di sana wajib
     * getRawOriginal('photo'). Placeholder adalah dekorasi publik, bukan
     * bukti — keputusan menyetujui toko tidak boleh berpijak pada gambar
     * yang tidak pernah diunggah pemilik.
     *
     * Nilai database bisa berupa URL absolut (lama) atau path relatif (baru);
     * PlaceholderImg::storageUrl menormalkannya ke URL publik yang benar.
     */
    protected function photo(): Attribute
    {
        return Attribute::get(function (?string $v): string {
            return PlaceholderImg::storageUrl($v) ?? PlaceholderImg::url('toko-'.$this->getKey(), 600, 400, 'Foto Toko');
        });
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

    /** Admin yang menolak pengajuan ini (pasangan rejected_at). */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** Admin yang memblokir bersama pemiliknya (pasangan blocked_at). */
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
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

    /**
     * Antrian verifikasi toko — SATU definisi bersama, dipakai halaman
     * antrian, lencana sidebar, dan API.
     *
     * Toko HANYA boleh masuk antrian bila PEMILIKNYA sudah terverifikasi:
     * menyetujui toko berarti mengesahkan usaha seseorang, dan orang yang
     * identitasnya belum lulus tidak punya usaha untuk disahkan. Tanpa
     * saringan ini, admin membuang waktu menilai toko yang persetujuannya
     * pasti ditolak syarat (canOpenStore) di detik terakhir.
     */
    public function scopePendingVerification(Builder $q): Builder
    {
        return $q
            ->where('status', StoreStatus::Pending)
            ->whereHas('owner', fn (Builder $owner) => $owner->whereNotNull('verified_at'));
    }

    /**
     * Kandidat "dalam radius" lewat kotak pembatas terindeks — tahap SQL
     * dari pencarian radius. Lingkaran akuratnya disaring BELAKANGAN di
     * PHP (Jarak::haversineKm), karena fungsi jarak tidak terindeks.
     */
    public function scopeWithinBox(Builder $q, float $lat, float $lng, float $radiusKm): Builder
    {
        [$latMin, $latMax, $lngMin, $lngMax] = Jarak::kotak($lat, $lng, $radiusKm);

        return $q
            ->whereBetween('latitude', [$latMin, $latMax])
            ->whereBetween('longitude', [$lngMin, $lngMax]);
    }

    /**
     * Keanggotaan SET store_type tanpa FIND_IN_SET mentah.
     *
     * SET MySQL adalah string dipisah koma, dan koma itulah jangkarnya:
     * empat pola LIKE ini mencocokkan ANGGOTA PENUH saja ('goods' tidak
     * akan pernah ikut tersaring oleh 'goods_bekas'), tetap memakai
     * where() biasa — tidak butuh fungsi mentah apa pun.
     */
    public function scopeWhereStoreType(Builder $q, string $type): Builder
    {
        return $q->where(fn (Builder $w) => $w
            ->where('store_type', $type)
            ->orWhere('store_type', 'like', $type.',%')
            ->orWhere('store_type', 'like', '%,'.$type)
            ->orWhere('store_type', 'like', '%,'.$type.',%'));
    }

    /**
     * Koordinat sebagai GeoJSON Point (API §12.3, RFC 7946).
     *
     * GeoJSON selalu [longitude, latitude] — urutannya terbalik dari
     * kebiasaan menulis "lat, lng". Kolomnya DECIMAL, jadi tinggal dibaca.
     */
    public function coordinates(): array
    {
        return [
            'type'        => 'Point',
            'coordinates' => [(float) $this->longitude, (float) $this->latitude],
        ];
    }

    /** Kedudukan toko saat blokir pemilik dicabut (lihat diagram StoreStatus). */
    public function statusSebelumDiblokir(): StoreStatus
    {
        if ($this->verified_at !== null) {
            return StoreStatus::Verified;
        }

        return $this->rejected_at !== null ? StoreStatus::Rejected : StoreStatus::Pending;
    }

    public function isVisible(): bool
    {
        return $this->is_active && $this->status === StoreStatus::Verified;
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
