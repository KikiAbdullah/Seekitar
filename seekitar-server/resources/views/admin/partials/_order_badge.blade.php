@php
  $s = $order->status;
  $map = [
    'menunggu_konfirmasi' => ['warning', 'Menunggu Konfirmasi'],
    'diproses'            => ['primary', 'Diproses'],
    'dikirim'             => ['info',    'Dikirim / Siap Diambil'],
    'selesai'             => ['success', 'Selesai'],
    'dibatalkan'          => ['secondary', 'Dibatalkan'],
    'dispute'             => ['danger',  'Dispute'],
  ];
  [$tone, $label] = $map[$s->value] ?? ['secondary', $s->label()];
@endphp
<span class="badge bg-light-{{ $tone }} text-{{ $tone }} fw-semibold fs-2">{{ $label }}</span>
