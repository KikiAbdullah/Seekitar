@extends('admin.layouts.admin')

@section('title', 'Manajemen Ulasan — Seekitar')

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
    #reviews-table tbody tr {
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
      <!-- Filter Card -->
      <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
          <h4 class="card-title">Filter Ulasan</h4>
          <p class="card-subtitle mb-3">Saring ulasan masuk berdasarkan nilai rating bintang.</p>
          
          <div class="row">
            <div class="col-md-3">
              <label for="filter-rating" class="form-label">Rating Bintang</label>
              <select class="form-select" id="filter-rating">
                <option value="">Semua Rating</option>
                <option value="5">★ 5 (Sangat Baik)</option>
                <option value="4">★ 4 (Baik)</option>
                <option value="3">★ 3 (Cukup)</option>
                <option value="2">★ 2 (Buruk)</option>
                <option value="1">★ 1 (Sangat Buruk)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-semibold mb-0">Daftar Ulasan & Rating</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
                @can('manage-reviews')
                <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Hapus Ulasan">
                    <i class="ti ti-trash"></i>
                    <span>Hapus</span>
                </a>
                @endcan
            </div>
          </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-sm table-bordered align-middle text-nowrap" id="reviews-table" style="width: 100%;">
              <thead>
                <tr>
                  <th>No Pesanan</th>
                  <th>Pengulas</th>
                  <th>Pihak Dinilai (Toko)</th>
                  <th>Arah Ulasan</th>
                  <th>Rating</th>
                  <th>Teks Komentar</th>
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
          { 
            data: 'order_id', 
            name: 'order_id',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">#' + data + '</span>';
            }
          },
          { data: 'reviewer_name', name: 'reviewer_id' },
          { data: 'store_name', name: 'store_id' },
          { 
            data: 'direction', 
            name: 'direction',
            render: function(data, type, row) {
              if (data === 'buyer_to_store') {
                return '<span class="badge bg-light-primary text-primary">Pembeli → Toko</span>';
              }
              return '<span class="badge bg-light-secondary text-secondary">Toko → Pembeli</span>';
            }
          },
          { 
            data: 'rating', 
            name: 'rating',
            render: function(data, type, row) {
              var stars = '';
              for (var i = 1; i <= 5; i++) {
                if (i <= data) {
                  stars += '<i class="fa-solid fa-star text-warning me-1"></i>';
                } else {
                  stars += '<i class="fa-regular fa-star text-muted me-1"></i>';
                }
              }
              return '<span class="text-nowrap">' + stars + ' (' + data + ')</span>';
            }
          },
          { 
            data: 'comment', 
            name: 'comment',
            render: function(data, type, row) {
              if (!data) return '<span class="text-muted font-italic">tanpa ulasan teks</span>';
              return '<div style="white-space: normal; max-width: 300px;">' + data + '</div>';
            }
          },
          { data: 'created_at', name: 'created_at' },
        ],
        order: [[7, 'desc']],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
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
          if (selectedRow && confirm('Apakah Anda yakin ingin menghapus ulasan ini? Tindakan ini tidak dapat dibatalkan.')) {
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
