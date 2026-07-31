@extends('admin.layouts.admin')

@section('title', 'Manajemen Pengguna — Seekitar')

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
    #users-table tbody tr {
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
              <h4 class="card-title">Filter Pengguna</h4>
              <p class="card-subtitle mb-0">Saring pengguna berdasarkan status akun mereka.</p>
            </div>
            <div class="mt-3 mt-md-0">
              @can('manage-users')
                <a href="{{ route('admin.users.export') }}" id="export-csv-btn" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                  <i class="ti ti-download fs-4"></i> Ekspor CSV
                </a>
              @endcan
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-3">
              <label for="filter-status" class="form-label">Status Akun</label>
              <select class="form-select" id="filter-status">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\UserStatus::cases() as $status)
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
            <h5 class="card-title fw-semibold mb-0">Daftar Pengguna</h5>
            <!-- Contextual Actions -->
            <div id="table-actions" class="d-none">
              <a id="action-show" href="#" class="btn btn-outline-info table-action-btn" title="Lihat Detail">
                <i class="ti ti-eye"></i>
                <span>Detail</span>
              </a>
              @can('manage-users')
              <a id="action-edit" href="#" class="btn btn-outline-warning table-action-btn" title="Edit Pengguna">
                <i class="ti ti-pencil"></i>
                <span>Edit</span>
              </a>
              <a id="action-block" href="#" class="btn btn-outline-danger table-action-btn" title="Blokir/Buka Blokir">
                <i class="ti ti-user-off"></i>
                <span>Blokir</span>
              </a>
              @endcan
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-sm table-bordered align-middle text-nowrap table-hover" id="users-table" style="width: 100%;">
            <thead>
              <tr>
                <th>Nama Pengguna</th>
                <th>Nomor HP</th>
                <th>Email</th>
                <th>Alamat Domisili</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Tanggal Terdaftar</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail Pengguna -->
  <div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detail Pengguna</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="user-detail-content">Memuat...</div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('vendor/mordenize/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
  <script>
    $(function () {
      var table = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('admin.users.data') }}",
          data: function (d) {
            d.status = $('#filter-status').val();
          }
        },
        columns: [
          // Kolom `id` ditambahkan tapi tidak ditampilkan (visible: false).
          // Ini penting untuk mengambil ID baris saat diseleksi.
          { data: 'id', name: 'id', visible: false },
          { data: 'name', name: 'name' },
          { data: 'phone', name: 'phone',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">' + data + '</span>';
            }
          },
          { data: 'email', name: 'email' },
          { data: 'address', name: 'address',
            render: function(data, type, row) {
              if (data === '—') return data;
              return '<div style="white-space: normal; max-width: 250px;">' + data + '</div>';
            }
          },
          { data: 'rating', name: 'rating_avg' },
          { data: 'status', name: 'status', orderable: false, searchable: false },
          { data: 'created_at', name: 'created_at' },
        ],
        // Urutkan berdasarkan kolom ke-8 (created_at), bukan ke-7 lagi
        // karena kolom ID (ke-0) baru saja ditambahkan.
        order: [[7, 'desc']],
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        },
        // Callback ini dipanggil setiap kali tabel selesai digambar ulang,
        // termasuk saat inisialisasi, paginasi, atau filtering.
        // Tujuannya adalah untuk memastikan tidak ada baris yang tampak
        // terseleksi saat data baru dimuat, menjaga UI tetap bersih.
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#users-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      // Event handler untuk klik pada baris tabel
      $('#users-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        // Jika baris yang sama diklik lagi, batalkan seleksi
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          // Hapus seleksi dari baris sebelumnya (jika ada)
          if (selectedRow) {
            $('#users-table tbody tr').removeClass('row-selected');
          }
          
          // Seleksi baris baru
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          // Update URL dan tampilkan tombol aksi
          var baseUrl = "{{ url('admin/users') }}";
          $('#action-show').attr('data-id', selectedRow.id).attr('href', '#');
          $('#action-show').off('click').on('click', function(e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            $('#userDetailModal').modal('show');
            $('#user-detail-content').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
            $.get("{{ url('admin/users') }}/" + id, function(data){
              $('#user-detail-content').html('<p><strong>Nama:</strong> ' + selectedRow.name + '</p><p><strong>HP:</strong> ' + selectedRow.phone + '</p><p><strong>Email:</strong> ' + (selectedRow.email || '-') + '</p>');
            });
          });
          @can('manage-users')
          $('#action-edit').attr('href', baseUrl + '/' + selectedRow.id + '/edit');
          $('#action-block').attr('data-id', selectedRow.id).attr('href', '#');
          $('#action-block').off('click').on('click', function(e) {
            e.preventDefault();
            alert('Blokir pengguna ID: ' + $(this).attr('data-id'));
          });
          @endcan
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#filter-status').change(function(){
        table.draw();
        
        var status = $(this).val();
        var exportUrl = "{{ route('admin.users.export') }}";
        if (status) {
          exportUrl += '?status=' + encodeURIComponent(status);
        }
        $('#export-csv-btn').attr('href', exportUrl);
      });
    });
  </script>
@endpush
