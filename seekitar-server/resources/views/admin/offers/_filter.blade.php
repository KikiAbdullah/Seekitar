{{-- Filter dibaca partial table-page lewat atribut data-dt-filter. --}}
<label for="filter-offer-status" class="form-label">Status</label>

<select id="filter-offer-status" name="status"
        class="form-select w-auto d-inline-block js-select2" data-dt-filter="offers-table">
    <option value="">Semua</option>
    @foreach (\App\Enums\OfferStatus::cases() as $status)
        <option value="{{ $status->value }}">{{ $status->label() }}</option>
    @endforeach
</select>
