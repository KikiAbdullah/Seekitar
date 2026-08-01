@extends('admin.layouts.admin')

@section('title', 'Manajemen Blog — Seekitar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
  <style>
    .table-action-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .table-action-btn .ti {
      font-size: 1.125rem;
    }
    #blog-table tbody tr {
      cursor: pointer;
    }
    .row-selected,
    .row-selected td {
      background-color: #fcefe2 !important;
      font-weight: 600;
    }
  </style>
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <h5 class="card-title fw-bold text-dark mb-0">Artikel Blog</h5>
              <p class="card-subtitle mb-0">Daftar semua artikel edukasi dan berita hyperlocal untuk pengguna Seekitar.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-blog')
                  <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Artikel">
                    <i class="ti ti-pencil" aria-hidden="true"></i>
                    <span>Edit</span>
                  </a>
                  <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Hapus Artikel">
                    <i class="ti ti-trash" aria-hidden="true"></i>
                    <span>Hapus</span>
                  </a>
                @endcan
              </div>
              @can('manage-blog')
                <a href="{{ route('admin.blog.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                  <i class="ti ti-plus fs-4" aria-hidden="true"></i> Tambah Artikel
                </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="blog-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Judul Artikel</th>
                  <th>Kategori</th>
                  <th>Penulis</th>
                  <th>Tanggal Terbit</th>
                  <th>Status</th>
                  <th>Tanggal Dibuat</th>
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
      var table = $('#blog-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.blog.data') }}",
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'title', name: 'title',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">' + data + '</span>';
            }
          },
          { data: 'category', name: 'category' },
          { data: 'author', name: 'author' },
          { data: 'published_at', name: 'published_at' },
          { data: 'status', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' }
        ],
        order: [[6, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#blog-table_wrapper').find('.dt-filters');
          $('#blog-toolbar').children().appendTo($slot);
          $('#blog-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#blog-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#blog-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();

        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#blog-table tbody tr').removeClass('row-selected');
          }

          $(this).addClass('row-selected');
          selectedRow = rowData;

          var baseUrl = "{{ url('admin/blog') }}";
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');

          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-delete').on('click', function(e) {
        e.preventDefault();

        if (!selectedRow) {
          return;
        }

        if (!confirm('Apakah Anda yakin ingin menghapus artikel ini?')) {
          return;
        }

        var baseUrl = "{{ url('admin/blog') }}";
        var form = $('<form>', { action: baseUrl + '/' + selectedRow.id, method: 'POST' });
        form.append($('<input>', { type: 'hidden', name: '_method', value: 'DELETE' }));
        form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
        $('body').append(form);
        form.submit();
      });
    });
  </script>
@endpush
