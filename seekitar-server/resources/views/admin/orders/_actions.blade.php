@can('manage-orders')
    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Lihat
    </a>
@endcan
