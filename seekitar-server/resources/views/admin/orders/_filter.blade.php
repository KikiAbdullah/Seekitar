<label for="filter-order-status" class="form-label">Status</label>

<select id="filter-order-status" name="status"
        class="form-select w-auto d-inline-block js-select2" data-dt-filter="orders-table">
    <option value="">Semua</option>
    @foreach (\App\Enums\OrderStatus::cases() as $status)
        <option value="{{ $status->value }}"
            @selected(request('status') === $status->value)>{{ $status->label() }}</option>
    @endforeach
</select>
