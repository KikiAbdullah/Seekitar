@extends('admin.layout')
@section('title', 'Sunting Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Sunting Pengguna</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.users.index') }}">Pengguna</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.users.show', $user) }}">Detail</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Sunting</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- multipart wajib: formulir ini juga menerima unggahan avatar & KTP. --}}
    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">

            <div class="col-lg-8">

                {{-- Identitas yang sedang disunting: pengaman anti-salah-orang,
                     sumber salah sasaran paling sering di panel admin. --}}
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <a href="{{ $user->avatar_url }}" data-lightbox data-title="Foto {{ $user->name ?? 'pengguna' }}">
                            <img src="{{ $user->avatar_url }}" alt="Foto profil {{ $user->name ?? 'pengguna' }}"
                                 class="rounded-circle flex-shrink-0 border" width="56" height="56"
                                 style="object-fit: cover; cursor: pointer;"
                                 data-avatar-pratinjau>
                        </a>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-semibold text-truncate d-inline-flex align-items-center">
                                {{ $user->name ?? '(belum mengisi nama)' }}
                                @include('admin.partials._cek_terverifikasi', ['user' => $user])
                            </div>
                            <div class="text-muted fs-3">
                                <i class="fa-regular fa-comment-dots me-1" aria-hidden="true"></i>{{ $user->phone }}
                            </div>
                        </div>
                        @include('admin.users._status', ['user' => $user])
                    </div>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold">Data Akun</div>
                    <div class="card-body">

                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" maxlength="100" required autofocus
                                   value="{{ old('name', $user->name) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('name')])
                                   placeholder="Nama sesuai KTP, mis. Sinta Wijaya">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Sebaiknya nama asli — nama inilah yang ditampilkan ke lawan transaksi.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label fw-semibold">Nomor WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light" aria-hidden="true">
                                    <i class="fa-regular fa-circle-stop" aria-hidden="true"></i>
                                </span>
                                <input type="text" id="phone" class="form-control" value="{{ $user->phone }}" readonly aria-describedby="phoneHelp">
                            </div>
                            <div class="form-text" id="phoneHelp">
                                Nomor ini adalah kredensial masuk (OTP) — identitas akun itu sendiri.
                                Penggantian hanya bisa dilakukan pemiliknya lewat aplikasi
                                (diverifikasi ulang via OTP ke nomor baru).
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input type="email" id="email" name="email" maxlength="255"
                                   value="{{ old('email', $user->email) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('email')])
                                   placeholder="Kosongkan bila tidak ada">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="avatar" class="form-label fw-semibold">Foto Profil</label>
                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png"
                                   @class(['form-control', 'is-invalid' => $errors->has('avatar')])
                                   aria-describedby="avatarHelp"
                                   data-avatar-input>
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="avatarHelp">
                                JPEG/PNG maksimal 2 MB. Pilihan baru langsung tampil sebagai pratinjau
                                pada strip identitas di atas; kosongkan bila tidak ingin mengganti.
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Domisili: teks + titik di peta, satu kartu — penanda klik/geser
                     lebih aman daripada mengetik angka buta. Nilai titik mentah untuk JS. --}}
                <div class="card">
                    <div class="card-header fw-semibold">Domisili</div>
                    <div class="card-body">

                        <div class="mb-4">
                            <label for="address" class="form-label fw-semibold">Alamat</label>
                            <textarea id="address" name="address" rows="2" maxlength="255"
                                      @class(['form-control', 'is-invalid' => $errors->has('address')])
                                      placeholder="Alamat domisili, mis. Jl. Raya Bangil No. 12">{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-2">
                            <span class="form-label fw-semibold d-block">Titik di Peta</span>

                            <div class="rounded border" style="height: 280px; width: 100%;"
                                 id="petaDomisili"
                                 role="application" aria-label="Peta pemilih titik domisili"></div>

                            <div class="row g-2 mt-2 align-items-end">
                                <div class="col-sm">
                                    <label for="latitude" class="form-label text-muted" style="font-size: 11px;">LINTANG</label>
                                    <input type="text" inputmode="decimal" id="latitude" name="latitude"
                                           value="{{ old('latitude', $user->latitude !== null ? (string) (float) $user->latitude : '') }}"
                                           @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('latitude') || $errors->has('longitude')])
                                           placeholder="-7,5966"
                                           data-kolom-titik>
                                    @error('latitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm">
                                    <label for="longitude" class="form-label text-muted" style="font-size: 11px;">BUJUR</label>
                                    <input type="text" inputmode="decimal" id="longitude" name="longitude"
                                           value="{{ old('longitude', $user->longitude !== null ? (string) (float) $user->longitude : '') }}"
                                           @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('latitude') || $errors->has('longitude')])
                                           placeholder="112,8203"
                                           data-kolom-titik>
                                    @error('longitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-auto">
                                    <button type="button" class="btn btn-outline-danger" data-hapus-titik
                                            title="Kosongkan titik domisili">
                                        <i class="fa-regular fa-map" aria-hidden="true"></i>
                                        Hapus Titik
                                    </button>
                                </div>
                            </div>
                            <div class="form-text">
                                Klik peta atau geser penanda untuk memindahkan titik; mengetik angka
                                di kolom juga menggerakkan penandanya. Seekitar hanya beroperasi di
                                {{ config('seekitar.regency') }} — pastikan titiknya di dalam wilayah.
                            </div>
                        </div>

                    </div>
                </div>

                {{-- NIK & berkas KTP adalah data pribadi (UU PDP): hanya untuk
                     pemegang izin verifikasi; gerbang yang sama di controller. --}}
                <div class="card">
                    <div class="card-header fw-semibold">Identitas (KTP)</div>
                    <div class="card-body">

                        <div class="mb-4">
                            <label for="nik" class="form-label fw-semibold">NIK</label>
                            <input type="text" id="nik" name="nik" maxlength="16" inputmode="numeric"
                                   value="{{ old('nik', $user->nik) }}"
                                   @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('nik')])
                                   placeholder="16 digit sesuai KTP">
                            @error('nik')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Tersimpan terenkripsi; keunikannya diperiksa lewat hash.
                                Kosongkan untuk menghapus NIK dari akun ini.
                            </div>
                        </div>

                        @can('verify-users')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="ktp_image" class="form-label fw-semibold">Foto KTP</label>
                                    @if ($user->ktp_image)
                                        <a href="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                                           data-lightbox="edit-ktp-{{ $user->id }}"
                                           data-title="KTP {{ $user->name }}" class="d-block mb-2">
                                            <img src="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                                                 alt="KTP {{ $user->name }}" class="img-fluid rounded border"
                                                 style="max-height: 120px; object-fit: cover; cursor: pointer;">
                                        </a>
                                    @else
                                        <div class="border rounded text-muted text-center py-3 small mb-2">Belum diunggah</div>
                                    @endif
                                    <input type="file" id="ktp_image" name="ktp_image" accept="image/jpeg,image/png"
                                           @class(['form-control', 'is-invalid' => $errors->has('ktp_image')])>
                                    @error('ktp_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="selfie_image" class="form-label fw-semibold">Foto Wajah</label>
                                    @if ($user->selfie_image)
                                        <a href="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                                           data-lightbox="edit-selfie-{{ $user->id }}"
                                           data-title="Foto Wajah {{ $user->name }}" class="d-block mb-2">
                                            <img src="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                                                 alt="Foto wajah {{ $user->name }}" class="img-fluid rounded border"
                                                 style="max-height: 120px; object-fit: cover; cursor: pointer;">
                                        </a>
                                    @else
                                        <div class="border rounded text-muted text-center py-3 small mb-2">Belum diunggah</div>
                                    @endif
                                    <input type="file" id="selfie_image" name="selfie_image" accept="image/jpeg,image/png"
                                           @class(['form-control', 'is-invalid' => $errors->has('selfie_image')])>
                                    @error('selfie_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-text mt-2">
                                JPEG/PNG maksimal 5 MB per berkas. Penggantian oleh admin
                                <strong>tidak mengubah status verifikasi</strong> — berbeda dengan
                                penggantian oleh pengguna sendiri lewat aplikasi, yang selalu
                                membuka peninjauan ulang.
                            </div>
                        @else
                            @if ($user->ktp_image || $user->selfie_image)
                                <div class="fs-2 text-muted">
                                    Foto KTP/wajah tersembunyi — memerlukan izin verifikasi pengguna.
                                </div>
                            @endif
                        @endcan

                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 mb-4">
                    <button type="submit" class="btn btn-seekitar">
                        <i class="fa-regular fa-floppy-disk me-1" aria-hidden="true"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-dark">Batal</a>
                </div>

            </div>

            {{-- Konteks samping: statistik & jejak — bahan pertimbangan
                 sebelum menekan Simpan, tanpa berpindah layar. --}}
            <div class="col-lg-4">

                <div class="card">
                    <div class="card-header fw-semibold">Ringkasan</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span class="text-muted">Pesanan</span>
                            <span class="fw-semibold">{{ $user->orders_count }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span class="text-muted">Kebutuhan terpasang</span>
                            <span class="fw-semibold">{{ $user->customer_requests_count }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span class="text-muted">Toko</span>
                            <span class="fw-semibold">{{ $user->stores_count }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span class="text-muted">Bergabung</span>
                            <span class="fw-semibold">{{ $user->created_at?->translatedFormat('d M Y') }}</span>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold">Kedudukan &amp; Jejak</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <span class="text-muted">Kedudukan</span>
                            @include('admin.users._status', ['user' => $user])
                        </li>
                        <li class="list-group-item">
                            <div class="text-muted fs-3 mb-1">IDENTITAS (KEPUTUSAN ADMIN)</div>
                            @if ($user->verified_at)
                                <span class="fs-3">
                                    <i class="fa-regular fa-circle-check text-success" aria-hidden="true"></i>
                                    Disetujui {{ $user->verified_at->translatedFormat('d M Y H:i') }}
                                    <span class="text-muted">oleh {{ $user->verifiedBy?->name ?? '—' }}</span>
                                </span>
                            @elseif ($user->rejected_at)
                                <span class="fs-3">
                                    <i class="fa-regular fa-circle-xmark text-danger" aria-hidden="true"></i>
                                    Ditolak {{ $user->rejected_at->translatedFormat('d M Y H:i') }}
                                    <span class="text-muted">oleh {{ $user->rejectedBy?->name ?? '—' }}</span>
                                </span>
                                <div class="text-danger fs-2 mt-1">{{ $user->rejected_reason }}</div>
                            @elseif ($user->ktp_submitted_at)
                                <span class="fs-3 text-muted">
                                    <i class="fa-regular fa-clock text-warning" aria-hidden="true"></i>
                                    Menunggu tinjauan — berkas masuk {{ $user->ktp_submitted_at->translatedFormat('d M Y H:i') }}
                                </span>
                            @else
                                <span class="text-muted fs-3">Belum mengajukan berkas.</span>
                            @endif
                        </li>
                        @if ($user->isBlocked())
                            <li class="list-group-item">
                                <div class="text-muted fs-3 mb-1">BLOKIR</div>
                                <span class="fs-3">
                                    <i class="fa-regular fa-circle-stop text-dark" aria-hidden="true"></i>
                                    {{ $user->blocked_at?->translatedFormat('d M Y H:i') }}
                                    <span class="text-muted">oleh {{ $user->blockedBy?->name ?? '—' }}</span>
                                </span>
                                <div class="fs-2 mt-1">{{ $user->blocked_reason }}</div>
                            </li>
                        @endif
                    </ul>
                    <div class="card-body border-top fs-3 text-muted">
                        Kedudukan &amp; stempel hanya berubah lewat antrian Verifikasi atau aksi
                        blokir — suntingan lewat formulir ini tidak pernah menyentuhnya (aturan 5).
                    </div>
                </div>

            </div>
        </div>
    </form>

    @include('admin.partials._lightbox')
@endsection

@push('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        /*
         * Pemilih titik domisili.
         *
         * Satu arah perubahan saja: PENANDA adalah kebenarannya. Klik peta &
         * geser penanda menulis kolom; mengetik kolom memindahkan penanda —
         * keduanya bertemu di satu fungsi supaya tidak bisa selisih.
         *
         * Pusat awal: titik yang tersimpan; kalau belum ada, alun-alun
         * Bangil (acuan wilayah, sama seperti data contoh seeder).
         */
        document.addEventListener('DOMContentLoaded', function () {
            const wadah  = document.getElementById('petaDomisili');
            if (!wadah || typeof L === 'undefined') return;

            const kolomLat = document.getElementById('latitude');
            const kolomLng = document.getElementById('longitude');
            const awalLat  = parseFloat(kolomLat.value);
            const awalLng  = parseFloat(kolomLng.value);
            const adaTitik = ! Number.isNaN(awalLat) && ! Number.isNaN(awalLng);

            const PUSAT = @js([-7.5966, 112.8203]);   // alun-alun Bangil — wilayah operasi

            const peta = L.map(wadah, {
                center: adaTitik ? [awalLat, awalLng] : PUSAT,
                zoom: adaTitik ? 15 : 12,
            });
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(peta);

            let penanda = null;

            function tetapkan(lat, lng) {
                kolomLat.value = lat.toFixed(6);
                kolomLng.value = lng.toFixed(6);
                if (penanda) {
                    penanda.setLatLng([lat, lng]);
                } else {
                    penanda = L.marker([lat, lng], { draggable: true }).addTo(peta);
                    penanda.on('dragend', function () {
                        const p = penanda.getLatLng();
                        tetapkan(p.lat, p.lng);
                    });
                }
            }

            if (adaTitik) tetapkan(awalLat, awalLng);

            peta.on('click', function (e) {
                tetapkan(e.latlng.lat, e.latlng.lng);
            });

            // Mengetik manual: dua kolom harus terisi VALID dulu baru
            // penandanya pindah — titik setengah jadi tidak boleh dibuat.
            function bacaKolom() {
                const lat = parseFloat(kolomLat.value.replace(',', '.'));
                const lng = parseFloat(kolomLng.value.replace(',', '.'));
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                if (Math.abs(lat) > 90 || Math.abs(lng) > 180) return;
                tetapkan(lat, lng);
            }
            kolomLat.addEventListener('change', bacaKolom);
            kolomLng.addEventListener('change', bacaKolom);

            document.querySelector('[data-hapus-titik]').addEventListener('click', function () {
                kolomLat.value = '';
                kolomLng.value = '';
                if (penanda) {
                    peta.removeLayer(penanda);
                    penanda = null;
                }
            });
        });

        /*
         * Pratinjau avatar: file belum terunggah saat formulir disimpan,
         * tetapi admin layak melihat hasilnya lebih dulu — FileReader
         * membacanya lokal, tidak berangkat ke mana-mana.
         */
        document.addEventListener('DOMContentLoaded', function () {
            const masukan = document.querySelector('[data-avatar-input]');
            const pratinjau = document.querySelector('[data-avatar-pratinjau]');
            if (!masukan || !pratinjau) return;

            masukan.addEventListener('change', function () {
                const berkas = masukan.files && masukan.files[0];
                if (!berkas || !berkas.type.startsWith('image/')) return;

                const pembaca = new FileReader();
                pembaca.onload = function (e) { pratinjau.src = e.target.result; };
                pembaca.readAsDataURL(berkas);
            });
        });
    </script>
@endpush
