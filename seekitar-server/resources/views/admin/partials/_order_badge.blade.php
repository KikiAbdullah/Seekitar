{{--
    Badge status pesanan berwarna mengikuti OrderStatus::color()
    (BRANDING-GUIDELINE §3.5.3) dengan label kontekstual statusLabel().

    Latar kuning ("menunggu konfirmasi") diberi teks gelap supaya tetap
    terbaca; warna lain memakai teks putih.

    $order  App\Models\Order — status tidak pernah null (default DB).
--}}
@php
    $teksGelap = $order->status->value === 'menunggu_konfirmasi';
@endphp
<span class="badge"
      style="background-color: {{ $order->status->color() }};
             color: {{ $teksGelap ? '#3f3f46' : '#ffffff' }}"
>{{ $order->statusLabel() }}</span>
