@extends('admin.layouts.admin')

@section('title', 'Langganan Toko — Seekitar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
  <style>
    .table-action-btn {
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      width: 80px;
      height: 70px;
      border-radius: 0.25rem;
      font-size: 0.75rem;
      padding: 0.5rem 0.25rem;
    }
    .table-action-btn .ti {
      font-size: 1.5rem;
      margin-bottom: 0.25rem;
    }
    #subscriptions-table tbody tr {
      cursor: pointer;
    }
    .row-selected {
      background-color: #fcefe2 !important;
      font-weight: 600;
    }
  </style>
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <!-- Filter Card -->
      <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
          <h4 class="card-title">Filter Langganan</h4>
          <p class="card-subtitle mb-3">Saring data langganan berdasarkan paket atau status keaktifan billing.</p>
          
          <div class="row g-3">
            <div class="col-md-3">
              <label for="filter-plan" class="form-label">Paket Langganan</label>
              <select class="form-select" id="filter-plan">
                <option value="">Semua Paket</option>
                <option value="pro">Toko PRO (pro)</option>
                <option value="boost">Boost Listing (boost)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="cancelled">Dibatalkan</option>
                <option value="expired">Kedaluwarsa</option>
                <option value="pending">Menunggu</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-semibold mb-0">Daftar Langganan Toko</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              @can('manage-subscriptions')
              <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Langganan">
                <i class="ti ti-pencil"></i>
                <span>Edit</span>
              </a>
              <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Batalkan Langganan">
                <i class="ti ti-x"></i>
                <span>Batalkan</span>
              </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-sm table-bordered align-middle text-nowrap" id="subscriptions-table" style="width: 100%;">
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
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
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
