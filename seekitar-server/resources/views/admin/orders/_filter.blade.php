<div class="row g-2 align-items-end">
    <div class="col-auto">
        <label for="filter-order-status" class="form-label">Status</label>
        <select id="filter-order-status" name="status"
                class="form-select w-auto js-select2" data-dt-filter="orders-table">
            <option value="">Semua</option>
            @foreach (\App\Enums\OrderStatus::cases() as $status)
                <option value="{{ $status->value }}"
                    @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-auto">
        <label for="filter-order-type" class="form-label">Tipe</label>
        <select id="filter-order-type" name="order_type"
                class="form-select w-auto js-select2" data-dt-filter="orders-table">
            <option value="">Semua</option>
            @foreach (\App\Enums\OrderType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-auto">
        <label for="filter-order-delivery" class="form-label">Pemenuhan</label>
        <select id="filter-order-delivery" name="delivery_method"
                class="form-select w-auto js-select2" data-dt-filter="orders-table">
            <option value="">Semua</option>
            @foreach (\App\Enums\DeliveryMethod::cases() as $method)
                <option value="{{ $method->value }}">{{ $method->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
