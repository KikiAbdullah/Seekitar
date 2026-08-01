@extends('admin.layouts.admin')

@section('title', 'Manajemen Pesanan — Seekitar')

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
    #orders-table tbody tr {
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
            <h5 class="card-title fw-semibold mb-0">Daftar Pesanan</h5>
            <div class="d-flex align-items-center gap-2">
              @can('manage-orders')
                <a href="{{ route('admin.orders.export') }}" id="export-csv-btn" class="btn btn-outline-secondary table-action-btn" title="Ekspor CSV" target="_blank">
                  <i class="ti ti-download fs-4" aria-hidden="true"></i> Ekspor CSV
                </a>
              @endcan
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-orders')
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
          <div id="orders-table-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <label for="filter-status" class="visually-hidden">Saring status</label>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  @foreach (\App\Enums\OrderStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="orders-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Nomor Pesanan</th>
                  <th>Pembeli</th>
                  <th>Toko</th>
                  <th>Status</th>
                  <th>Total</th>
                  <th>Tanggal</th>
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
      var table = $('#orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.orders.data') }}",
          data: function (d) {
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'order_number', name: 'order_number' },
          { data: 'buyer_name', name: 'buyer.name' },
          { data: 'store_name', name: 'store.name' },
          { data: 'status_label', name: 'status' },
          { data: 'total_amount', name: 'total_amount', searchable: false },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[6, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#orders-table_wrapper').find('.dt-filters');
          $('#orders-table-toolbar').children().appendTo($slot);
          $('#orders-table-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#orders-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#orders-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#orders-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          var baseUrl = "{{ url('admin/orders') }}";
          @can('manage-orders')
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#filter-status').change(function(){
        table.draw();
        
        var status = $(this).val();
        var exportUrl = "{{ route('admin.orders.export') }}";
        if (status) {
          exportUrl += '?status=' + encodeURIComponent(status);
        }
        $('#export-csv-btn').attr('href', exportUrl);
      });
    });
  </script>
@endpush
