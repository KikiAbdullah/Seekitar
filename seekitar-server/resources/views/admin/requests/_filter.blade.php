<label for="filter-request-status" class="form-label">Status</label>

<select id="filter-request-status" name="status"
        class="form-select w-auto d-inline-block js-select2" data-dt-filter="requests-table">
    <option value="">Semua</option>
    @foreach (\App\Enums\RequestStatus::cases() as $status)
        <option value="{{ $status->value }}">{{ $status->label() }}</option>
    @endforeach
</select>
