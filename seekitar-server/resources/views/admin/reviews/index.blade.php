@extends('admin.layouts.admin')

@section('title', 'Manajemen Review — Seekitar')

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
    #reviews-table tbody tr {
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
            <h5 class="card-title fw-semibold mb-0">Daftar Ulasan & Rating</h5>
            <div class="d-flex align-items-center gap-2">
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-reviews')
                <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Hapus Ulasan">
                  <i class="ti ti-trash" aria-hidden="true"></i>
                  <span>Hapus</span>
                </a>
                @endcan
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="reviews-table-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <label for="filter-rating" class="visually-hidden">Saring rating</label>
                <select class="form-select js-select2" id="filter-rating">
                  <option value="">Semua Rating</option>
                  @for ($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}">{{ $i }} ★</option>
                  @endfor
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="reviews-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
                  <th>Penulis</th>
                  <th>Toko</th>
                  <th>Rating</th>
                  <th>Komentar</th>
                  <th>Arah</th>
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
      var table = $('#reviews-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.reviews.data') }}",
          data: function (d) {
            d.rating = $('#filter-rating').val();
          }
        },
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'reviewer_name', name: 'reviewer.name' },
          { data: 'store_name', name: 'store.name' },
          { data: 'rating', name: 'rating' },
          { data: 'comment', name: 'comment' },
          { data: 'direction', name: 'direction' },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[6, 'desc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#reviews-table_wrapper').find('.dt-filters');
          $('#reviews-table-toolbar').children().appendTo($slot);
          $('#reviews-table-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#reviews-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#reviews-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#reviews-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-delete').on('click', function(e) {
        e.preventDefault();
        if (selectedRow && confirm('Apakah Anda yakin ingin menghapus ulasan ini? Penghapusan ulasan akan memicu kalkulasi ulang reputasi/rating toko dan pengulas.')) {
          var form = $('<form>', {
            'method': 'POST',
            'action': "{{ url('admin/reviews') }}/" + selectedRow.id,
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

      $('#filter-rating').change(function(){
        table.draw();
      });
    });
  </script>
@endpush
