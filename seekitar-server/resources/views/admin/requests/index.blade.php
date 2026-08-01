@extends('admin.layouts.admin')

@section('title', 'Manajemen Permintaan — Seekitar')

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
    #requests-table tbody tr {
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
            <h5 class="card-title fw-semibold mb-0">Daftar Permintaan Belanja</h5>
            <div class="d-flex align-items-center gap-2">
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-requests')
                <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                  <i class="ti ti-search" aria-hidden="true"></i>
                  <span>Detail</span>
                </a>
                @endcan
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="requests-table-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <label for="filter-status" class="visually-hidden">Saring status</label>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  @foreach (\App\Enums\RequestStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="requests-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Judul Permintaan</th>
                  <th>Peminta</th>
                  <th>Budget</th>
                  <th>Kategori</th>
                  <th>Penawaran</th>
                  <th>Status</th>
                  <th>Dibuat</th>
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
      var table = $('#requests-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.requests.data') }}",
          data: function (d) {
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'title', name: 'title' },
          { data: 'buyer_name', name: 'buyer.name' },
          { data: 'budget', name: 'budget' },
          { data: 'category_name', name: 'category.name' },
          { data: 'offers_count', name: 'offers_count', searchable: false },
          { data: 'status', name: 'status' },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#requests-table_wrapper').find('.dt-filters');
          $('#requests-table-toolbar').children().appendTo($slot);
          $('#requests-table-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#requests-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#requests-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#requests-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          var baseUrl = "{{ url('admin/requests') }}";
          @can('manage-requests')
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#filter-status').change(function(){
        table.draw();
      });
    });
  </script>
@endpush
