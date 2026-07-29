<label for="filter-status" class="form-label">Kedudukan</label>

{{-- Nilai opsi = nilai enum mentahnya, jadi pilihan di UI tidak bisa
     menyimpang dari StoreStatus. --}}
<select id="filter-status" name="status"
        class="form-select w-auto d-inline-block js-select2"
        data-dt-filter="stores-table">
    <option value="">Semua</option>
    @foreach (\App\Enums\StoreStatus::cases() as $status)
        <option value="{{ $status->value }}">{{ $status->label() }}</option>
    @endforeach
</select>
