@extends('admin.layouts.admin')

@section('title', 'Edit Profil Toko — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-md-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
              <h4 class="card-title">Edit Profil Toko</h4>
              <p class="card-subtitle">Sunting profil operasional, wilayah pelayanan, jam buka, dan info rekening toko.</p>
            </div>
            <div>
              <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali ke Detail
              </a>
            </div>
          </div>
          
          <form action="{{ route('admin.stores.update', $store) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
              <!-- Left Column: Basic Info, Location, Service Scope, Operating Hours -->
              <div class="col-lg-8">
                <h5 class="fw-semibold text-dark mb-3">Informasi Dasar</h5>
                
                <div class="mb-3">
                  <label for="name" class="form-label">Nama Toko</label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $store->name) }}" required min="3" max="100">
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label d-block">Tipe Layanan Toko</label>
                  <div class="d-flex flex-wrap gap-3">
                    @foreach ($tipeToko as $type)
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="store_type[]" value="{{ $type->value }}" id="type-{{ $type->value }}" 
                          {{ in_array($type->value, old('store_type', collect($store->store_type)->map->value->all() ?? [])) ? 'checked' : '' }}>
                        <label class="form-check-label text-capitalize" for="type-{{ $type->value }}">
                          {{ $type->value }}
                        </label>
                      </div>
                    @endforeach
                  </div>
                  @error('store_type')
                    <div class="text-danger fs-2 mt-1">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label for="category_ids" class="form-label">Kategori Toko (Maksimal 10 Kategori)</label>
                  <select name="category_ids[]" id="category_ids" class="form-select js-select2 @error('category_ids') is-invalid @enderror" multiple style="height: 150px;" required>
                    @foreach ($kategori as $kat)
                      <option value="{{ $kat->id }}" {{ in_array($kat->id, old('category_ids', $store->category_ids ?? [])) ? 'selected' : '' }}>
                        {{ $kat->name }}
                      </option>
                    @endforeach
                  </select>
                  <div class="form-text">Tahan tombol <code>Ctrl</code> (Windows) / <code>Cmd</code> (Mac) untuk memilih lebih dari satu kategori.</div>
                  @error('category_ids')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <hr class="my-4 text-muted opacity-25">
                
                <h5 class="fw-semibold text-dark mb-3">Domisili & Lokasi Peta</h5>
                
                <div class="mb-3">
                  <label for="address" class="form-label">Alamat Toko</label>
                  <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $store->address) }}">
                  @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="latitude" class="form-label">Lintang (Latitude)</label>
                    <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude', $store->latitude) }}" required>
                    @error('latitude')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-md-6">
                    <label for="longitude" class="form-label">Bujur (Longitude)</label>
                    <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude', $store->longitude) }}" required>
                    @error('longitude')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-12">
                    <div class="form-text text-danger fs-2"><i class="ti ti-info-circle" aria-hidden="true"></i> Koordinat lokasi pin GPS wajib dipasang untuk operasional hyperlocal.</div>
                  </div>
                </div>

                <hr class="my-4 text-muted opacity-25">
                
                <h5 class="fw-semibold text-dark mb-3">Metode Pelayanan & Radius</h5>

                <div class="mb-3">
                  <label for="service_radius_km" class="form-label">Radius Pelayanan (km)</label>
                  <input type="number" step="0.1" class="form-control @error('service_radius_km') is-invalid @enderror" id="service_radius_km" name="service_radius_km" value="{{ old('service_radius_km', $store->service_radius_km) }}" min="0.1" max="50">
                  <div class="form-text">Maksimal radius pengiriman toko ke pembeli (km).</div>
                  @error('service_radius_km')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-4">
                  <label class="form-label d-block">Metode Pemenuhan Pesanan</label>
                  <div class="form-check form-check-inline mb-2">
                    <input class="form-check-input" type="checkbox" name="allows_pickup" value="1" id="allows_pickup" {{ old('allows_pickup', $store->allows_pickup) ? 'checked' : '' }}>
                    <label class="form-check-label text-dark fs-3" for="allows_pickup">Ambil di Tempat (Pickup)</label>
                  </div>
                  <div class="form-check form-check-inline mb-2">
                    <input class="form-check-input" type="checkbox" name="offers_delivery" value="1" id="offers_delivery" {{ old('offers_delivery', $store->offers_delivery) ? 'checked' : '' }}>
                    <label class="form-check-label text-dark fs-3" for="offers_delivery">Pengiriman Toko (Delivery)</label>
                  </div>
                  <div class="form-check form-check-inline mb-2">
                    <input class="form-check-input" type="checkbox" name="accepts_cod" value="1" id="accepts_cod" {{ old('accepts_cod', $store->accepts_cod) ? 'checked' : '' }}>
                    <label class="form-check-label text-dark fs-3" for="accepts_cod">Bayar di Tempat (COD)</label>
                  </div>
                  <div class="form-text text-danger fs-2 mt-1">@error('allows_pickup') {{ $message }} @enderror</div>
                </div>

                <hr class="my-4 text-muted opacity-25">
                
                <h5 class="fw-semibold text-dark mb-3">Jam Operasional Harian</h5>
                <div class="table-responsive">
                  <table class="table align-middle">
                    <thead>
                      <tr class="text-muted fw-semibold">
                        <th scope="col" style="width: 150px;">Hari</th>
                        <th scope="col" style="width: 150px;">Status</th>
                        <th scope="col">Jam Buka</th>
                        <th scope="col">Jam Tutup</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari)
                        @php
                          $jam = $store->operating_hours[$hari] ?? null;
                          $isOpen = !empty($jam);
                        @endphp
                        <tr>
                          <td class="text-capitalize fw-bold text-dark fs-3">{{ $hari }}</td>
                          <td>
                            <div class="form-check form-switch">
                              <input class="form-check-input day-toggle" type="checkbox" name="operating_hours[{{ $hari }}][is_open]" value="1" id="open-{{ $hari }}" {{ old("operating_hours.$hari.is_open", $isOpen) ? 'checked' : '' }}>
                              <label class="form-check-label text-dark fs-3" for="open-{{ $hari }}">Buka</label>
                            </div>
                          </td>
                          <td>
                            <input type="time" class="form-control form-control-sm time-input-{{ $hari }}" name="operating_hours[{{ $hari }}][open]" value="{{ old("operating_hours.$hari.open", $jam['open'] ?? '08:00') }}" {{ !$isOpen ? 'disabled' : '' }}>
                          </td>
                          <td>
                            <input type="time" class="form-control form-control-sm time-input-{{ $hari }} @error("operating_hours.$hari.close") is-invalid @enderror" name="operating_hours[{{ $hari }}][close]" value="{{ old("operating_hours.$hari.close", $jam['close'] ?? '17:00') }}" {{ !$isOpen ? 'disabled' : '' }}>
                            @error("operating_hours.$hari.close")
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
              
              <!-- Right Column: Front Photo & Financial Info -->
              <div class="col-lg-4">
                <div class="card bg-light border-0 shadow-none mb-3">
                  <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Foto Depan Toko</h5>
                    <div class="mb-3 text-center">
                      <img src="{{ \App\Support\PlaceholderImg::src($store->getRawOriginal('photo')) ?? 'https://placehold.co/150x150?text=Toko' }}" class="img-fluid rounded border shadow-sm" style="max-height: 150px; object-fit: cover;">
                    </div>
                    <div class="mb-3">
                      <label for="photo" class="form-label">Unggah Foto Baru</label>
                      <input type="file" class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                      <div class="form-text">Maksimal 5 MB (JPEG, PNG, WEBP).</div>
                      @error('photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
                
                <div class="card bg-light border-0 shadow-none">
                  <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Data Finansial & Pajak</h5>
                    
                    <div class="mb-3">
                      <label for="npwp" class="form-label">NPWP Toko</label>
                      <input type="text" class="form-control @error('npwp') is-invalid @enderror" id="npwp" name="npwp" value="{{ old('npwp', $store->npwp) }}" maxlength="20">
                      @error('npwp')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="bank_account" class="form-label">Nomor Rekening</label>
                      <input type="text" class="form-control @error('bank_account') is-invalid @enderror" id="bank_account" name="bank_account" value="{{ old('bank_account', $store->bank_account) }}" maxlength="100">
                      @error('bank_account')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="bank_account_name" class="form-label">Atas Nama Rekening</label>
                      <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror" id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $store->bank_account_name) }}" maxlength="100">
                      @error('bank_account_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-4 border-top pt-3">
              <button type="submit" class="btn btn-primary px-5 btn-hover-shadow">Simpan Perubahan</button>
              <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-outline-secondary px-4 ms-2">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    $(function() {
      // Toggle Jam Buka/Tutup inputs based on switcher status
      $('.day-toggle').change(function() {
        var day = $(this).attr('id').replace('open-', '');
        var isOpen = $(this).is(':checked');
        $('.time-input-' + day).prop('disabled', !isOpen);
      });
    });
  </script>
@endpush
