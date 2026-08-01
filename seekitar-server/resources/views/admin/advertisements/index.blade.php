@extends('admin.layouts.admin')

@section('title', 'Manajemen Iklan Banner — Seekitar')

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
    #ads-table tbody tr {
      cursor: pointer;
    }
    .row-selected,
    .row-selected td {
      background-color: #fcefe2 !important; /* Mordenize primary-light */
      font-weight: 600;
    }
  </style>
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <!-- Table Card -->
      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-semibold mb-0">Daftar Iklan Banner</h5>
            <div class="d-flex align-items-center gap-2">
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-advertisements')
                <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                  <i class="ti ti-search" aria-hidden="true"></i>
                  <span>Detail</span>
                </a>
                <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Iklan">
                  <i class="ti ti-pencil" aria-hidden="true"></i>
                  <span>Edit</span>
                </a>
                @endcan
              </div>
              @can('manage-advertisements')
                <a href="{{ route('admin.advertisements.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                  <i class="ti ti-plus fs-4" aria-hidden="true"></i> Buat Iklan Baru
                </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="ads-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <select class="form-select js-select2" id="filter-position">
                  <option value="">Semua Posisi</option>
                  <option value="feed">Feed Utama</option>
                  <option value="sidebar">Sidebar</option>
                  <option value="search">Halaman Pencarian</option>
                  <option value="category">Halaman Kategori</option>
                </select>
              </div>
              <div>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  <option value="available">Tersedia (Available)</option>
                  <option value="active">Aktif (Active)</option>
                  <option value="inactive">Nonaktif (Inactive)</option>
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="ads-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Judul Iklan</th>
                  <th>Posisi</th>
                  <th>Tarif/Hari</th>
                  <th>Mulai Tanggal</th>
                  <th>Selesai Tanggal</th>
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
      var table = $('#ads-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.advertisements.data') }}",
          data: function (d) {
            d.position = $('#filter-position').val();
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'title', name: 'title',
            render: function(data, type, row) {
              return '<div class="d-flex align-items-center">' +
                     '<div>' +
                     '<h6 class="fw-semibold text-dark mb-0">' + data + '</h6>' +
                     (row.link_url ? '<a href="' + row.link_url + '" class="fs-2 text-primary text-decoration-none" target="_blank">' + row.link_url + '</a>' : '') +
                     '</div></div>';
            }
          },
          { data: 'position_label', name: 'position' },
          { data: 'price_per_day', name: 'price_per_day' },
          { data: 'starts_at', name: 'starts_at' },
          { data: 'ends_at', name: 'ends_at' },
          { 
            data: 'status', 
            name: 'status',
            render: function(data, type, row) {
              var badgeClass = 'secondary';
              var label = data;
              if (data === 'active') {
                badgeClass = 'success';
                label = 'Aktif';
              } else if (data === 'available') {
                badgeClass = 'info';
                label = 'Tersedia';
              } else if (data === 'inactive') {
                badgeClass = 'danger';
                label = 'Nonaktif';
              }
              return '<span class="badge bg-light-' + badgeClass + ' text-' + badgeClass + ' fw-semibold">' + label + '</span>';
            }
          },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#ads-table_wrapper').find('.dt-filters');
          $('#ads-toolbar').children().appendTo($slot);
          $('#ads-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#ads-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#ads-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#ads-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          var baseUrl = "{{ url('admin/advertisements') }}";
          @can('manage-advertisements')
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#filter-position, #filter-status').change(function(){
        table.draw();
      });
    });
  </script>
@endpush
