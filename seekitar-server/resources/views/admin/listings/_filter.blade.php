{{-- Filter dibaca partial table-page lewat atribut data-dt-filter. --}}
<div class="row g-2 align-items-end">
    <div class="col-auto">
        <label for="filter-listing-status" class="form-label">Status</label>
        <select id="filter-listing-status" name="status"
                class="form-select w-auto js-select2" data-dt-filter="listings-table">
            <option value="">Semua</option>
            @foreach (\App\Enums\ListingStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-auto">
        <label for="filter-listing-type" class="form-label">Tipe</label>
        <select id="filter-listing-type" name="listing_type"
                class="form-select w-auto js-select2" data-dt-filter="listings-table">
            <option value="">Semua</option>
            @foreach (\App\Enums\ListingType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
