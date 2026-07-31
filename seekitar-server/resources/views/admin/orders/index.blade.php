@extends('admin.layouts.admin')

@section('title', 'Manajemen Pesanan — Seekitar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card mb-4 shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-3">
            <div>
              <h4 class="card-title">Filter Pesanan</h4>
              <p class="card-subtitle mb-0">Saring berdasarkan status, tipe, atau metode pengambilan.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
              @can('manage-orders')
                <a href="{{ route('admin.orders.export') }}" id="export-btn" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                  <i class="ti ti-download fs-4"></i> Ekspor CSV
                </a>
              @endcan
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\OrderStatus::cases() as $st)
                  <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label for="filter-type" class="form-label">Tipe Pesanan</label>
              <select class="form-select" id="filter-type">
                <option value="">Semua Tipe</option>
                @foreach (\App\Enums\OrderType::cases() as $type)
                  <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label for="filter-delivery" class="form-label">Metode Pengambilan</label>
              <select class="form-select" id="filter-delivery">
                <option value="">Semua Metode</option>
                @foreach (\App\Enums\DeliveryMethod::cases() as $dm)
                  <option value="{{ $dm->value }}">{{ $dm->label() }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card w-100 shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered align-middle text-nowrap" id="orders-table" style="width:100%;">
              <thead>
                <tr>
                  <th>No Pesanan</th>
                  <th>Pembeli</th>
                  <th>Toko</th>
                  <th>Metode</th>
                  <th>Qty</th>
                  <th>Total</th>
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
      var table = $('#orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.orders.data') }}",
          data: function (d) {
            d.status          = $('#filter-status').val();
            d.order_type      = $('#filter-type').val();
            d.delivery_method = $('#filter-delivery').val();
          }
        },
        columns: [
          { data: 'order_number',  name: 'order_number' },
          { data: 'buyer_name',    name: 'buyer.name' },
          { data: 'store_name',    name: 'store.name' },
          { data: 'delivery_method', name: 'delivery_method',
            render: function(d){ return d ? d.charAt(0).toUpperCase() + d.slice(1) : '—'; } },
          { data: 'quantity',      name: 'quantity' },
          { data: 'total_amount',  name: 'total_amount' },
          { data: 'status_label',  name: 'status', orderable: false, searchable: false },
          { data: 'created_at',    name: 'created_at' },
        ],
        order: [[7, 'desc']],
        language: { url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json" }
      });

      $('#filter-status, #filter-type, #filter-delivery').change(function(){
        table.draw();
        var params = new URLSearchParams({
          status: $('#filter-status').val(),
          order_type: $('#filter-type').val(),
          delivery_method: $('#filter-delivery').val()
        });
        $('#export-btn').attr('href', "{{ route('admin.orders.export') }}?" + params.toString());
      });
    });
  </script>
@endpush
