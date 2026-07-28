{{--
    Filter status verifikasi.

    `data-dt-filter` menunjuk id tabelnya; partial table-page membaca atribut
    itu dan menyertakan name/value elemen ke setiap request Datatables. Dengan
    begitu halaman tidak perlu menulis ulang blok `ajax`.
--}}
<label for="filter-verification-status" class="form-label">Status verifikasi</label>

<select id="filter-verification-status" name="verification_status"
        class="form-select w-auto d-inline-block js-select2"
        data-dt-filter="stores-table">
    <option value="">Semua</option>
    @foreach (\App\Enums\VerificationStatus::cases() as $status)
        <option value="{{ $status->value }}">{{ $status->label() }}</option>
    @endforeach
</select>
