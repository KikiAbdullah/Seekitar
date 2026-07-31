@extends('admin.layouts.admin')

@section('title', 'Detail Listing — Seekitar')

@section('content')
  <!-- Main Info & Statistics -->
  <div class="row">
    <!-- Left Column: Details & Performance -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
              <span class="badge bg-light-primary text-primary fw-semibold fs-2 text-capitalize mb-2">
                @php
                  $type = $listing->listing_type->value ?? $listing->listing_type;
                  $labels = ['product' => 'Barang', 'service' => 'Jasa', 'rental' => 'Sewa'];
                  echo $labels[$type] ?? $type;
                @endphp
              </span>
              <h3 class="fw-bold mb-0 text-dark">{{ $listing->title }}</h3>
              <p class="text-muted mb-0 fs-3">ID: <code>{{ $listing->id }}</code> | Tanggal posting: {{ $listing->created_at?->format('d M Y H:i') }}</p>
            </div>
            <div class="mt-3 mt-md-0">
              <span class="badge bg-light-{{ $listing->status->color() }} text-{{ $listing->status->color() }} fw-bold px-3 py-2 fs-3">
                {{ $listing->status->label() }}
              </span>
            </div>
          </div>

          <!-- Image Gallery -->
          @php
            $images = is_array($listing->images) ? $listing->images : json_decode($listing->images, true);
          @endphp
          @if (!empty($images))
            <h5 class="fw-bold mb-3 text-dark">Galeri Foto</h5>
            <div class="row g-3 mb-4">
              @foreach ($images as $img)
                <div class="col-6 col-md-4">
                  <a href="{{ asset('storage/' . $img) }}" target="_blank">
                    <img src="{{ asset('storage/' . $img) }}" class="img-fluid rounded border shadow-sm" style="height: 150px; width: 100%; object-fit: cover;" alt="Foto Listing">
                  </a>
                </div>
              @endforeach
            </div>
          @endif

          <h5 class="fw-bold mb-2 text-dark">Deskripsi & Harga</h5>
          <h4 class="text-primary fw-bold mb-3">Rp {{ number_format($listing->price, 0, ',', '.') }}</h4>
          
          <div class="row g-3 mb-4">
            @if ($listing->listing_type->value === 'product')
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light">
                  <span class="text-muted fs-2 d-block">Stok Tersedia</span>
                  <span class="fw-bold text-dark fs-4">{{ $listing->stock_qty }} unit</span>
                </div>
              </div>
            @endif
            @if ($listing->listing_type->value === 'service' || $listing->listing_type->value === 'rental')
              <div class="col-sm-6">
                <div class="border rounded p-2 bg-light">
                  <span class="text-muted fs-2 d-block">Slot/Kapasitas</span>
                  <span class="fw-bold text-dark fs-4">{{ $listing->slot ?? 'unlimited' }}</span>
                </div>
              </div>
            @endif
            <div class="col-sm-6">
              <div class="border rounded p-2 bg-light">
                <span class="text-muted fs-2 d-block">Jumlah Favorit</span>
                <span class="fw-bold text-dark fs-4"><i class="fa-solid fa-heart text-danger me-1"></i> {{ $listing->favorites_count }} pengguna</span>
              </div>
            </div>
          </div>

          <div class="border-top pt-3">
            <h6 class="fw-bold text-dark mb-2">Deskripsi Lengkap:</h6>
            <div class="text-dark fs-3" style="white-space: pre-wrap; line-height: 1.6;">{{ $listing->description }}</div>
          </div>
        </div>
      </div>

      <!-- Performance Stats -->
      <div class="row g-4 mt-1">
        <div class="col-md-4">
          <div class="card border-0 bg-light-primary shadow-sm h-100">
            <div class="card-body p-4">
              <span class="text-muted fs-3 d-block mb-1">Total Pesanan</span>
              <h3 class="fw-bold mb-0 text-dark">{{ number_format($statistik['total']) }}</h3>
              <span class="fs-2 text-muted mt-2 d-block">Semua status</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border-0 bg-light-success shadow-sm h-100">
            <div class="card-body p-4">
              <span class="text-muted fs-3 d-block mb-1">Pesanan Selesai</span>
              <h3 class="fw-bold mb-0 text-dark">{{ number_format($statistik['selesai']) }}</h3>
              <span class="fs-2 text-muted mt-2 d-block">Selesai transaksi</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border-0 bg-light-info shadow-sm h-100">
            <div class="card-body p-4">
              <span class="text-muted fs-3 d-block mb-1">Total Omzet</span>
              <h3 class="fw-bold mb-0 text-dark">Rp {{ number_format($statistik['omzet'], 0, ',', '.') }}</h3>
              <span class="fs-2 text-muted mt-2 d-block">Dari pesanan selesai</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders List -->
      <div class="card shadow-sm mt-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Riwayat Transaksi Terakhir (Maksimal 8)</h5>
          @if ($pesanan->isEmpty())
            <p class="text-muted fs-3 mb-0">Belum ada transaksi untuk listing ini.</p>
          @else
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">No Pesanan</th>
                    <th scope="col">Pembeli</th>
                    <th scope="col">Jumlah</th>
                    <th scope="col">Total Bayar</th>
                    <th scope="col">Status</th>
                    <th scope="col">Tanggal</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($pesanan as $o)
                    <tr>
                      <td>
                        <a href="{{ route('admin.orders.show', $o->id) }}" class="fw-semibold text-primary">#{{ $o->order_number }}</a>
                      </td>
                      <td>
                        <span class="fs-3 text-dark">{{ $o->buyer?->name }}</span>
                      </td>
                      <td>
                        <span class="fs-3 text-dark">{{ $o->quantity }}</span>
                      </td>
                      <td>
                        <span class="fw-bold text-dark fs-3">Rp {{ number_format($o->total_amount, 0, ',', '.') }}</span>
                      </td>
                      <td>
                        @php
                          $o_colors = [
                            'pending' => 'warning',
                            'paid' => 'info',
                            'confirmed' => 'primary',
                            'processing' => 'primary',
                            'shipping' => 'info',
                            'delivered' => 'success',
                            'completed' => 'success',
                            'cancelled' => 'secondary',
                            'dispute' => 'danger'
                          ];
                          $o_color = $o_colors[$o->status->value ?? $o->status] ?? 'secondary';
                          $o_label = $o->status->label() ?? $o->status;
                        @endphp
                        <span class="badge bg-light-{{ $o_color }} text-{{ $o_color }} fw-semibold fs-2">{{ $o_label }}</span>
                      </td>
                      <td>
                        <span class="fs-2 text-muted">{{ $o->created_at?->format('d M Y') }}</span>
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

    <!-- Right Column: Owner Store & Actions -->
    <div class="col-lg-4">
      <!-- Store Info Card -->
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Toko Penjual</h5>
          <div class="d-flex align-items-center gap-3 mb-3">
            <img src="{{ $listing->store?->photo ? asset('storage/' . $listing->store?->photo) : 'https://placehold.co/100x100?text=Toko' }}" class="rounded-circle border" width="48" height="48" style="object-fit: cover;">
            <div>
              <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark">{{ $listing->store?->name }}</h6>
                @if ($listing->store?->status->value === 'verified')
                  <span class="text-success" title="Toko Terverifikasi">
                    <i class="fa-solid fa-circle-check fs-4"></i>
                  </span>
                @endif
              </div>
              <span class="fs-2 text-muted">{{ $listing->store?->regency }}</span>
            </div>
          </div>
          <a href="{{ route('admin.stores.show', $listing->store_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-store me-1"></i> Detail Toko</a>
        </div>
      </div>

      <!-- Fans Card -->
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark"><i class="fa-regular fa-heart text-danger me-1"></i> Favorit Pengguna</h5>
          <p class="card-subtitle mb-4">Daftar pengguna terakhir yang memfavoritkan listing ini.</p>
          
          @if ($penggemar->isEmpty())
            <p class="text-muted fs-3 mb-0">Belum difavoritkan siapa pun.</p>
          @else
            <div class="d-flex flex-column gap-3">
              @foreach ($penggemar as $fav)
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 32px; height: 32px;">
                      {{ $fav->user?->initials }}
                    </span>
                    <span class="fw-semibold text-dark fs-3">{{ $fav->user?->name }}</span>
                  </div>
                  <a href="{{ route('admin.users.show', $fav->user_id) }}" class="btn btn-xs btn-light-info text-info"><i class="ti ti-eye"></i></a>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <!-- Action Takedown -->
      @can('manage-listings')
        <div class="card bg-light-danger shadow-sm">
          <div class="card-body p-4">
            <h5 class="fw-bold text-danger mb-2">Takedown Konten</h5>
            <p class="fs-3 text-dark-danger mb-4">Menghapus listing ini secara permanen dari etalase Seekitar karena melanggar ketentuan/SOP.</p>
            
            <form action="{{ route('admin.listings.destroy', $listing) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin men-takedown/menghapus listing ini secara permanen?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger w-100 btn-hover-shadow py-2 fw-semibold">
                <i class="ti ti-trash fs-5 me-1"></i> Hapus Listing Permanen
              </button>
            </form>
          </div>
        </div>
      @endcan
    </div>
  </div>
@endsection
