@php
  $r_colors = [
    'open' => 'success',
    'closed' => 'secondary',
    'expired' => 'danger',
  ];
  $color = $r_colors[$request->status->value ?? $request->status] ?? 'secondary';
  $label = $request->status->label() ?? $request->status;
@endphp
<span class="badge bg-light-{{ $color }} text-{{ $color }} fw-semibold fs-2">{{ $label }}</span>
