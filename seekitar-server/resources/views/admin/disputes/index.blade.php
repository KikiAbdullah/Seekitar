@extends('admin.layouts.admin')

@section('title', 'Laporan Masalah (Dispute) — Seekitar')

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
    #disputes-table tbody tr {
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
          <h4 class="card-title">Filter Laporan</h4>
          <p class="card-subtitle mb-3">Saring laporan sengketa transaksi berdasarkan status penanganan.</p>
          
          <div class="row">
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status Laporan</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                <option value="open">Terbuka (Open)</option>
                <option value="resolved">Selesai (Resolved)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card w-100 shadow-sm">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-semibold mb-0">Daftar Laporan Masalah / Sengketa Transaksi</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              @can('manage-disputes')
              <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Tangani Sengketa">
                <i class="ti ti-check"></i>
                <span>Selesaikan</span>
              </a>
              <a id="action-delete" href="#" class="btn btn-outline-danger table-action-btn" title="Hapus Laporan">
                <i class="ti ti-trash"></i>
                <span>Hapus</span>
              </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-sm table-bordered align-middle text-nowrap" id="disputes-table" style="width: 100%;">
            <thead>
              <tr>
                <th>No Pesanan</th>
                <th>Alasan Laporan</th>
                <th>Tenggat Respon (SLA)</th>
                <th>Status</th>
                <th>Overdue (Lewat SLA)</th>
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
      var table = $('#disputes-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.disputes.data') }}",
          data: function (d) {
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          { data: 'order_number', name: 'order.order_number',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">#' + data + '</span>';
            }
          },
          { data: 'reason', name: 'reason',
            render: function(data, type, row) {
              var labels = {
                'item_not_received': 'Barang Tidak Diterima',
                'item_damaged': 'Barang Rusak/Cacat',
                'item_not_matching': 'Barang Tidak Sesuai Deskripsi',
                'others': 'Lainnya'
              };
              return labels[data] ?? data;
            }
          },
          { data: 'response_deadline', name: 'response_deadline' },
          { 
            data: 'status', 
            name: 'status',
            render: function(data, type, row) {
              var badgeClass = 'secondary';
              var label = data;
              if (data === 'open') {
                badgeClass = 'warning';
                label = 'Terbuka';
              } else if (data === 'resolved') {
                badgeClass = 'success';
                label = 'Selesai';
              }
              return '<span class="badge bg-light-' + badgeClass + ' text-' + badgeClass + ' fw-semibold">' + label + '</span>';
            }
          },
          { 
            data: 'overdue', 
            name: 'overdue',
            render: function(data, type, row) {
              if (data === 'YA') {
                return '<span class="badge bg-danger text-white fw-bold">YA (LEWAT SLA)</span>';
              }
              return '—';
            }
          },
        ],
        order: [[2, 'asc']], // SLA terdekat dulu
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
