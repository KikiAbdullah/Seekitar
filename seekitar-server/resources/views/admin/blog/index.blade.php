@extends('admin.layouts.admin')

@section('title', 'Manajemen Blog — Seekitar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card w-100">
        <div class="card-body">
          <div class="d-md-flex align-items-center justify-content-between mb-4">
            <div>
              <h4 class="card-title">Artikel Blog</h4>
              <p class="card-subtitle">Daftar semua artikel edukasi dan berita hyperlocal untuk pengguna Seekitar.</p>
            </div>
            <div>
              @can('manage-blog')
                <a href="{{ route('admin.blog.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                  <i class="ti ti-plus fs-4"></i> Tambah Artikel
                </a>
              @endcan
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-striped table-sm table-bordered align-middle text-nowrap" id="blog-table" style="width: 100%;">
              <thead>
                <tr>
                  <th>Judul Artikel</th>
                  <th>Kategori</th>
                  <th>Penulis</th>
                  <th>Tanggal Terbit</th>
                  <th>Status</th>
                  <th>Tanggal Dibuat</th>
                  <th>Aksi</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('vendor/mordenize/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
  <script>
    $(function () {
      $('#blog-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.blog.data') }}",
        columns: [
          { data: 'title', name: 'title',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">' + data + '</span>';
            }
          },
          { data: 'category', name: 'category' },
          { data: 'author', name: 'author' },
          { data: 'published_at', name: 'published_at' },
          { data: 'status', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' },
          { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        }
      });
    });
  </script>
@endpush
