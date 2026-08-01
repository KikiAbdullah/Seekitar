<div class="d-flex flex-wrap align-items-center gap-3">
    <div>
        <label for="listings-filter-type" class="visually-hidden">Saring tipe</label>
        <select class="form-select js-select2" id="listings-filter-type" name="listing_type"
                data-dt-filter="{{ $tableId }}">
            <option value="">Semua Tipe</option>
            @foreach (\App\Enums\ListingType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="listings-filter-status" class="visually-hidden">Saring status</label>
        <select class="form-select js-select2" id="listings-filter-status" name="status"
                data-dt-filter="{{ $tableId }}">
            <option value="">Semua Status</option>
            @foreach (\App\Enums\ListingStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
