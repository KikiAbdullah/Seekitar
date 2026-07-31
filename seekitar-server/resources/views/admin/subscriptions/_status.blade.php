@php
  $status = $s->status;
  $s_colors = [
    'active' => 'success',
    'cancelled' => 'danger',
    'expired' => 'secondary',
    'pending' => 'warning'
  ];
  $color = $s_colors[$status] ?? 'secondary';
  
  $labels = [
    'active' => 'Aktif',
    'cancelled' => 'Dibatalkan',
    'expired' => 'Kedaluwarsa',
    'pending' => 'Menunggu'
  ];
  $label = $labels[$status] ?? $status;
@endphp
<span class="badge bg-light-{{ $color }} text-{{ $color }} fw-semibold fs-2">{{ $label }}</span>
