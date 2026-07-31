@extends('admin.layouts.admin')

@section('title', 'Permintaan Pengguna — Seekitar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <!-- Filter Card -->
      <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
          <h4 class="card-title">Filter Permintaan</h4>
          <p class="card-subtitle mb-3">Saring permintaan belanja dari pembeli berdasarkan status.</p>
          
          <div class="row">
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status Permintaan</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                <option value="open">Terbuka (Open)</option>
                <option value="closed">Ditutup (Closed)</option>
                <option value="expired">Kedaluwarsa (Expired)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card w-100 shadow-sm">
        <div class="card-body">
          <div class="d-md-flex align-items-center justify-content-between mb-4">
            <div>
              <h4 class="card-title">Daftar Permintaan Belanja</h4>
              <p class="card-subtitle">Semua kebutuhan belanja yang disiarkan oleh pembeli di wilayah kabupaten.</p>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle text-nowrap" id="requests-table" style="width: 100%;">
              <thead>
                <tr>
                  <th>Judul Permintaan</th>
                  <th>Pembeli</th>
                  <th>Kategori</th>
                  <th>Penawaran</th>
                  <th>Kedaluwarsa</th>
                  <th>Tanggal Siar</th>
                  <th>Status</th>
                  <th>Aksi</th>
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
          { data: 'title', name: 'title',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">' + data + '</span>';
            }
          },
          { data: 'buyer', name: 'user.name' },
          { data: 'category_id', name: 'category_id', defaultContent: '—' },
          { 
            data: 'offers_count', 
            name: 'offers_count',
            render: function(data, type, row) {
              return '<span class="badge bg-light-info text-info fw-bold">' + data + ' Penawaran</span>';
            }
          },
          { data: 'expires_at', name: 'expires_at' },
          { data: 'created_at', name: 'created_at' },
          { 
            data: 'status', 
            name: 'status',
            render: function(data, type, row) {
              var badgeClass = 'secondary';
              if (data === 'Terbuka' || data === 'Open') badgeClass = 'success';
              else if (data === 'Ditutup' || data === 'Closed') badgeClass = 'secondary';
              else if (data === 'Kedaluwarsa' || data === 'Expired') badgeClass = 'danger';
              return '<span class="badge bg-light-' + badgeClass + ' text-' + badgeClass + ' fw-semibold">' + data + '</span>';
            }
          },
          { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        }
      });

      $('#filter-status').change(function(){
        table.draw();
      });
    });
  </script>
@endpush
