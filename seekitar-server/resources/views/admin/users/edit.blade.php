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

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
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
                            <div class="fw-semibold text-truncate">{{ $user->name ?? '(belum mengisi nama)' }}</div>
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
                                Nomor ini adalah kredensial masuk (OTP) — identitas akun itu sendiri,
                                sehingga tidak bisa diubah lewat formulir biasa.
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-seekitar">
                                <i class="ti ti-device-floppy me-1" aria-hidden="true"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-dark">Batal</a>
                        </div>

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
                                    @if ($user->verified1By)
                                        <span class="text-muted">oleh {{ $user->verified1By->name }}</span>
                                    @endif
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
                        Kedua stempel di atas ditulis dari antrian Verifikasi — setiap
                        persetujuan tercatat siapa &amp; kapannya.
                    </div>
                </div>

            </div>
        </div>
    </form>

@endsection
