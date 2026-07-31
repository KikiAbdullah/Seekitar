@extends('admin.layouts.admin')

@section('title', 'Pengaturan Sistem — Seekitar')

@section('content')
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">Pengaturan Sistem</h4>
          <p class="card-subtitle mb-4">Konfigurasi parameter runtime aplikasi seperti radius pencarian, masa aktif iklan, durasi kedaluwarsa, dan batas SLA.</p>
          
          <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="accordion" id="settingsAccordion">
              @foreach ($groups as $groupName => $settings)
                <div class="accordion-item mb-3 border rounded-3 overflow-hidden">
                  <h2 class="accordion-header" id="heading-{{ $groupName }}">
                    <button class="accordion-button fs-4 fw-semibold text-capitalize" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $groupName }}" aria-expanded="true" aria-controls="collapse-{{ $groupName }}">
                      <i class="fa-solid fa-sliders me-2"></i> Grup: {{ $groupName }}
                    </button>
                  </h2>
                  <div id="collapse-{{ $groupName }}" class="accordion-collapse collapse show" aria-labelledby="heading-{{ $groupName }}" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body bg-white p-4">
                      @foreach ($settings as $setting)
                        <div class="mb-4">
                          <label for="setting-{{ $setting->key }}" class="form-label fw-semibold text-dark mb-1">
                            {{ $setting->label ?? $setting->key }}
                          </label>
                          <div class="form-text text-muted mb-2">Key: <code>{{ $setting->key }}</code></div>
                          
                          @if ($setting->type === 'integer')
                            <input type="number" class="form-control" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}" required>
                          @elseif ($setting->type === 'boolean')
                            <select class="form-select" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" required>
                              <option value="1" {{ old('settings.' . $setting->key, $setting->value) == '1' ? 'selected' : '' }}>Aktif (True)</option>
                              <option value="0" {{ old('settings.' . $setting->key, $setting->value) == '0' ? 'selected' : '' }}>Nonaktif (False)</option>
                            </select>
                          @elseif ($setting->type === 'json')
                            <textarea class="form-control" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" rows="5" required>{{ old('settings.' . $setting->key, $setting->value) }}</textarea>
                            <div class="form-text">Harus berupa format JSON yang valid.</div>
                          @else
                            <input type="text" class="form-control" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}">
                          @endif
                        </div>
                        @if (!$loop->last)
                          <hr class="my-4 text-muted opacity-25">
                        @endif
                      @endforeach
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
            
            <div class="mt-4">
              <button type="submit" class="btn btn-primary px-5 btn-hover-shadow">Simpan Semua Pengaturan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
