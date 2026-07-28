{{--
    Modal alasan penolakan — dipakai antrian KTP maupun antrian toko.

    $modalId  id unik elemen modal
    $judul    judul di header
    $action   URL tujuan form
    $catatan  keterangan singkat di bawah textarea
--}}
<div class="modal fade text-start" id="{{ $modalId }}" tabindex="-1"
     aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ $action }}">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">{{ $judul }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <label for="{{ $modalId }}Reason" class="form-label">Alasan penolakan</label>

                <textarea id="{{ $modalId }}Reason" name="reason" class="form-control"
                          rows="3" required minlength="10" maxlength="500"
                          placeholder="Contoh: Foto KTP buram, nomor NIK tidak terbaca."></textarea>

                <div class="form-text">{{ $catatan }}</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak</button>
            </div>
        </form>
    </div>
</div>
