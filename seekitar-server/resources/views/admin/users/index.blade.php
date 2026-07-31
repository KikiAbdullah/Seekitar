@extends('admin.layout')
@section('title', 'Pengguna')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Pengguna',
        'tableId'  => 'users-table',
        'ajax'     => route('admin.users.data'),
        'order'    => [[4, 'desc']],
        'exportRoute' => 'admin.users.export',
        'filterView' => 'admin.users._filter',
        'columns'  => [
            ['data' => 'name',       'label' => 'Nama'],
            ['data' => 'phone',      'label' => 'Telepon'],
            ['data' => 'rating',     'label' => 'Rating', 'orderable' => false, 'searchable' => false],
            ['data' => 'status',     'label' => 'Status', 'orderable' => false, 'searchable' => false],
            // created_at TETAP kolom ke-5: urutan default ('order' di atas)
            // menunjuk indeks kolom, bukan nama.
            ['data' => 'created_at', 'label' => 'Terdaftar'],
            ['data' => 'email',      'label' => 'Email'],
            ['data' => 'address',    'label' => 'Alamat'],
        ],
    ])

    @can('manage-users')
        <div class="modal fade" id="modalBlokirUser" tabindex="-1"
             aria-labelledby="modalBlokirUserLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" id="formBlokirUser">
                    @csrf
                    <input type="hidden" name="action" value="block">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalBlokirUserLabel">Blokir pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-2">Blokir: <strong id="modalBlokirUserNama"></strong></p>

                        <label for="modalBlokirUserAlasan" class="form-label">Alasan blokir</label>
                        <textarea id="modalBlokirUserAlasan" name="reason" class="form-control"
                                  rows="3" required minlength="10" maxlength="500"
                                  placeholder="Contoh: Melanggar ketentuan dengan mengirim spam."></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Blokir</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@can('manage-users')
@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('modalBlokirUser');
    const form    = document.getElementById('formBlokirUser');
    if (!modalEl || !form) return;

    const modal = new bootstrap.Modal(modalEl);

    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('.js-blokir-user');
        if (!tombol) return;

        form.action = tombol.dataset.action;
        document.getElementById('modalBlokirUserNama').textContent = tombol.dataset.nama;
        document.getElementById('modalBlokirUserAlasan').value = '';
        modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        document.getElementById('modalBlokirUserAlasan').focus();
    });
})();
</script>
@endpush
@endcan
