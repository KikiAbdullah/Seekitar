@extends('admin.layout')
@section('title', 'Sunting Pengguna')

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

                {{-- Identitas yang sedang disunting: pengaman anti-salah-orang.
                     Mengedit akun tanpa tahu persis siapa pemiliknya adalah
                     sumber salah sasaran yang paling sering di panel admin. --}}
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <img src="{{ $user->avatar_url }}" alt="Foto profil {{ $user->name ?? 'pengguna' }}"
                             class="rounded-circle flex-shrink-0" width="56" height="56"
                             style="object-fit: cover;">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-semibold text-truncate d-inline-flex align-items-center">
                                {{ $user->name ?? '(belum mengisi nama)' }}
                                @include('admin.partials._cek_terverifikasi', ['user' => $user])
                            </div>
                            <div class="text-muted fs-3">
                                <i class="ti ti-device-mobile me-1" aria-hidden="true"></i>{{ $user->phone }}
                            </div>
                        </div>
                        @if ($user->verified2_at)
                            <span class="badge bg-light-primary text-primary flex-shrink-0">KTP terverifikasi</span>
                        @endif
                    </div>
                </div>

                {{-- Formulir --}}
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
                                    <i class="ti ti-lock"></i>
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
                            <div class="form-text">
                                Opsional untuk pengguna biasa — hanya akun panel admin yang
                                masuk dengan email &amp; kata sandi.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label fw-semibold">Alamat</label>
                            <textarea id="address" name="address" rows="2" maxlength="255"
                                      @class(['form-control', 'is-invalid' => $errors->has('address')])
                                      placeholder="Alamat domisili, mis. Jl. Raya Darmo No. 12, Surabaya">{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-2">
                            <label for="avatar" class="form-label fw-semibold">Foto Profil</label>
                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png"
                                   @class(['form-control', 'is-invalid' => $errors->has('avatar')])
                                   aria-describedby="avatarHelp">
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="avatarHelp">
                                JPEG/PNG maksimal 2 MB. Kosongkan bila tidak ingin mengganti —
                                foto saat ini tampil pada strip identitas di atas.
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-4">
                            <button type="submit" class="btn btn-seekitar">
                                <i class="ti ti-device-floppy me-1" aria-hidden="true"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-dark">Batal</a>
                        </div>

                    </div>
                </div>

                {{-- Identitas resmi: NIK & berkas KTP. Berkasnya adalah data
                     pribadi (UU PDP) sehingga bagian ini — seperti halaman
                     detail & antrian verifikasi — hanya untuk pemegang izin
                     verifikasi. Gerbang yang sama ada di controller. --}}
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
                                           target="_blank" rel="noopener" class="d-block mb-2">
                                            <img src="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                                                 alt="KTP {{ $user->name }}" class="img-fluid rounded border"
                                                 style="max-height: 120px; object-fit: cover;">
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
                                           target="_blank" rel="noopener" class="d-block mb-2">
                                            <img src="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                                                 alt="Foto wajah {{ $user->name }}" class="img-fluid rounded border"
                                                 style="max-height: 120px; object-fit: cover;">
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

            </div>

            {{-- Konteks samping: statistik & jejak verifikasi — bahan pertimbangan
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
                    <div class="card-header fw-semibold">Jejak Verifikasi</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <div class="text-muted fs-3 mb-1">TAHAP 1 — NOMOR HP</div>
                            @if ($user->verified1_at)
                                <span class="fs-3">
                                    <i class="ti ti-circle-check text-success" aria-hidden="true"></i>
                                    {{ $user->verified1_at->translatedFormat('d M Y H:i') }}
                                    <span class="text-muted">oleh {{ $user->verified1_by_label }}</span>
                                </span>
                            @else
                                <span class="text-muted fs-3">Belum ada catatan.</span>
                            @endif
                        </li>
                        <li class="list-group-item">
                            <div class="text-muted fs-3 mb-1">TAHAP 2 — IDENTITAS (KTP)</div>
                            @if ($user->verified2_at)
                                <span class="fs-3">
                                    <i class="ti ti-circle-check text-success" aria-hidden="true"></i>
                                    {{ $user->verified2_at->translatedFormat('d M Y H:i') }}
                                    @if ($user->verified2By)
                                        <span class="text-muted">oleh {{ $user->verified2By->name }}</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-muted fs-3">Belum ada catatan.</span>
                            @endif
                        </li>
                    </ul>
                    <div class="card-body border-top fs-3 text-muted">
                        Stempel ditulis dari antrian Verifikasi — atau oleh <em>Sistem (OTP)</em>
                        untuk tahap 1 bila nomornya dibuktikan kode OTP. Suntingan lewat
                        formulir ini tidak pernah mengubah stempel tersebut.
                    </div>
                </div>

            </div>
        </div>
    </form>

@endsection
