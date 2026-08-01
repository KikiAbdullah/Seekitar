@extends('admin.layouts.admin')

@section('title', 'Manajemen Pengguna — Seekitar')

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
    #users-table tbody tr {
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
      <!-- Table Card -->
      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-semibold mb-0">Daftar Pengguna</h5>
            <div class="d-flex align-items-center gap-2">
              @can('manage-users')
                <a href="{{ route('admin.users.export') }}" id="export-csv-btn" class="btn btn-outline-secondary table-action-btn" title="Ekspor CSV" target="_blank">
                  <i class="ti ti-download fs-4" aria-hidden="true"></i> Ekspor CSV
                </a>
              @endcan
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-users')
                <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                  <i class="ti ti-search" aria-hidden="true"></i>
                  <span>Detail</span>
                </a>
                <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Pengguna">
                  <i class="ti ti-pencil" aria-hidden="true"></i>
                  <span>Edit</span>
                </a>
                @endcan
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="users-table-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <label for="filter-status" class="visually-hidden">Saring status</label>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  @foreach (\App\Enums\UserStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="users-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Nama Pengguna</th>
                  <th>Nomor HP</th>
                  <th>Email</th>
                  <th>Alamat Domisili</th>
                  <th>Rating</th>
                  <th>Status</th>
                  <th>Tanggal Terdaftar</th>
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
      var table = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.users.data') }}",
          data: function (d) {
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'name', name: 'name' },
          { data: 'phone', name: 'phone',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">' + data + '</span>';
            }
          },
          { data: 'email', name: 'email' },
          { data: 'address', name: 'address',
            render: function(data, type, row) {
              return data === '—' ? data : '<div style="white-space: normal; max-width: 250px;">' + data + '</div>';
            }
          },
          { data: 'rating', name: 'rating_avg', orderable: false, searchable: false },
          { data: 'status', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#users-table_wrapper').find('.dt-filters');
          $('#users-table-toolbar').children().appendTo($slot);
          $('#users-table-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#users-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#users-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#users-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          var baseUrl = "{{ url('admin/users') }}";
          @can('manage-users')
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#filter-status').change(function(){
        table.draw();
        
        var status = $(this).val();
        var exportUrl = "{{ route('admin.users.export') }}";
        if (status) {
          exportUrl += '?status=' + encodeURIComponent(status);
        }
        $('#export-csv-btn').attr('href', exportUrl);
      });
    });
  </script>
@endpush
