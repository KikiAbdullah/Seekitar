@extends('admin.layout')
@section('title', 'Sunting Pengguna')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Pengguna</a></li>
    <li class="breadcrumb-item active" aria-current="page">Sunting</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Sunting Pengguna</h1>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Nama</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $user->name) }}" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Telepon</label>
                    {{-- Nomor adalah identitas akun; hanya bisa berubah lewat alur OTP. --}}
                    <input type="text" id="phone" class="form-control" value="{{ $user->phone }}" readonly>
                </div>

                <div class="mb-3">
                    <label for="verification_level" class="form-label">Level Verifikasi</label>
                    <select id="verification_level" name="verification_level" class="form-select js-select2">
                        @foreach (\App\Enums\VerificationLevel::cases() as $level)
                            <option value="{{ $level->value }}"
                                @selected(old('verification_level', $user->verification_level?->value) == $level->value)>
                                {{ $level->value }} — {{ $level->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-seekitar">Simpan</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-link">Batal</a>
            </form>
        </div>
    </div>
@endsection
