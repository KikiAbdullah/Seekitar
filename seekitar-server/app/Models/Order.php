<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\SerializesDatesAsUtc;
use App\Support\PlaceholderImg;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /*
     * HasLocation dibutuhkan kolom `shipping_location` (POINT SRID 4326).
     *
     * Sebelumnya trait ini TIDAK dipasang meski kolomnya ada di migrasi,
     * sehingga `setLocation(..., 'shipping_location')` melempar
     * "Call to undefined method App\Models\Order::setLocation()" — dan tidak
     * ada satu pun jalur yang bisa mengisi titik tujuan antar. Kesalahan yang
     * sama pernah terjadi pada `users.location`.
     */
    use HasFactory, HasLocation, HasUuids, SerializesDatesAsUtc;

    protected $fillable = [
        'order_number', 'buyer_id', 'store_id', 'offer_id', 'listing_id',
        'order_type', 'quantity', 'total_amount', 'status',
        'payment_method', 'delivery_method', 'shipping_address',
        'payment_proof_url', 'payment_confirmed_at', 'notes',
        'completed_at', 'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected $attributes = [
        'quantity'        => 1,
        'status'          => 'menunggu_konfirmasi',
        'delivery_method' => 'pickup',
    ];

    protected function casts(): array
    {
        return [
            'order_type'           => OrderType::class,
            'status'               => OrderStatus::class,
            'payment_method'       => PaymentMethod::class,
            'delivery_method'      => DeliveryMethod::class,
            'total_amount'         => 'decimal:2',
            'payment_confirmed_at' => 'datetime',
            'completed_at'         => 'datetime',
            'cancelled_at'         => 'datetime',
        ];
    }

    /*
     * Bukti bayar selalu punya URL tampilan (placeholder berseed nomor
     * pesanan bila kosong — PlaceholderImg). Blade detail tidak lagi butuh
     * cabang "belum ada bukti" untuk merender spot gambarnya.
     */
    protected function paymentProofUrl(): Attribute
    {
        return Attribute::get(
            fn (?string $v) => $v ?: PlaceholderImg::url('bukti-'.$this->getKey(), 600, 400)
        );
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Siapa yang membatalkan; NULL bila dibatalkan sistem atau belum batal. */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    /** Label yang tampil ke pengguna, bergantung tipe & metode kirim. */
    public function statusLabel(): string
    {
        return $this->status->contextualLabel($this->order_type, $this->delivery_method);
    }

    /** Jendela ulasan 7 hari sejak selesai (PRD §5.4.1). */
    public function acceptsReview(): bool
    {
        return $this->status === OrderStatus::Selesai
            && $this->completed_at !== null
            && $this->completed_at->diffInDays(now()) <= 7;
    }
}
