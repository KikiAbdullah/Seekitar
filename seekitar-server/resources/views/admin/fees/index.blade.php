@extends('admin.layouts.admin')

@section('title', 'Biaya Layanan — Seekitar')

@inject('settings', 'App\Services\SettingService')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
  <!-- Stats Section -->
  <div class="row">
    <div class="col-md-4">
      <div class="card border-0 bg-light-primary shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h6 class="text-muted mb-1 fs-3">Total Biaya Terkumpul</h6>
              <h3 class="fw-bold mb-0">Rp {{ number_format($total, 0, ',', '.') }}</h3>
            </div>
            <span class="rounded bg-primary-subtle text-primary p-3">
              <i class="fa-solid fa-sack-dollar fs-5"></i>
            </span>
          </div>
          <span class="fs-2 text-muted">Seluruh transaksi sukses</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 bg-light-info shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h6 class="text-muted mb-1 fs-3">Bulan Ini</h6>
              <h3 class="fw-bold mb-0">Rp {{ number_format($bulan, 0, ',', '.') }}</h3>
            </div>
            <span class="rounded bg-info-subtle text-info p-3">
              <i class="fa-solid fa-calendar-days fs-5"></i>
            </span>
          </div>
          <span class="fs-2 text-muted">Bulan berjalan</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 bg-light-success shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h6 class="text-muted mb-1 fs-3">Transaksi Berbayar</h6>
              <h3 class="fw-bold mb-0">{{ number_format($count) }}</h3>
            </div>
            <span class="rounded bg-success-subtle text-success p-3">
              <i class="fa-solid fa-handshake fs-5"></i>
            </span>
          </div>
          <span class="fs-2 text-muted">Transaksi berbiaya layanan</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Fee Configuration Form -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">Pengaturan Biaya</h4>
          <p class="card-subtitle mb-4">Ubah persentase/flat biaya layanan, harga langganan toko PRO, boost listing, dan biaya banner iklan.</p>
          
          <form action="{{ route('admin.fees.update-settings') }}" method="POST">
            @csrf
            
            <div class="mb-3">
              <label for="service_fee_enabled" class="form-label">Status Biaya Layanan Transaksi</label>
              <select class="form-select @error('service_fee_enabled') is-invalid @enderror" id="service_fee_enabled" name="service_fee_enabled" required>
                <option value="1" {{ old('service_fee_enabled', $settings->get('service_fee_enabled')) == '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ old('service_fee_enabled', $settings->get('service_fee_enabled')) == '0' ? 'selected' : '' }}>Nonaktif</option>
              </select>
              @error('service_fee_enabled')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-3">
              <label for="service_fee_amount" class="form-label">Biaya Layanan Transaksi (Rp)</label>
              <input type="number" class="form-control @error('service_fee_amount') is-invalid @enderror" id="service_fee_amount" name="service_fee_amount" value="{{ old('service_fee_amount', $settings->int('service_fee_amount')) }}" min="0" max="100000" required>
              <div class="form-text text-muted">Dikenakan flat per transaksi sukses kepada pembeli.</div>
              @error('service_fee_amount')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-3">
              <label for="pro_monthly_price" class="form-label">Harga Langganan PRO Bulanan (Rp)</label>
              <input type="number" class="form-control @error('pro_monthly_price') is-invalid @enderror" id="pro_monthly_price" name="pro_monthly_price" value="{{ old('pro_monthly_price', $settings->int('pro_monthly_price')) }}" min="0" required>
              @error('pro_monthly_price')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-3">
              <label for="boost_listing_price" class="form-label">Harga Boost Listing per Item (Rp)</label>
              <input type="number" class="form-control @error('boost_listing_price') is-invalid @enderror" id="boost_listing_price" name="boost_listing_price" value="{{ old('boost_listing_price', $settings->int('boost_listing_price')) }}" min="0" required>
              @error('boost_listing_price')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-4">
              <label for="banner_price_per_day" class="form-label">Harga Iklan Banner per Hari (Rp)</label>
              <input type="number" class="form-control @error('banner_price_per_day') is-invalid @enderror" id="banner_price_per_day" name="banner_price_per_day" value="{{ old('banner_price_per_day', $settings->int('banner_price_per_day')) }}" min="0" required>
              @error('banner_price_per_day')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <button type="submit" class="btn btn-primary px-4 btn-hover-shadow">Simpan Pengaturan</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Data Table Transaksi -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">Riwayat Biaya Layanan</h4>
          <p class="card-subtitle mb-4">Daftar transaksi yang menghasilkan biaya layanan bagi platform Seekitar.</p>
          
          <div class="table-responsive">
            <table class="table table-striped table-sm table-bordered align-middle text-nowrap" id="fees-table" style="width: 100%;">
              <thead>
                <tr>
                  <th>No Pesanan</th>
                  <th>Pembeli</th>
                  <th>Toko</th>
                  <th>Total Transaksi</th>
                  <th>Biaya Layanan</th>
                  <th>Tanggal</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('vendor/mordenize/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
  <script>
    $(function () {
      $('#fees-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.fees.data') }}",
        columns: [
          { 
            data: 'order_number',
            name: 'order_number',
            render: function(data, type, row) {
              return '<a href="' + row.order_url + '" class="fw-semibold text-primary">' + data + '</a>';
            }
          },
          { data: 'buyer_name', name: 'buyer_name' },
          { data: 'store_name', name: 'store_name' },
          { data: 'total_amount', name: 'total_amount' },
          { data: 'service_fee', name: 'service_fee' },
          { data: 'created_at', name: 'created_at' }
        ],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        }
      });
    });
  </script>
@endpush
