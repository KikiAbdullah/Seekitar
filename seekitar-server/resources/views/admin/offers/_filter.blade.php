<div class="d-flex flex-wrap align-items-center gap-3">
    <div>
        <label for="offers-filter-status" class="visually-hidden">Saring status</label>
        <select class="form-select js-select2" id="offers-filter-status" name="status"
                data-dt-filter="{{ $tableId }}">
            <option value="">Semua Status</option>
            @foreach (\App\Enums\OfferStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
