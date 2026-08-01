<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan konten/pengguna melanggar — terpisah dari Dispute (sengketa transaksi).
 *
 * Seorang pengguna bisa melaporkan listing, toko, atau pengguna lain karena
 * konten ilegal, penipuan, pelecehan, dll.
 */
class Report extends Model
{
    use HasUuids;

    protected $fillable = [
        'reporter_id', 'reportable_type', 'reportable_id',
        'reason', 'description',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportable()
    {
        return $this->morphTo();
    }

    /** Scope: laporan yang belum diselesaikan. */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }
}
