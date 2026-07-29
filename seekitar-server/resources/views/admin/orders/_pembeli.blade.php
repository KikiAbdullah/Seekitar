{{--
    Sel pembeli untuk DataTables Pesanan (render per baris di server).

    Centang hijau = identitas pembeli sudah disetujui admin (stempel
    verified_at) — bahasa visual yang sama dengan tabel Pengguna.
    Nomor HP di baris bawah mempersingkat kerja admin yang menelpon.

    $order — model Order baris (relasi buyer sudah di-eager-load).
--}}
@if ($order->buyer)
    <span class="d-flex flex-column">
        <span class="d-inline-flex align-items-center">
            @can('manage-users')
                <a href="{{ route('admin.users.show', $order->buyer) }}" class="text-decoration-none">
                    {{ $order->buyer->name }}
                </a>
            @else
                {{ $order->buyer->name }}
            @endcan
            @include('admin.partials._cek_terverifikasi', ['user' => $order->buyer])
        </span>
        @if ($order->buyer->phone)
            <small class="text-muted font-monospace">{{ $order->buyer->phone }}</small>
        @endif
    </span>
@else
    <span class="text-muted">—</span>
@endif
