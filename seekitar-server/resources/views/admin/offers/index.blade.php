@extends('admin.layouts.admin')

@section('title', 'Manajemen Penawaran — Seekitar')

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
    #offers-table tbody tr {
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
      <!-- Filter & Export Card -->
      <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-3">
            <div>
              <h4 class="card-title">Filter Penawaran</h4>
              <p class="card-subtitle mb-0">Saring penawaran harga dari toko berdasarkan statusnya.</p>
            </div>
            <div class="mt-3 mt-md-0">
              @can('manage-offers')
                <a href="{{ route('admin.offers.export') }}" id="export-csv-btn" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                  <i class="ti ti-download fs-4"></i> Ekspor CSV
                </a>
              @endcan
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status Penawaran</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\OfferStatus::cases() as $status)
                  <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-semibold mb-0">Daftar Penawaran Toko</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              @can('manage-offers')
              <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Lihat Detail">
                <i class="ti ti-eye"></i>
                <span>Lihat</span>
              </a>
              <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Tolak/Hapus">
                <i class="ti ti-x"></i>
                <span>Tolak</span>
              </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle text-nowrap" id="offers-table" style="width: 100%;">
            <thead>
              <tr>
                <th>Toko Penawar</th>
                <th>Permintaan Asal</th>
                <th>Harga Penawaran</th>
                <th>Total Biaya</th>
                <th>Status</th>
                <th>Masa Berlaku</th>
                <th>Tanggal Dibuat</th>
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
      var table = $('#offers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.offers.data') }}",
          data: function (d) {
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'store_name', name: 'store.name' },
          { data: 'request_title', name: 'request.title',
            render: function(data, type, row) {
              return '<span style="white-space: normal; max-width: 250px; display: inline-block;">' + data + '</span>';
            }
          },
          { data: 'price', name: 'price' },
          { data: 'total', name: 'total', searchable: false,
            render: function(data, type, row) {
              return '<span class="fw-bold text-primary">' + data + '</span>';
            }
          },
          { 
            data: 'status', 
            name: 'status',
            render: function(data, type, row) {
              var badgeClass = 'secondary';
              if (data === 'Diterima' || data === 'Accepted') badgeClass = 'success';
              else if (data === 'Ditolak' || data === 'Rejected') badgeClass = 'danger';
              else if (data === 'Menunggu' || data === 'Pending') badgeClass = 'warning';
              return '<span class="badge bg-light-' + badgeClass + ' text-' + badgeClass + ' fw-semibold">' + data + '</span>';
            }
          },
          { data: 'expires_at', name: 'expires_at' },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#offers-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#offers-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#offers-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          @can('manage-offers')
          var baseUrl = "{{ url('admin/offers') }}";
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id);
          $('#action-delete').attr('href', baseUrl + '/' + selectedRow.id);
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-delete').on('click', function(e) {
          e.preventDefault();
          if (selectedRow && confirm('Tolak penawaran ini? Penawaran akan ditolak dan pembeli akan diberitahu.')) {
              var form = $('<form>', {
                  'method': 'POST',
                  'action': "{{ url('admin/offers') }}/" + selectedRow.id,
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

      $('#filter-status').change(function(){
        table.draw();
        
        var status = $(this).val();
        var exportUrl = "{{ route('admin.offers.export') }}";
        if (status) {
          exportUrl += '?status=' + encodeURIComponent(status);
        }
        $('#export-csv-btn').attr('href', exportUrl);
      });
    });
  </script>
@endpush
