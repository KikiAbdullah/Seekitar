<?php

namespace App\Models;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc;

    protected $fillable = [
        'order_id', 'reported_by', 'reason', 'description', 'status',
        'response_deadline', 'first_responded_at', 'escalated_at',
        'assigned_to', 'resolution_note', 'resolved_at',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'reason'             => DisputeReason::class,
            'status'             => DisputeStatus::class,
            'response_deadline'  => 'datetime',
            'first_responded_at' => 'datetime',
            'escalated_at'       => 'datetime',
            'resolved_at'        => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /** SLA terlewat = belum direspons DAN sudah lewat tenggat. */
    public function isOverdue(): bool
    {
        return $this->status === DisputeStatus::Open
            && $this->first_responded_at === null
            && $this->response_deadline->isPast();
    }
}
