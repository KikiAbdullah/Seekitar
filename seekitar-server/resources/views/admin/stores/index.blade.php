@extends('admin.layout')
@section('title', 'Toko')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Toko',
        'tableId'  => 'stores-table',
        'ajax'     => route('admin.stores.data'),
        'order'    => [[5, 'desc']],
        'filterView' => 'admin.stores._filter',
        'columns'  => [
            ['data' => 'name',                'label' => 'Nama'],
            ['data' => 'owner',               'label' => 'Pemilik', 'orderable' => false],
            ['data' => 'regency',             'label' => 'Kabupaten'],
            ['data' => 'verification_status', 'label' => 'Verifikasi'],
            ['data' => 'rating_avg',          'label' => 'Rating'],
            ['data' => 'created_at',          'label' => 'Dibuat'],
        ],
    ])

    @can('verify-stores')
        <div class="modal fade" id="modalTolakToko" tabindex="-1"
             aria-labelledby="modalTolakTokoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" id="formTolakToko">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTolakTokoLabel">Tolak toko</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-2">Menolak: <strong id="modalTolakTokoNama"></strong></p>

                        <label for="modalTolakTokoAlasan" class="form-label">Alasan penolakan</label>
                        <textarea id="modalTolakTokoAlasan" name="reason" class="form-control"
                                  rows="3" required minlength="10" maxlength="500"
                                  placeholder="Contoh: Alamat toko tidak sesuai dengan wilayah layanan."></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@can('verify-stores')
@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('modalTolakToko');
    const form    = document.getElementById('formTolakToko');
    if (!modalEl || !form) return;

    const modal = new bootstrap.Modal(modalEl);

    /*
     * Delegasi event pada document, BUKAN pemasangan langsung ke tombol.
     * Tombol .js-tolak-toko baru dibuat setiap kali baris dipilih, jadi
     * listener yang dipasang saat halaman dimuat tidak akan pernah mengenainya.
     */
    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('.js-tolak-toko');
        if (!tombol) return;

        form.action = tombol.dataset.action;
        document.getElementById('modalTolakTokoNama').textContent = tombol.dataset.nama;
        document.getElementById('modalTolakTokoAlasan').value = '';
        modal.show();
    });

    // Fokus ke textarea begitu modal terbuka — pengguna keyboard tidak perlu
    // menekan Tab dari awal dialog.
    modalEl.addEventListener('shown.bs.modal', function () {
        document.getElementById('modalTolakTokoAlasan').focus();
    });
})();
</script>
@endpush
@endcan
