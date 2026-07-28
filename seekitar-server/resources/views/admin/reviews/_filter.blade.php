{{-- Filter dibaca partial table-page lewat atribut data-dt-filter. --}}
<label for="filter-rating" class="form-label">Rating</label>

<select id="filter-rating" name="rating"
        class="form-select w-auto d-inline-block js-select2" data-dt-filter="reviews-table">
    <option value="">Semua</option>
    @for ($i = 1; $i <= 5; $i++)
        <option value="{{ $i }}">{{ $i }} bintang</option>
    @endfor
</select>
