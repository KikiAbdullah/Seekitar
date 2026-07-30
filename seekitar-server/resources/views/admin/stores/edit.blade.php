@extends('admin.layout')
@section('title', 'Sunting Toko')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Sunting Toko</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.stores.index') }}">Toko</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.stores.show', $store) }}">{{ $store->name }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Sunting</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Strip identitas: pengaman anti-salah-toko di tiap layar editor. --}}
    <div class="card">
        <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
            @if ($store->photo)
                <a href="{{ $store->photo }}" data-lightbox="store-edit-{{ $store->id }}"
                   data-title="Foto {{ $store->name }}">
                    <img src="{{ $store->photo }}" alt=""
                         width="72" height="54" class="rounded border flex-shrink-0" style="object-fit: cover; cursor: pointer;">
                </a>
            @else
                <span class="rounded bg-light-primary text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0"
                      style="width: 72px; height: 54px;" aria-hidden="true">
                    <i class="ti ti-building-store" aria-hidden="true"></i>
                </span>
            @endif
            <div class="lh-sm">
                <div class="fw-semibold fs-5 d-inline-flex align-items-center">
                    {{ $store->name }}
                </div>
                <div class="text-muted fs-3">
                    Pemilik {{ $store->owner?->name ?? '—' }}
                    <span class="font-monospace">{{ $store->owner?->phone }}</span>
                </div>
            </div>
            <div class="ms-auto">
                @include('admin.stores._status', ['store' => $store])
            </div>
        </div>
    </div>

    {{-- multipart wajib: formulir ini menerima unggahan foto tampak depan. --}}
    <form method="POST" action="{{ route('admin.stores.update', $store) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">

            <div class="col-lg-8">

                {{-- Data pokok toko. --}}
                <div class="card">
                    <div class="card-header fw-semibold">Data Toko</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama toko</label>
                            <input type="text" id="name" name="name"
                                   value="{{ old('name', $store->name) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('name')])
                                   required minlength="3" maxlength="100">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <span class="form-label d-block">Jenis usaha</span>
                            {{-- SET MySQL: centang boleh lebih dari satu — sama seperti di aplikasi. --}}
                            @foreach ($tipeToko as $tipe)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox"
                                           id="tipe-{{ $tipe->value }}" name="store_type[]"
                                           value="{{ $tipe->value }}"
                                           @checked(in_array($tipe->value, array_map(
                                               fn ($t) => $t instanceof \App\Enums\StoreType ? $t->value : $t,
                                               old('store_type', $store->store_type ?? [])), true))>
                                    <label class="form-check-label" for="tipe-{{ $tipe->value }}">{{ $tipe->label() }}</label>
                                </div>
                            @endforeach
                            @error('store_type') <div class="text-danger fs-2 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="category_ids" class="form-label">Kategori</label>
                            <select id="category_ids" name="category_ids[]" multiple
                                    class="form-select js-select2 @error('category_ids') is-invalid @enderror">
                                @php $terpilih = old('category_ids', $store->category_ids ?? []); @endphp
                                @foreach ($kategori as $kat)
                                    <option value="{{ $kat->id }}" @selected(in_array($kat->id, $terpilih))>
                                        {{ $kat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label for="npwp" class="form-label">NPWP <span class="text-muted">(opsional)</span></label>
                            <input type="text" id="npwp" name="npwp"
                                   value="{{ old('npwp', $store->getRawOriginal('npwp')) }}"
                                   @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('npwp')])
                                   maxlength="20" placeholder="12.345.678.9-012.345">
                            @error('npwp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Foto tampak depan — satu-satunya foto toko. --}}
                <div class="card">
                    <div class="card-header fw-semibold">Foto Tampak Depan</div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <a href="{{ $store->photo }}" data-lightbox data-title="Foto {{ $store->name }}">
                                <img id="pratinjau-foto" src="{{ $store->photo }}" alt="Pratinjau foto toko"
                                     class="rounded border flex-shrink-0"
                                     style="width: 200px; height: 140px; object-fit: cover; cursor: pointer;">
                            </a>
                            <div class="flex-grow-1">
                                <label for="photo" class="form-label">Ganti foto</label>
                                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"
                                       @class(['form-control', 'is-invalid' => $errors->has('photo')])
                                       data-foto-input data-pratinjau="#pratinjau-foto">
                                @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">
                                    JPEG/PNG/WebP maks 5 MB. Tepat SATU foto — yang dinilai admin saat
                                    verifikasi dan yang dilihat pembeli di pencarian.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Lokasi: alamat + pin di peta (wajib bagi toko). --}}
                <div class="card">
                    <div class="card-header fw-semibold">Lokasi</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <input type="text" id="address" name="address"
                                   value="{{ old('address', $store->address) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('address')])
                                   maxlength="255" placeholder="Jl. Raya …, Kec. …">
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="rounded border mb-3" style="height: 280px; width: 100%;"
                             id="petaSuntingToko" role="img" aria-label="Peta pemilih titik toko"></div>

                        <div class="row g-2">
                            <div class="col-sm">
                                <label for="latitude" class="form-label text-muted" style="font-size: 11px;">LINTANG</label>
                                <input type="text" inputmode="decimal" id="latitude" name="latitude"
                                       value="{{ old('latitude', (string) (float) $store->latitude) }}"
                                       @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('latitude') || $errors->has('longitude')])
                                       placeholder="-7,5966" data-kolom-titik required>
                            </div>
                            <div class="col-sm">
                                <label for="longitude" class="form-label text-muted" style="font-size: 11px;">BUJUR</label>
                                <input type="text" inputmode="decimal" id="longitude" name="longitude"
                                       value="{{ old('longitude', (string) (float) $store->longitude) }}"
                                       @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('latitude') || $errors->has('longitude')])
                                       placeholder="112,8203" data-kolom-titik required>
                            </div>
                        </div>
                        @if ($errors->has('latitude') || $errors->has('longitude'))
                            <div class="text-danger fs-2 mt-1">
                                {{ $errors->first('latitude') ?: $errors->first('longitude') }}
                            </div>
                        @endif
                        <div class="form-text mt-1">
                            Geser penanda atau klik peta; kolomnya ikut terisi. Mengetik koma (mis.
                            <em>-7,5966</em>) juga boleh — diubah otomatis.
                        </div>
                    </div>
                </div>

                {{-- Operasional: radius, pemenuhan, jam buka. --}}
                <div class="card">
                    <div class="card-header fw-semibold">Operasional</div>
                    <div class="card-body">
                        <div class="mb-3" style="max-width: 260px;">
                            <label for="service_radius_km" class="form-label">Radius layanan (km)</label>
                            <input type="number" step="0.1" min="0.1" max="50" id="service_radius_km"
                                   name="service_radius_km"
                                   value="{{ old('service_radius_km', (string) (float) $store->service_radius_km) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('service_radius_km')])>
                            @error('service_radius_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex flex-wrap gap-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="accepts_cod" name="accepts_cod" value="1"
                                       @checked(old('accepts_cod', $store->accepts_cod))>
                                <label class="form-check-label" for="accepts_cod">Bisa COD</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="offers_delivery" name="offers_delivery" value="1"
                                       @checked(old('offers_delivery', $store->offers_delivery))>
                                <label class="form-check-label" for="offers_delivery">Bisa Diantar</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="allows_pickup" name="allows_pickup" value="1"
                                       @checked(old('allows_pickup', $store->allows_pickup))>
                                <label class="form-check-label" for="allows_pickup">Ambil di Tempat</label>
                            </div>
                        </div>
                        @error('allows_pickup') <div class="text-danger fs-2 mb-3">{{ $message }}</div> @enderror
                        <div class="form-text mb-3">
                            Minimal satu cara pemenuhan harus menyala (diantar atau ambil di tempat).
                        </div>

                        {{-- Editor jam terstruktur: tidak ada JSON mentah yang bisa salah koma. --}}
                        <span class="form-label d-block text-muted" style="font-size: 11px;">JAM OPERASIONAL</span>
                        @php
                            $hariLabel = ['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu',
                                          'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu',
                                          'minggu' => 'Minggu'];
                            $jamAsli   = $store->operating_hours ?? [];
                        @endphp
                        @foreach ($hariLabel as $kunci => $label)
                            @php
                                $hari = old("operating_hours.$kunci", $jamAsli[$kunci] ?? null);
                                $buka = is_array($hari);
                            @endphp
                            <div class="row align-items-center mb-2 g-2">
                                <div class="col-3 col-md-2">{{ $label }}</div>
                                <div class="col-3 col-md-2 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="operating_hours[{{ $kunci }}][is_open]" value="1"
                                           data-hari="{{ $kunci }}" @checked($buka)>
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" class="form-control form-control-sm"
                                           name="operating_hours[{{ $kunci }}][open]"
                                           value="{{ $hari['open'] ?? '08:00' }}" @disabled(! $buka)
                                           data-jam="{{ $kunci }}">
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" class="form-control form-control-sm"
                                           name="operating_hours[{{ $kunci }}][close]"
                                           value="{{ $hari['close'] ?? '17:00' }}" @disabled(! $buka)
                                           data-jam="{{ $kunci }}">
                                </div>
                            </div>
                            @error("operating_hours.$kunci.close")
                                <div class="text-danger fs-2 mb-2">{{ $message }}</div>
                            @enderror
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">

                {{-- Rekening tujuan transfer manual. --}}
                <div class="card">
                    <div class="card-header fw-semibold">Rekening Bank</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="bank_account" class="form-label">Bank & nomor</label>
                            <input type="text" id="bank_account" name="bank_account"
                                   value="{{ old('bank_account', $store->bank_account) }}"
                                   @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('bank_account')])
                                   maxlength="100" placeholder="BSI 7112345678">
                            @error('bank_account') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-0">
                            <label for="bank_account_name" class="form-label">Atas nama</label>
                            <input type="text" id="bank_account_name" name="bank_account_name"
                                   value="{{ old('bank_account_name', $store->bank_account_name) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('bank_account_name')])
                                   maxlength="100" placeholder="Nama pemilik rekening">
                            @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">
                                Pembeli transfer manual mencocokkan atas nama ini — kosong berarti
                                pesanan transfer tidak tahu harus dikirim ke siapa.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ringkasan kedudukan: dibaca, bukan diubah dari sini. --}}
                <div class="card">
                    <div class="card-header fw-semibold">Kedudukan</div>
                    <div class="card-body fs-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Status</span>
                            @include('admin.stores._status', ['store' => $store])
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Dibuat</span>
                            <span>{{ $store->created_at->format('d M Y') }}</span>
                        </div>
                        @if ($store->verified_at)
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Disetujui</span>
                                <span>{{ $store->verified_at->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Aturan 5: editor ini tidak menyentuh stempel/kedudukan. --}}
                <div class="card bg-light-warning shadow-none border-0">
                    <div class="card-body fs-3 mb-0">
                        Menyimpan di sini <strong>tidak mengubah kedudukan maupun stempel</strong>
                        toko — persetujuan, penolakan, dan blokir hanya berubah lewat kanal
                        verifikasi & blokir pemiliknya (lihat halaman Verifikasi Toko).
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-1 mb-4">
            <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-link text-muted">
                <i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali ke detail
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="ti ti-device-floppy me-1" aria-hidden="true"></i> Simpan Perubahan
            </button>
        </div>
    </form>

    @include('admin.partials._lightbox')
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Pratinjau foto instan — admin melihat gambar SEBELUM tersimpan.
        const inputFoto = document.querySelector('[data-foto-input]');
        if (inputFoto) {
            inputFoto.addEventListener('change', function () {
                const pratinjau = document.querySelector(inputFoto.dataset.pratinjau);
                const berkas    = inputFoto.files && inputFoto.files[0];
                if (!pratinjau || !berkas) return;
                const pembaca = new FileReader();
                pembaca.onload = function (e) { pratinjau.src = e.target.result; };
                pembaca.readAsDataURL(berkas);
            });
        }

        // Checkbox hari menyalakan/mematikan pasangan input jamnya.
        document.querySelectorAll('[data-hari]').forEach(function (saklar) {
            saklar.addEventListener('change', function () {
                document.querySelectorAll('[data-jam="' + saklar.dataset.hari + '"]')
                    .forEach(function (input) { input.disabled = !saklar.checked; });
            });
        });

        // Peta pemilih titik: klik/geser penanda <-> kolom, sinkron dua arah.
        const wadah = document.getElementById('petaSuntingToko');
        if (wadah && typeof L !== 'undefined') {
            const kolom = {
                lat: document.getElementById('latitude'),
                lng: document.getElementById('longitude'),
            };

            function bacaKolom(input) {
                const nilai = parseFloat((input.value || '').replace(',', '.'));
                return isFinite(nilai) ? nilai : null;
            }

            function tulisKolom(lat, lng) {
                kolom.lat.value = lat.toFixed(8);
                kolom.lng.value = lng.toFixed(8);
            }

            const awalLat = bacaKolom(kolom.lat) ?? @js(-7.5966);   // pusat: alun-alun Bangil
            const awalLng = bacaKolom(kolom.lng) ?? @js(112.8203);

            const peta = L.map(wadah, { center: [awalLat, awalLng], zoom: 13 });
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(peta);

            const penanda = L.marker([awalLat, awalLng], { draggable: true }).addTo(peta);

            function tetapkan(lat, lng, geser) {
                tulisKolom(lat, lng);
                penanda.setLatLng([lat, lng]);
                if (geser) peta.panTo([lat, lng]);
            }

            peta.on('click', function (e) { tetapkan(e.latlng.lat, e.latlng.lng, false); });
            penanda.on('dragend', function () {
                const t = penanda.getLatLng();
                tetapkan(t.lat, t.lng, false);
            });

            // Mengetik koordinat manual tetap dihormati — peta mengikuti.
            [kolom.lat, kolom.lng].forEach(function (input) {
                input.addEventListener('change', function () {
                    const lat = bacaKolom(kolom.lat);
                    const lng = bacaKolom(kolom.lng);
                    if (lat !== null && lng !== null) tetapkan(lat, lng, true);
                });
            });
        }
    });
</script>
@endpush
