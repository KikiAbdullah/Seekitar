@extends('admin.layouts.admin')

@section('title', 'Manajemen Toko — Seekitar')

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
    #stores-table tbody tr {
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
            <h5 class="card-title fw-semibold mb-0">Daftar Toko</h5>
            <div class="d-flex align-items-center gap-2">
              @can('manage-stores')
                <a href="{{ route('admin.stores.export') }}" id="export-csv-btn" class="btn btn-outline-secondary table-action-btn" title="Ekspor CSV" target="_blank">
                  <i class="ti ti-download fs-4" aria-hidden="true"></i> Ekspor CSV
                </a>
              @endcan
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-stores')
                <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                  <i class="ti ti-search" aria-hidden="true"></i>
                  <span>Detail</span>
                </a>
                <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Toko">
                  <i class="ti ti-pencil" aria-hidden="true"></i>
                  <span>Edit</span>
                </a>
                @endcan
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="stores-table-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <label for="filter-status" class="visually-hidden">Saring status</label>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  @foreach (\App\Enums\StoreStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="stores-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Nama Toko</th>
                  <th>Owner</th>
                  <th>Kota</th>
                  <th>Rating</th>
                  <th>Status Aktif</th>
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
      var table = $('#stores-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.stores.data') }}",
          data: function (d) {
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'name', name: 'name' },
          { data: 'owner', name: 'owner.name' },
          { data: 'regency', name: 'regency' },
          { data: 'rating', name: 'rating_avg', orderable: false, searchable: false },
          { data: 'is_active', name: 'is_active',
            render: function(data, type, row) {
              return data ? '<span class="badge bg-success text-white fw-semibold fs-2">Aktif</span>' : '<span class="badge bg-secondary text-white fw-semibold fs-2">Nonaktif</span>';
            }
          },
          { data: 'status', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#stores-table_wrapper').find('.dt-filters');
          $('#stores-table-toolbar').children().appendTo($slot);
          $('#stores-table-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#stores-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#stores-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#stores-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          var baseUrl = "{{ url('admin/stores') }}";
          @can('manage-stores')
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#filter-status').change(function(){
        table.draw();
        
        var status = $(this).val();
        var exportUrl = "{{ route('admin.stores.export') }}";
        if (status) {
          exportUrl += '?status=' + encodeURIComponent(status);
        }
        $('#export-csv-btn').attr('href', exportUrl);
      });
    });
  </script>
@endpush
