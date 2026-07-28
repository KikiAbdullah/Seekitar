@extends('admin.layout')
@section('title', 'Pengaturan Sistem')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pengaturan</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Pengaturan Sistem</h1>

    {{-- Perubahan hanya berlaku untuk data BARU: menurunkan
         request_expiry_hours tidak memperpendek permintaan yang sedang
         berjalan, karena expires_at sudah dihitung saat baris dibuat. --}}
    <div class="alert alert-info py-2">
        Perubahan berlaku untuk data <strong>baru</strong> saja. Permintaan
        dan penawaran yang sudah berjalan tetap memakai nilai lamanya.
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $settings)
            <div class="card mb-3">
                <div class="card-header text-capitalize">{{ $group }}</div>
                <div class="card-body">
                    @foreach ($settings as $setting)
                        <div class="row mb-3 align-items-center">
                            <label for="setting-{{ $setting->key }}" class="col-sm-5 col-form-label">
                                {{ $setting->label }}
                                <br><code class="small text-muted">{{ $setting->key }}</code>
                            </label>
                            <div class="col-sm-7">
                                @if ($setting->type === 'boolean')
                                    <select id="setting-{{ $setting->key }}" data-min-search="20"
                                            name="settings[{{ $setting->key }}]" class="form-select js-select2">
                                        <option value="1" @selected($setting->typedValue())>Aktif</option>
                                        <option value="0" @selected(! $setting->typedValue())>Nonaktif</option>
                                    </select>
                                @else
                                    <input id="setting-{{ $setting->key }}"
                                           type="{{ $setting->type === 'integer' ? 'number' : 'text' }}"
                                           name="settings[{{ $setting->key }}]"
                                           class="form-control"
                                           value="{{ old('settings.'.$setting->key, $setting->value) }}">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button type="submit" class="btn btn-seekitar">Simpan Pengaturan</button>
    </form>
@endsection
