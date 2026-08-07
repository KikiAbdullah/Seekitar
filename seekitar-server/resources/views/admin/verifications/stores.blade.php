@extends('admin.layouts.admin')

@section('title', 'Antrian Verifikasi Toko — Seekitar')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/magnific-popup/dist/magnific-popup.css') }}">
  <style>
    .table-action-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .table-action-btn .ti {
      font-size: 1.125rem;
    }
    #stores-verifications-table tbody tr {
      cursor: pointer;
    }
    .row-selected,
    .row-selected td {
      background-color: #fcefe2 !important;
      font-weight: 600;
    }
    /* Lightbox foto harus DI ATAS modal verifikasi (Bootstrap modal = 1055). */
    .mfp-bg { z-index: 2070; }
    .mfp-wrap { z-index: 2071; }
    .mfp-content { z-index: 2072; }
  </style>
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card w-100 shadow-sm border-0 rounded-3">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <h5 class="card-title fw-bold text-dark mb-0">Antrian Verifikasi Toko</h5>
              <p class="card-subtitle mb-0">Daftar toko baru yang mengajukan verifikasi operasional. Hanya menampilkan toko yang identitas pemiliknya sudah terverifikasi.</p>
            </div>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              <button type="button" id="action-review" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                <i class="ti ti-search" aria-hidden="true"></i>
                <span>Detail</span>
              </button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table align-middle text-nowrap mb-0 search-table" id="stores-verifications-table" style="width: 100%;">
              <thead class="bg-light">
                <tr class="text-uppercase text-muted fs-2">
                  <th style="display:none">ID</th>
                  <th scope="col" class="px-4 py-3">Nama Toko</th>
                  <th scope="col" class="py-3">Pemilik</th>
                  <th scope="col" class="py-3">Tanggal Pengajuan</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  @foreach ($pending as $store)
    @include('admin.verifications._store_modal', ['store' => $store])
  @endforeach
@endsection

@push('scripts')
  <script src="{{ asset('vendor/mordenize/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/magnific-popup/dist/jquery.magnific-popup.min.js') }}"></script>
  <script>
    $(function () {
      var table = $('#stores-verifications-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.verifications.stores.data') }}",
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'store_info', name: 'name' },
          { data: 'owner_info', name: 'owner', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' }
        ],
        order: [[3, 'asc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#stores-verifications-table_wrapper').find('.dt-filters');
          $('#stores-verifications-toolbar').children().appendTo($slot);
          $('#stores-verifications-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#stores-verifications-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#stores-verifications-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();

        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#stores-verifications-table tbody tr').removeClass('row-selected');
          }

          $(this).addClass('row-selected');
          selectedRow = rowData;
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-review').on('click', function(e) {
        e.preventDefault();
        if (selectedRow) {
          $('#verifyStoreModal-' + selectedRow.id).modal('show');
        }
      });

      // Lightbox foto toko: muncul di atas modal verifikasi.
      $('.wa-lightbox').magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        closeBtnInside: true,
        mainClass: 'mfp-img-mobile',
        image: { verticalFit: true },
        zoom: { enabled: true, duration: 300 },
      });
    });
  </script>
@endpush
