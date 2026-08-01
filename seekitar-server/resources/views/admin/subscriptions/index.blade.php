@extends('admin.layouts.admin')

@section('title', 'Langganan Toko — Seekitar')

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
    #subscriptions-table tbody tr {
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
            <h5 class="card-title fw-semibold mb-0">Daftar Langganan Toko</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              @can('manage-subscriptions')
              <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                <i class="ti ti-search" aria-hidden="true"></i>
                <span>Detail</span>
              </a>
              <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Langganan">
                <i class="ti ti-pencil" aria-hidden="true"></i>
                <span>Edit</span>
              </a>
              <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Batalkan Langganan">
                <i class="ti ti-x" aria-hidden="true"></i>
                <span>Batalkan</span>
              </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="subscriptions-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <select class="form-select js-select2" id="filter-plan">
                  <option value="">Semua Paket</option>
                  <option value="pro">Toko PRO (pro)</option>
                  <option value="boost">Boost Listing (boost)</option>
                </select>
              </div>
              <div>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  <option value="active">Aktif</option>
                  <option value="cancelled">Dibatalkan</option>
                  <option value="expired">Kedaluwarsa</option>
                  <option value="pending">Menunggu</option>
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="subscriptions-table" style="width: 100%;">
            <thead>
              <tr>
                <th>ID</th>
                <th>Pengguna</th>
                <th>Toko</th>
                <th>Paket</th>
                <th>Biaya Billing</th>
                <th>Mulai Tanggal</th>
                <th>Selesai Tanggal</th>
                <th>Status</th>
                <th>Tanggal Transaksi</th>
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
      var table = $('#subscriptions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.subscriptions.data') }}",
          data: function (d) {
            d.plan = $('#filter-plan').val();
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'user', name: 'user' },
          { data: 'store.name', name: 'store.name', defaultContent: '—' },
          { data: 'plan_label', name: 'plan' },
          { data: 'amount', name: 'amount' },
          { data: 'starts_at', name: 'starts_at' },
          { data: 'ends_at', name: 'ends_at' },
          { data: 'status_badge', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[8, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#subscriptions-table_wrapper').find('.dt-filters');
          $('#subscriptions-toolbar').children().appendTo($slot);
          $('#subscriptions-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#subscriptions-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#subscriptions-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#subscriptions-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          @can('manage-subscriptions')
          var baseUrl = "{{ url('admin/subscriptions') }}";
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');
          $('#action-delete').attr('href', baseUrl + '/' + selectedRow.id);
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-delete').on('click', function(e) {
          e.preventDefault();
          if (selectedRow && confirm('Batalkan langganan ini? Pembeli akan kehilangan akses ke fitur premium.')) {
              var form = $('<form>', {
                  'method': 'POST',
                  'action': "{{ url('admin/subscriptions') }}/" + selectedRow.id,
                  'style': 'display:none'
              });
              form.append($('<input>', {
                  'type': 'hidden',
                  'name': '_method',
                  'value': 'DELETE'
              }));
              form.append($('<input>', {
                  'type': 'hidden',
                  'name': '_token',
                  'value': '{{ csrf_token() }}'
              }));
              $('body').append(form);
              form.submit();
          }
      });

      $('#filter-plan, #filter-status').change(function(){
        table.draw();
      });
    });
  </script>
@endpush
