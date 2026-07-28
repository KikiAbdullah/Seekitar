{{-- Aksi baris pesanan — hanya baca; admin tidak mengubah status dari sini. --}}
@can('manage-orders')
    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
        <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Lihat
    </a>

    <span class="text-muted small ms-1">{{ $order->order_number }}</span>
@endcan
