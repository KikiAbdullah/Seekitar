@php
  $type = $listing->listing_type->value ?? $listing->listing_type;
  $labels = ['product' => 'Barang', 'service' => 'Jasa', 'rental' => 'Sewa'];
  $tones = ['product' => 'primary', 'service' => 'info', 'rental' => 'warning'];
  $label = $labels[$type] ?? '—';
  $tone = $tones[$type] ?? 'secondary';
@endphp
<span class="badge bg-light-{{ $tone }} text-{{ $tone }} fw-semibold fs-2">{{ $label }}</span>
