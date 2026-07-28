{{-- Filter dibaca partial table-page lewat atribut data-dt-filter. --}}
<div class="row g-2 align-items-end">
    <div class="col-auto">
        <label for="filter-verification-level" class="form-label">Level verifikasi</label>
        <select id="filter-verification-level" name="verification_level"
                class="form-select w-auto js-select2" data-dt-filter="users-table">
            <option value="">Semua</option>
            @foreach (\App\Enums\VerificationLevel::cases() as $level)
                <option value="{{ $level->value }}">{{ $level->value }} — {{ $level->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-auto">
        <label for="filter-is-blocked" class="form-label">Status</label>
        {{-- Nilai "0" sengaja BUKAN string kosong: request()->filled() menganggap
             "0" terisi, sehingga filter "Aktif" benar-benar terkirim. --}}
        <select id="filter-is-blocked" data-min-search="20" name="is_blocked"
                class="form-select w-auto js-select2" data-dt-filter="users-table">
            <option value="">Semua</option>
            <option value="0">Aktif</option>
            <option value="1">Diblokir</option>
        </select>
    </div>
</div>
