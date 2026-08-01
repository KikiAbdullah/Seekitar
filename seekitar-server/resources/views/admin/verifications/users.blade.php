@extends('admin.layouts.admin')

@section('title', 'Antrian Verifikasi KTP — Seekitar')

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
    #users-verifications-table tbody tr {
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
      <div class="card w-100 shadow-sm border-0 rounded-3">
        <div class="card-body border-bottom">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <h5 class="card-title fw-bold text-dark mb-0">Antrian Verifikasi Identitas</h5>
              <p class="card-subtitle mb-0">Daftar pengguna yang mengajukan verifikasi identitas (KTP + Wajah), diurutkan dari pengajuan terlama sesuai SLA 24 jam.</p>
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
            <table class="table align-middle text-nowrap mb-0 search-table" id="users-verifications-table" style="width: 100%;">
              <thead class="bg-light">
                <tr class="text-uppercase text-muted fs-2">
                  <th style="display:none">ID</th>
                  <th scope="col" class="px-4 py-3">Pengguna</th>
                  <th scope="col" class="py-3">Nomor HP</th>
                  <th scope="col" class="py-3">Tanggal Pengajuan</th>
                  <th scope="col" class="py-3">Konteks</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  @foreach ($pending as $user)
    @include('admin.verifications._user_modal', ['user' => $user])
  @endforeach
@endsection

@push('scripts')
  <script src="{{ asset('vendor/mordenize/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('js/checklist-gate.js') }}"></script>
  <script>
    $(function () {
      var table = $('#users-verifications-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.verifications.users.data') }}",
        columns: [
          { data: 'id', name: 'id', visible: false },
          { data: 'user_info', name: 'name' },
          { data: 'phone', name: 'phone' },
          { data: 'submitted_at', name: 'ktp_submitted_at' },
          { data: 'context', name: 'rejected_at', orderable: false, searchable: false }
        ],
        order: [[3, 'asc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#users-verifications-table_wrapper').find('.dt-filters');
          $('#users-verifications-toolbar').children().appendTo($slot);
          $('#users-verifications-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#users-verifications-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#users-verifications-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();

        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#users-verifications-table tbody tr').removeClass('row-selected');
          }

          $(this).addClass('row-selected');
          selectedRow = rowData;
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#action-review').on('click', function(e) {
        e.preventDefault();
        if (selectedRow) {
          $('#verifyModal-' + selectedRow.id).modal('show');
        }
      });
    });
  </script>
@endpush
