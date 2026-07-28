{{-- Filter dibaca partial table-page lewat atribut data-dt-filter. --}}
<label for="filter-dispute-status" class="form-label">Status</label>

<select id="filter-dispute-status" name="status"
        class="form-select w-auto d-inline-block" data-dt-filter="disputes-table">
    <option value="">Semua</option>
    @foreach (\App\Enums\DisputeStatus::cases() as $status)
        <option value="{{ $status->value }}">{{ $status->label() }}</option>
    @endforeach
</select>
