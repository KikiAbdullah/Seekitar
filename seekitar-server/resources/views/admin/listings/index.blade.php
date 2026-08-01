@extends('admin.layouts.admin')

@section('title', 'Manajemen Etalase Listing — Seekitar')

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
    #listings-table tbody tr {
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
            <h5 class="card-title fw-semibold mb-0">Daftar Listing Produk & Jasa</h5>
            <div class="d-flex align-items-center gap-2">
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-listings')
                <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                  <i class="ti ti-search" aria-hidden="true"></i>
                  <span>Detail</span>
                </a>
                <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Takedown/Hapus Listing">
                  <i class="ti ti-trash" aria-hidden="true"></i>
                  <span>Takedown</span>
                </a>
                @endcan
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="listings-table-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <label for="filter-type" class="visually-hidden">Saring tipe</label>
                <select class="form-select js-select2" id="filter-type">
                  <option value="">Semua Tipe</option>
                  @foreach (\App\Enums\ListingType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label for="filter-status" class="visually-hidden">Saring status</label>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  @foreach (\App\Enums\ListingStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="listings-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Judul Listing</th>
                  <th>Toko</th>
                  <th>Tipe</th>
                  <th>Harga</th>
                  <th>Favorit</th>
                  <th>Status</th>
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
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#listings-table_wrapper').find('.dt-filters');
          $('#listings-table-toolbar').children().appendTo($slot);
          $('#listings-table-toolbar').remove();
          $slot.filter(':empty').remove();
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
          $('#action-show').attr('href', baseUrl + '/' + selectedRow.id);
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-delete').on('click', function(e) {
        e.preventDefault();
        if (selectedRow && confirm('Apakah Anda yakin ingin men-takedown/menghapus listing ini?')) {
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
