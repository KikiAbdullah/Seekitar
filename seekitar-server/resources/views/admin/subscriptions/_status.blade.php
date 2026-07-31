@php
    $warna = match ($s->status) {
        'active'    => 'success',
        'pending'   => 'warning',
        'expired'   => 'secondary',
        'cancelled' => 'danger',
        default     => 'secondary',
    };
@endphp
<span class="badge bg-{{ $warna }}-subtle text-{{ $warna }}">{{ match ($s->status) { 'active' => 'Aktif', 'pending' => 'Tertunda', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', default => ucfirst($s->status) } }}</span>
@if ($s->status === 'active' && $s->ends_at?->isPast())
    <span class="badge bg-danger-subtle text-danger ms-1">Lewat</span>
@endif
