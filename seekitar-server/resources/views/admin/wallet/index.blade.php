@extends('admin.layouts.admin')

@section('title', 'Dompet — Verifikasi Pembayaran — Seekitar')

@push('styles')
  <style>
    .wallet-ref { font-family: ui-monospace, monospace; font-size: 0.8rem; }
  </style>
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card w-100 shadow-sm mb-3">
        <div class="card-body border-bottom">
          <h5 class="card-title fw-semibold mb-0">Top Up Pending (verifikasi pembayaran)</h5>
          <p class="text-muted mb-0 fs-2">Saldo dikredit hanya setelah pembayaran transfer terverifikasi.</p>
        </div>
        <div class="card-body p-0">
          @if ($topups->isEmpty())
            <div class="p-4 text-center text-muted">Tidak ada top up yang menunggu verifikasi.</div>
          @else
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead class="text-dark fs-4">
                  <tr>
                    <th>Referensi</th>
                    <th>Pengguna</th>
                    <th>Jumlah</th>
                    <th>Diajukan</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($topups as $tx)
                    <tr>
                      <td><span class="wallet-ref">{{ $tx->reference }}</span></td>
                      <td>
                        {{ $tx->wallet->user->phone }}
                        <div class="text-muted fs-2">{{ $tx->wallet->user->name }}</div>
                      </td>
                      <td class="fw-semibold">Rp {{ number_format((float) $tx->amount, 0, ',', '.') }}</td>
                      <td>{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
                      <td class="text-end">
                        <form action="{{ route('admin.wallet.topups.confirm', $tx) }}" method="POST" class="d-inline">
                          @csrf
                          <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Konfirmasi top up ini? Saldo akan dikredit.')">Konfirmasi</button>
                        </form>
                        <form action="{{ route('admin.wallet.topups.cancel', $tx) }}" method="POST" class="d-inline">
                          @csrf
                          <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Batalkan top up ini?')">Batal</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <h5 class="card-title fw-semibold mb-0">Penarikan Pending (payout)</h5>
          <p class="text-muted mb-0 fs-2">Dana sudah di-hold dari saldo. Selesaikan payout atau tolak (refund).</p>
        </div>
        <div class="card-body p-0">
          @if ($withdrawals->isEmpty())
            <div class="p-4 text-center text-muted">Tidak ada penarikan yang menunggu.</div>
          @else
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead class="text-dark fs-4">
                  <tr>
                    <th>Referensi</th>
                    <th>Pengguna</th>
                    <th>Jumlah</th>
                    <th>Rekening</th>
                    <th>Diajukan</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($withdrawals as $tx)
                    <tr>
                      <td><span class="wallet-ref">{{ $tx->reference }}</span></td>
                      <td>
                        {{ $tx->wallet->user->phone }}
                        <div class="text-muted fs-2">{{ $tx->wallet->user->name }}</div>
                      </td>
                      <td class="fw-semibold text-danger">Rp {{ number_format(abs((float) $tx->amount), 0, ',', '.') }}</td>
                      <td>{{ $tx->description }}</td>
                      <td>{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
                      <td class="text-end">
                        <form action="{{ route('admin.wallet.withdrawals.complete', $tx) }}" method="POST" class="d-inline">
                          @csrf
                          <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Tandai payout selesai?')">Selesai</button>
                        </form>
                        <form action="{{ route('admin.wallet.withdrawals.reject', $tx) }}" method="POST" class="d-inline">
                          @csrf
                          <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Tolak & kembalikan dana ke saldo?')">Tolak</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
