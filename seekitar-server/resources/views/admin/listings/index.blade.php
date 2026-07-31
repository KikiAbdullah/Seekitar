@extends('admin.layouts.admin')

@section('title', 'Manajemen Etalase Listing — Seekitar')

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
    #listings-table tbody tr {
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
          <h4 class="card-title">Filter Listing</h4>
          <p class="card-subtitle mb-3">Saring etalase listing berdasarkan tipe barang/jasa atau status tayang.</p>
          
          <div class="row g-3">
            <div class="col-md-3">
              <label for="filter-type" class="form-label">Tipe Listing</label>
              <select class="form-select" id="filter-type">
                <option value="">Semua Tipe</option>
                <option value="product">Barang</option>
                <option value="service">Jasa</option>
                <option value="rental">Sewa</option>
              </select>
            </div>
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\ListingStatus::cases() as $status)
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
            <h5 class="card-title fw-semibold mb-0">Etalase Listing Produk & Jasa</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              @can('manage-listings')
              <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Listing">
                <i class="ti ti-pencil"></i>
                <span>Edit</span>
              </a>
              <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Hapus Listing">
                <i class="ti ti-trash"></i>
                <span>Hapus</span>
              </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-sm table-bordered align-middle text-nowrap" id="listings-table" style="width: 100%;">
            <thead>
              <tr>
                <th>Nama Listing / Produk</th>
                <th>Nama Toko</th>
                <th>Tipe</th>
                <th>Harga</th>
                <th>Favorit</th>
                <th>Status</th>
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
      var table = $('#listings-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.listings.data') }}",
          data: function (d) {
            d.listing_type = $('#filter-type').val();
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'title', name: 'title' },
          { data: 'store_name', name: 'store.name' },
          { data: 'listing_type', name: 'listing_type' },
          { data: 'price', name: 'price' },
          { data: 'favorites_count', name: 'favorites_count', searchable: false },
          { data: 'status', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#listings-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#listings-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#listings-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          var baseUrl = "{{ url('admin/listings') }}";
          @can('manage-listings')
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');
          $('#action-delete').attr('href', baseUrl + '/' + selectedRow.id);
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-delete').on('click', function(e) {
          e.preventDefault();
          if (selectedRow && confirm('Apakah Anda yakin ingin menghapus listing ini? Tindakan ini tidak dapat dibatalkan.')) {
              var form = $('<form>', {
                  'method': 'POST',
                  'action': "{{ url('admin/listings') }}/" + selectedRow.id,
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

      $('#filter-type, #filter-status').change(function(){
        table.draw();
      });
    });
  </script>
@endpush
