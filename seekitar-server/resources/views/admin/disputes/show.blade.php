@extends('admin.layouts.admin')

@section('title', 'Detail Laporan Masalah — Seekitar')

@section('content')
  <div class="row">
    <!-- Left: Dispute details & Order Info -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
              <span class="badge bg-light-danger text-danger fw-semibold fs-2 text-capitalize mb-2">
                @php
                  $reasons = [
                    'item_not_received' => 'Barang Tidak Diterima',
                    'item_damaged' => 'Barang Rusak/Cacat',
                    'item_not_matching' => 'Barang Tidak Sesuai Deskripsi',
                    'others' => 'Lainnya'
                  ];
                  echo $reasons[$dispute->reason->value ?? $dispute->reason] ?? $dispute->reason;
                @endphp
              </span>
              <h3 class="fw-bold mb-0 text-dark">Laporan Sengketa Pesanan #{{ $dispute->order?->order_number }}</h3>
              <p class="text-muted mb-0 fs-3">Dilaporkan pada {{ $dispute->created_at?->format('d M Y H:i') }}</p>
            </div>
            <div class="mt-3 mt-md-0">
              @php
                $d_colors = [
                  'open' => 'warning',
                  'resolved' => 'success'
                ];
                $color = $d_colors[$dispute->status->value ?? $dispute->status] ?? 'secondary';
                $label = $dispute->status->value === 'open' ? 'Terbuka' : 'Selesai';
              @endphp
              <span class="badge bg-light-{{ $color }} text-{{ $color }} fw-bold px-3 py-2 fs-3">
                {{ $label }}
              </span>
            </div>
          </div>

          <h5 class="fw-bold mb-3 text-dark">Detail Keluhan</h5>
          <div class="border rounded p-3 bg-light mb-4">
            <span class="text-muted fs-2 d-block mb-1">Pesan Pengaduan:</span>
            <p class="mb-0 text-dark fs-3" style="white-space: pre-wrap; line-height: 1.6;">{{ $dispute->description ?? 'Tidak ada deskripsi tambahan.' }}</p>
          </div>

          <!-- Order Info -->
          @if ($dispute->order)
            <h5 class="fw-bold mb-3 text-dark">Pesanan Terkait</h5>
            <div class="table-responsive border rounded p-3 bg-white mb-4">
              <table class="table table-borderless align-middle mb-0 fs-3">
                <tbody>
                  <tr>
                    <td class="text-muted ps-0" style="width: 200px;">ID Pesanan</td>
                    <td class="fw-bold text-dark">#{{ $dispute->order->order_number }}</td>
                  </tr>
                  <tr>
                    <td class="text-muted ps-0">Total Pembayaran</td>
                    <td class="fw-bold text-primary fs-4">Rp {{ number_format($dispute->order->total_amount, 0, ',', '.') }}</td>
                  </tr>
                  <tr>
                    <td class="text-muted ps-0">Pembeli</td>
                    <td class="text-dark">{{ $dispute->order->buyer?->name }} ({{ $dispute->order->buyer?->phone }})</td>
                  </tr>
                  <tr>
                    <td class="text-muted ps-0">Toko Penjual</td>
                    <td class="text-dark">{{ $dispute->order->store?->name }}</td>
                  </tr>
                  <tr>
                    <td class="text-muted ps-0">Status Pesanan saat ini</td>
                    <td class="fw-semibold text-dark">{{ $dispute->order->status?->label() }}</td>
                  </tr>
                </tbody>
              </table>
              <a href="{{ route('admin.orders.show', $dispute->order_id) }}" class="btn btn-sm btn-outline-primary mt-3"><i class="ti ti-credit-card me-1"></i> Detail Pesanan Lengkap</a>
            </div>
          @endif
        </div>
      </div>

      <!-- Resolution History (if resolved) -->
      @if ($dispute->status->value === 'resolved')
        <div class="card shadow-sm mt-4">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3 text-success"><i class="ti ti-circle-check"></i> Hasil Keputusan Mediasi</h5>
            <div class="table-responsive border rounded p-3 bg-light mb-0 fs-3">
              <table class="table table-borderless align-middle mb-0">
                <tbody>
                  <tr>
                    <td class="text-muted ps-0" style="width: 200px;">Ditangani Oleh</td>
                    <td class="fw-semibold text-dark">{{ $dispute->assignee?->name ?? 'Sistem' }}</td>
                  </tr>
                  <tr>
                    <td class="text-muted ps-0">Tanggal Keputusan</td>
                    <td class="text-dark">{{ $dispute->resolved_at?->format('d M Y H:i') }}</td>
                  </tr>
                  <tr>
                    <td class="text-muted ps-0">Keputusan Akhir</td>
                    <td class="fw-bold text-dark text-capitalize">
                      {{ $dispute->order->status->value === 'completed' ? 'Dana Diteruskan ke Penjual (Selesai)' : 'Dana Dikembalikan ke Pembeli (Dibatalkan)' }}
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted ps-0">Catatan Mediasi</td>
                    <td class="text-dark" style="white-space: pre-wrap;">{{ $dispute->resolution_note }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif
    </div>

    <!-- Right: Reporter & Resolve Form -->
    <div class="col-lg-4">
      <!-- Reporter Info Card -->
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Pelapor Masalah</h5>
          <div class="d-flex align-items-center gap-3">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px;">
              {{ $dispute->reporter?->initials }}
            </span>
            <div>
              <h6 class="fw-bold mb-0 text-dark">{{ $dispute->reporter?->name }}</h6>
              <span class="fs-2 text-muted">{{ $dispute->reporter?->phone }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- SLA Deadline Info -->
      @if ($dispute->status->value === 'open')
        <div class="card bg-light-warning shadow-sm mb-4">
          <div class="card-body p-4">
            <h5 class="fw-bold text-warning mb-2"><i class="ti ti-clock"></i> Tenggat Tanggapan (SLA)</h5>
            <p class="fs-3 text-dark mb-0"><strong>Batas SLA:</strong> {{ $dispute->response_deadline?->format('d M Y H:i') }}</p>
            @if ($dispute->response_deadline?->isPast())
              <span class="badge bg-danger text-white fw-bold mt-2">SLA TERLAMPAUI (Overdue)</span>
            @else
              <span class="badge bg-success text-white fw-bold mt-2">Sisa Waktu: {{ $dispute->response_deadline?->diffForHumans() }}</span>
            @endif
          </div>
        </div>

        <!-- Resolve Form -->
        @can('manage-disputes')
          <div class="card border">
            <div class="card-body p-4">
              <h5 class="fw-bold text-dark mb-3">Mediasi & Keputusan Admin</h5>
              <p class="text-muted fs-2">Admin berhak mengambil keputusan mediasi untuk melepaskan dana ke penjual atau mengembalikannya ke pembeli.</p>
              
              <form action="{{ route('admin.disputes.resolve', $dispute) }}" method="POST">
                @csrf
                
                <div class="mb-3">
                  <label for="resolution" class="form-label fw-semibold text-dark fs-3">Keputusan Akhir</label>
                  <select class="form-select @error('resolution') is-invalid @enderror" id="resolution" name="resolution" required>
                    <option value="">Pilih Keputusan</option>
                    <option value="selesai" {{ old('resolution') === 'selesai' ? 'selected' : '' }}>Selesaikan (Teruskan Dana ke Penjual)</option>
                    <option value="dibatalkan" {{ old('resolution') === 'dibatalkan' ? 'selected' : '' }}>Batalkan (Kembalikan Dana ke Pembeli)</option>
                  </select>
                  @error('resolution')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="resolution_note" class="form-label fw-semibold text-dark fs-3">Catatan / Alasan Keputusan</label>
                  <textarea class="form-control @error('resolution_note') is-invalid @enderror" id="resolution_note" name="resolution_note" rows="4" placeholder="Jelaskan alasan hukum / mediasi keputusan Anda..." required maxlength="2000">{{ old('resolution_note') }}</textarea>
                  @error('resolution_note')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-hover-shadow py-2 fw-semibold" onclick="return confirm('Apakah Anda yakin ingin memproses keputusan mediasi ini? Tindakan ini bersifat permanen dan tidak dapat diubah.');">
                  <i class="ti ti-circle-check fs-5 me-1"></i> Kirim Keputusan
                </button>
              </form>
            </div>
          </div>
        @endcan
      @endif
    </div>
  </div>
@endsection
