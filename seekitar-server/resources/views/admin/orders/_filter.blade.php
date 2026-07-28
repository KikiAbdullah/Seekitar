{{-- Filter dibaca partial table-page lewat atribut data-dt-filter. --}}
<label for="filter-order-status" class="form-label">Status</label>

<select id="filter-order-status" name="status"
        class="form-select w-auto d-inline-block" data-dt-filter="orders-table">
    <option value="">Semua</option>
    @foreach (\App\Enums\OrderStatus::cases() as $status)
        {{-- label() dasar, bukan contextualLabel(): filter menyaring NILAI
             kolom status, sedangkan label kontekstual bergantung pada tipe
             pesanan tiap baris dan tidak bisa dipetakan balik ke satu nilai. --}}
        <option value="{{ $status->value }}"
            @selected(request('status') === $status->value)>{{ $status->label() }}</option>
    @endforeach
</select>
