<div class="d-flex flex-wrap align-items-center gap-3">
    <div>
        <label for="reviews-filter-rating" class="visually-hidden">Saring rating</label>
        <select class="form-select js-select2" id="reviews-filter-rating" name="rating"
                data-dt-filter="{{ $tableId }}">
            <option value="">Semua Rating</option>
            @for ($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}">{{ $i }} ★</option>
            @endfor
        </select>
    </div>
</div>
