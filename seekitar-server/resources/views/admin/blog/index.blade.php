@extends('admin.layout')
@section('title', 'Blog')

@section('content')
    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Blog</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Blog</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card w-100">
        <div class="card-body">
            <div class="d-sm-flex d-block align-items-center justify-content-between mb-4">
                <div>
                    @include('admin.blog._filter')
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.blog.create') }}" class="btn btn-seekitar">
                        <i class="fa-regular fa-plus-square me-1" aria-hidden="true"></i> Artikel Baru
                    </a>
                </div>
            </div>

            <table id="blog-table" class="table align-middle text-nowrap w-100">
                <thead>
                    <tr>
                        <th scope="col">Judul</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Penulis</th>
                        <th scope="col">Status</th>
                        <th scope="col">Dibuat</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const tabel = $('#blog-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @js(route('admin.blog.data')),
            data: function (d) {
                d.category = $('#filter-category').val();
            },
        },
        order: [[4, 'desc']],
        columns: [
            { data: 'title',       orderable: true  },
            { data: 'category',    orderable: false },
            { data: 'author',      orderable: false },
            { data: 'status',      orderable: false, searchable: false },
            { data: 'created_at',  orderable: true  },
            { data: 'action',      orderable: false, searchable: false },
        ],
        language: { url: @js(asset('vendor/datatables/id.json')) },
    });

    $('#filter-category').on('change', function () {
        tabel.ajax.reload();
    });
})();
</script>
@endpush
