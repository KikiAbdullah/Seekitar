<div class="row g-2 align-items-end">
    <div class="col-auto">
        <label for="filter-status" class="form-label">Kedudukan</label>
        {{-- Nilai opsi = nilai enum mentah: DataTables meneruskannya utuh
             dan query memfilternya langsung — tanpa tabel terjemahan. --}}
        <select id="filter-status" data-min-search="20" name="status"
                class="form-select w-auto js-select2" data-dt-filter="users-table">
            <option value="">Semua</option>
            @foreach (\App\Enums\UserStatus::cases() as $kedudukan)
                <option value="{{ $kedudukan->value }}">{{ $kedudukan->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
