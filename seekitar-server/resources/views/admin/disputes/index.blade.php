@extends('admin.layouts.admin')

@section('title', 'Laporan Masalah (Dispute) — Seekitar')

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
    #disputes-table tbody tr {
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
            <h5 class="card-title fw-semibold mb-0">Daftar Laporan Masalah / Sengketa Transaksi</h5>
            <div class="d-flex align-items-center gap-2">
              <!-- Contextual Actions -->
              <div id="table-actions" class="d-none">
                @can('manage-disputes')
                <a id="action-resolve" href="#" class="btn btn-outline-warning table-action-btn" title="Tangani Laporan">
                  <i class="ti ti-check" aria-hidden="true"></i>
                  <span>Selesaikan</span>
                </a>
                @endcan
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="disputes-table-toolbar" class="d-none">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div>
                <label for="filter-status" class="visually-hidden">Saring status</label>
                <select class="form-select js-select2" id="filter-status">
                  <option value="">Semua Status</option>
                  <option value="open">Terbuka (Open)</option>
                  <option value="resolved">Selesai (Resolved)</option>
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap search-table" id="disputes-table" style="width: 100%;">
              <thead>
                <tr>
                  <th style="display:none">ID</th>
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

      <!-- Modal Selesaikan Laporan -->
      <div class="modal fade" id="resolveModal" tabindex="-1" aria-labelledby="resolveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header border-bottom">
              <h5 class="modal-title fw-bold text-dark" id="resolveModalLabel">Detail Laporan</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
              <div class="row g-4">
                <div class="col-lg-7">
                  <div class="card border mb-3">
                    <div class="card-body p-4">
                      <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="ti ti-credit-card fs-5 text-primary" aria-hidden="true"></i>
                        <h6 class="fw-bold mb-0 text-dark">Pesanan Terkait</h6>
                      </div>
                      <table class="table table-sm table-borderless mb-0 fs-3">
                        <tbody>
                          <tr>
                            <td class="text-muted ps-0" style="width: 150px;">No Pesanan</td>
                            <td class="fw-bold text-dark" id="resolve-order-number">—</td>
                          </tr>
                          <tr>
                            <td class="text-muted ps-0">Total Pembayaran</td>
                            <td class="fw-bold text-primary" id="resolve-total">—</td>
                          </tr>
                          <tr>
                            <td class="text-muted ps-0">Status Pesanan</td>
                            <td class="text-dark" id="resolve-order-status">—</td>
                          </tr>
                          <tr>
                            <td class="text-muted ps-0">Pembeli</td>
                            <td class="text-dark" id="resolve-buyer">—</td>
                          </tr>
                          <tr>
                            <td class="text-muted ps-0">Toko Penjual</td>
                            <td class="text-dark" id="resolve-store">—</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="card border">
                    <div class="card-body p-4">
                      <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="ti ti-alert-triangle fs-5 text-danger" aria-hidden="true"></i>
                        <h6 class="fw-bold mb-0 text-dark">Detail Keluhan</h6>
                      </div>
                      <p class="mb-1 fs-3 text-muted">Alasan: <span class="fw-semibold text-dark" id="resolve-reason">—</span></p>
                      <div class="bg-light rounded p-3">
                        <span class="text-muted fs-2 d-block mb-1">Pesan Pengaduan:</span>
                        <p class="mb-0 text-dark fs-3" style="white-space: pre-wrap; line-height: 1.6;" id="resolve-description">—</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-lg-5">
                  <div class="card border mb-3">
                    <div class="card-body p-3">
                      <h6 class="fw-semibold mb-2 text-dark">Pelapor</h6>
                      <p class="mb-0 fs-3 text-muted" id="resolve-reporter">—</p>
                    </div>
                  </div>

                  <div class="card border mb-3">
                    <div class="card-body p-3">
                      <h6 class="fw-semibold mb-2 text-dark"><i class="ti ti-clock me-1" aria-hidden="true"></i> Tenggat Tanggapan (SLA)</h6>
                      <div id="resolve-sla">—</div>
                    </div>
                  </div>

                  <div id="resolve-form-wrap" class="card border">
                    <div class="card-body p-4">
                      <h6 class="fw-bold mb-3 text-dark">Mediasi & Keputusan Admin</h6>
                      <p class="text-muted fs-2">Admin berhak melepaskan dana ke penjual atau mengembalikannya ke pembeli.</p>

                      <form id="resolve-form" action="#" method="POST">
                        @csrf
                        <div class="mb-3">
                          <label for="resolution" class="form-label fw-semibold text-dark fs-3">Keputusan Akhir</label>
                          <select class="form-select js-select2" id="resolution" name="resolution" required>
                            <option value="">Pilih Keputusan</option>
                            <option value="selesai">Selesaikan (Teruskan Dana ke Penjual)</option>
                            <option value="dibatalkan">Batalkan (Kembalikan Dana ke Pembeli)</option>
                          </select>
                        </div>

                        <div class="mb-3">
                          <label for="resolution_note" class="form-label fw-semibold text-dark fs-3">Catatan / Alasan Keputusan</label>
                          <textarea class="form-control" id="resolution_note" name="resolution_note" rows="4" placeholder="Jelaskan alasan hukum / mediasi keputusan Anda..." required maxlength="2000"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-hover-shadow py-2 fw-semibold" onclick="return confirm('Apakah Anda yakin ingin memproses keputusan mediasi ini? Tindakan ini bersifat permanen dan tidak dapat diubah.');">
                          <i class="ti ti-circle-check fs-5 me-1" aria-hidden="true"></i> Kirim Keputusan
                        </button>
                      </form>
                    </div>
                  </div>

                  <div id="resolve-outcome-wrap" class="card border d-none">
                    <div class="card-body p-4">
                      <h6 class="fw-bold mb-3 text-success"><i class="ti ti-circle-check" aria-hidden="true"></i> Hasil Keputusan Mediasi</h6>
                      <table class="table table-sm table-borderless mb-0 fs-3">
                        <tbody>
                          <tr>
                            <td class="text-muted ps-0" style="width: 140px;">Ditangani Oleh</td>
                            <td class="fw-semibold text-dark" id="outcome-assignee">—</td>
                          </tr>
                          <tr>
                            <td class="text-muted ps-0">Tanggal Keputusan</td>
                            <td class="text-dark" id="outcome-resolved-at">—</td>
                          </tr>
                          <tr>
                            <td class="text-muted ps-0">Keputusan Akhir</td>
                            <td class="fw-bold text-dark" id="outcome-decision">—</td>
                          </tr>
                          <tr>
                            <td class="text-muted ps-0">Catatan Mediasi</td>
                            <td class="text-dark" style="white-space: pre-wrap;" id="outcome-note">—</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top justify-content-end">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
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
          { data: 'id', name: 'id', visible: false },
          { data: 'order_number', name: 'order.order_number',
            render: function(data, type, row) {
              return '<span class="fw-semibold text-dark">#' + data + '</span>';
            }
          },
          { data: 'reason', name: 'reason',
            render: function(data, type, row) {
              var labels = {
                'barang_tidak_sesuai': 'Barang Tidak Sesuai',
                'jasa_tidak_profesional': 'Jasa Tidak Selesai / Tidak Profesional',
                'penyedia_tidak_responsif': 'Penyedia Tidak Responsif',
                'pembeli_fiktif': 'Pembeli Fiktif / Tidak Bayar',
                'lainnya': 'Lainnya'
              };
              return labels[data] || data;
            }
          },
          { data: 'response_deadline', name: 'response_deadline' },
          { data: 'status', name: 'status',
            render: function(data, type, row) {
              if (data === 'open') return '<span class="badge bg-light-warning text-warning fw-semibold">Terbuka</span>';
              if (data === 'resolved') return '<span class="badge bg-light-success text-success fw-semibold">Selesai</span>';
              return data;
            }
          },
          { data: 'overdue', name: 'overdue',
            render: function(data, type, row) {
              return data === 'YA' ? '<span class="badge bg-danger text-white fw-bold">YA (LEWAT SLA)</span>' : '—';
            }
          },
        ],
        order: [[3, 'asc']],
        dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3'f<'.dt-filters'>>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3'<'d-flex align-items-center gap-2'l><'d-flex align-items-center gap-2'i><'d-flex align-items-center gap-2'p>>",
        initComplete: function () {
          var $slot = $('#disputes-table_wrapper').find('.dt-filters');
          $('#disputes-table-toolbar').children().appendTo($slot);
          $('#disputes-table-toolbar').remove();
          $slot.filter(':empty').remove();
        },
        drawCallback: function(settings) {
          $('#table-actions').addClass('d-none');
          $('#disputes-table tbody tr').removeClass('row-selected');
          selectedRow = null;
        }
      });

      var selectedRow = null;

      $('#disputes-table tbody').on('click', 'tr', function () {
        var rowData = table.row(this).data();
        
        if (selectedRow && selectedRow.id === rowData.id) {
          $(this).removeClass('row-selected');
          $('#table-actions').addClass('d-none');
          selectedRow = null;
        } else {
          if (selectedRow) {
            $('#disputes-table tbody tr').removeClass('row-selected');
          }
          
          $(this).addClass('row-selected');
          selectedRow = rowData;
          
          $('#table-actions').removeClass('d-none');
        }
      });

      $('#filter-status').change(function(){
        table.draw();
      });

      function openDisputeModal(id, mode) {
        $.ajax({
          url: "{{ url('admin/disputes') }}/" + id + "/info",
          method: 'GET',
          success: function (d) {
            var title = mode === 'resolve' ? 'Selesaikan' : 'Detail';
            $('#resolveModal .modal-title').text(title + ' Laporan #' + d.order.order_number);
            $('#resolve-order-number').text('#' + d.order.order_number);
            $('#resolve-total').text(d.order.total_amount);
            $('#resolve-order-status').text(d.order.status);
            $('#resolve-buyer').text(d.buyer ? (d.buyer.name + ' (' + d.buyer.phone + ')') : '—');
            $('#resolve-store').text(d.store ? d.store.name : '—');
            $('#resolve-reason').text(d.reason);
            $('#resolve-description').text(d.description || 'Tidak ada deskripsi tambahan.');
            $('#resolve-reporter').text(d.reporter ? (d.reporter.name + ' (' + d.reporter.phone + ')') : '—');

            var sla = d.deadline ? ('Batas SLA: ' + d.deadline) : '—';
            var slaBadge = '';
            if (d.deadline_iso) {
              if (d.deadline_past) {
                slaBadge = '<span class="badge bg-danger text-white fw-bold fs-2">SLA TERLAMPAUI (Overdue)</span>';
              } else {
                var sisa = Math.max(0, new Date(d.deadline_iso) - Date.now());
                var hari = Math.floor(sisa / 86400000);
                var jam = Math.floor((sisa % 86400000) / 3600000);
                var menit = Math.floor((sisa % 3600000) / 60000);
                var teksSisa = (hari > 0 ? hari + ' hari ' : '') + jam + ' jam ' + menit + ' menit';
                slaBadge = '<span class="badge bg-success text-white fw-bold fs-2">Sisa: ' + teksSisa + '</span>';
              }
            }
            $('#resolve-sla').html(sla + ' ' + slaBadge);

            if (d.status === 'open') {
              $('#resolve-form-wrap').removeClass('d-none');
              $('#resolve-outcome-wrap').addClass('d-none');
            } else {
              $('#resolve-form-wrap').addClass('d-none');
              $('#resolve-outcome-wrap').removeClass('d-none');
              $('#outcome-assignee').text(d.outcome.assignee);
              $('#outcome-resolved-at').text(d.outcome.resolved_at);
              $('#outcome-decision').text(d.outcome.decision);
              $('#outcome-note').text(d.outcome.note);
            }

            $('#resolve-form').attr('action', "{{ url('admin/disputes') }}/" + d.id + "/resolve");
            $('#resolveModal').modal('show');

            if (mode === 'resolve') {
              $('#resolve-form-wrap').get(0)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          },
          error: function () {
            alert('Gagal memuat data laporan. Coba lagi.');
          }
        });
      }

      $('#action-resolve').on('click', function (e) {
        e.preventDefault();
        if (selectedRow) {
          openDisputeModal(selectedRow.id, selectedRow.status === 'open' ? 'resolve' : 'detail');
        }
      });
    });
  </script>
@endpush
