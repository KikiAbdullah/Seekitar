{{--
    Sel nomor pesanan untuk DataTables Pesanan (render per baris di server).

    Nomor monospasi tertaut ke detail; baris bawahnya merangkum tipe,
    jumlah, dan cara pemenuhan dalam satu baris ikon — admin membaca
    "pesanan apa ini" tanpa membuka halamannya.

    $order — model Order baris (order_type, quantity, delivery_method).
--}}
<span class="d-flex flex-column">
    <a href="{{ route('admin.orders.show', $order) }}"
       class="fw-semibold text-decoration-none font-monospace">{{ $order->order_number }}</a>
    <small class="text-muted text-nowrap">
        {{ $order->order_type?->label() }} · × {{ $order->quantity }} ·
        @if ($order->delivery_method === \App\Enums\DeliveryMethod::Delivery)
            <i class="fa-regular fa-paper-plane" aria-hidden="true"></i> diantar
        @else
            <i class="fa-regular fa-credit-card" aria-hidden="true"></i> ambil sendiri
        @endif
    </small>
</span>
