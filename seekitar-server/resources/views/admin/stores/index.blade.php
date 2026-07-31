@extends('admin.layouts.admin')

@section('title', 'Manajemen Toko — Seekitar')

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
    #stores-table tbody tr {
      cursor: pointer;
    }
    .row-selected {
      background-color: #fcefe2 !important; /* Mordenize primary-light */
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
              <h4 class="card-title">Filter Toko</h4>
              <p class="card-subtitle mb-0">Saring toko berdasarkan status verifikasi mereka.</p>
            </div>
            <div class="mt-3 mt-md-0">
              @can('manage-stores')
                <a href="{{ route('admin.stores.export') }}" id="export-csv-btn" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                  <i class="ti ti-download fs-4"></i> Ekspor CSV
                </a>
              @endcan
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status Toko</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\StoreStatus::cases() as $status)
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
            <h5 class="card-title fw-semibold mb-0">Daftar Toko</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                <i class="ti ti-eye"></i>
                <span>Detail</span>
              </a>
              @can('manage-stores')
              <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Toko">
                <i class="ti ti-pencil"></i>
                <span>Edit</span>
              </a>
              <a id="action-block" href="#" class="btn btn-outline-danger table-action-btn" title="Blokir/Buka Blokir">
                <i class="ti ti-building-store-off"></i>
                <span>Blokir</span>
              </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-sm table-bordered align-middle text-nowrap" id="stores-table" style="width: 100%;">
            <thead>
              <tr>
                <th>Nama Toko</th>
                <th>Pemilik</th>
                <th>Kabupaten</th>
                <th>Rating</th>
                <th>Keaktifan</th>
                <th>Status</th>
                <th>Tanggal Terdaftar</th>
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
          { data: 'owner', name: 'owner' },
          { data: 'regency', name: 'regency' },
          { data: 'rating', name: 'rating_avg' },
          { 
            data: 'is_active', 
            name: 'is_active',
            render: function(data, type, row) {
              if (data) {
                return '<span class="badge bg-success text-white fw-semibold fs-2">Aktif</span>';
              }
              return '<span class="badge bg-secondary text-white fw-semibold fs-2">Nonaktif</span>';
            }
          },
          { data: 'status', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
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
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          @can('manage-stores')
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');
          $('#action-block').attr('href', baseUrl + '/' + selectedRow.id + '/block');
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
