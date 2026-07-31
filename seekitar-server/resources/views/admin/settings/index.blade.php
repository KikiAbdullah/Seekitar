@extends('admin.layout')
@section('title', 'Pengaturan Sistem')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Pengaturan Sistem</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item active" aria-current="page">Pengaturan</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-light-info shadow-none border-0 mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3">
                <i class="fa-regular fa-circle-question fs-6 text-info mt-1" aria-hidden="true"></i>
                <p class="mb-0 fs-3">Perubahan berlaku untuk data <strong>baru</strong> saja. Permintaan dan penawaran yang sudah berjalan tetap memakai nilai lamanya.</p>
            </div>
        </div>
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
