@extends('admin.layouts.admin')

@section('title', 'Gateway WhatsApp — Seekitar')

@push('styles')
  <style>
    .wa-qr-box {
      width: 260px;
      height: 260px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 16px;
      background: #f1f5f2;
      border: 1px dashed #c9d4cd;
    }
    .wa-qr-box img {
      width: 240px;
      height: 240px;
      border-radius: 8px;
    }
    .wa-status-dot {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin-right: 6px;
      vertical-align: middle;
    }
    .wa-status-dot.online { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.2); }
    .wa-status-dot.offline { background: #f87171; box-shadow: 0 0 0 3px rgba(248,113,113,.2); }
  </style>
@endpush

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
              <h4 class="fw-bold mb-1">Gateway WhatsApp</h4>
              <p class="text-muted mb-0 fs-3">Kelola koneksi WhatsApp untuk pengiriman OTP & notifikasi.</p>
            </div>
            <div>
              <span class="badge bg-light-primary text-primary fs-3 px-3 py-2">
                <i class="ti ti-brand-whatsapp me-1" aria-hidden="true"></i> Driver: {{ ucfirst($driver) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (! $isBaileys)
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 text-center py-5">
            <i class="ti ti-brand-whatsapp fs-1 text-primary" aria-hidden="true"></i>
            <h5 class="fw-bold mt-3">Fitur ini memerlukan driver <code>baileys</code></h5>
            <p class="text-muted fs-3 mb-0">
              Set <code>WHATSAPP_DRIVER=baileys</code> di <code>.env</code>, lalu jalankan service Node:
              <code>cd seekitar-server/whatsapp-gateway && npm start</code>.
              Petunjuk lengkap: <code>seekitar-server/whatsapp-gateway/README.md</code>.
            </p>
          </div>
        </div>
      </div>
    </div>
  @else
    <div class="row">
      <!-- Status -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <h6 class="text-muted mb-3 fs-3 fw-semibold">Status Koneksi</h6>
            <div class="d-flex align-items-center gap-2 mb-3">
              <span class="wa-status-dot {{ $status['online'] ? 'online' : 'offline' }}" id="wa-dot"></span>
              <h3 class="fw-bold mb-0" id="wa-status-text">{{ $status['online'] ? 'Online' : 'Offline' }}</h3>
            </div>
            <table class="table table-borderless mb-3 fs-3">
              <tbody>
                <tr>
                  <td class="text-muted">Nomor tersambung</td>
                  <td class="text-end fw-semibold" id="wa-phone">{{ $status['phone'] ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Terakhir tersambung</td>
                  <td class="text-end fw-semibold" id="wa-last">{{ $status['last_connected_at'] ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
            <div class="d-flex gap-2">
              <button class="btn btn-light d-inline-flex align-items-center gap-1" id="wa-refresh" type="button">
                <i class="ti ti-refresh" aria-hidden="true"></i> Muat Ulang
              </button>
              <button class="btn btn-outline-danger d-inline-flex align-items-center gap-1" id="wa-logout" type="button">
                <i class="ti ti-plug-connected-x" aria-hidden="true"></i> Cabut Sesi
              </button>
            </div>
          </div>
        </div>

        <!-- Uji kirim -->
        <div class="card border-0 shadow-sm mt-3">
          <div class="card-body p-4">
            <h6 class="text-muted mb-3 fs-3 fw-semibold">Uji Kirim Pesan</h6>
            <form id="wa-send-form">
              <div class="mb-3">
                <label class="form-label">Nomor tujuan</label>
                <input type="text" class="form-control" id="wa-send-phone" placeholder="6281234567890" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Pesan</label>
                <textarea class="form-control" id="wa-send-text" rows="2" placeholder="Halo, ini pesan uji dari Seekitar." required></textarea>
              </div>
              <button class="btn btn-primary d-inline-flex align-items-center gap-1" id="wa-send-btn" type="submit">
                <i class="ti ti-send" aria-hidden="true"></i> Kirim
              </button>
            </form>
            <div id="wa-send-result" class="mt-2 fs-2"></div>
          </div>
        </div>
      </div>

      <!-- QR -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4 text-center">
            <h6 class="text-muted mb-3 fs-3 fw-semibold">Scan QR WhatsApp</h6>
            <div id="wa-qr-online" class="{{ $status['online'] ? '' : 'd-none' }} py-5">
              <i class="ti ti-circle-check fs-1 text-success" aria-hidden="true"></i>
              <h5 class="fw-bold mt-2">WhatsApp sudah tersambung</h5>
              <p class="text-muted fs-3 mb-0">OTP & pesan akan terkirim lewat nomor ini.</p>
            </div>
            <div id="wa-qr-box" class="wa-qr-box {{ $status['online'] ? 'd-none' : '' }}">
              <p class="text-muted fs-3 px-3 text-center mb-0" id="wa-qr-empty">
                <i class="ti ti-qrcode fs-1 d-block mb-2 text-muted" aria-hidden="true"></i>
                Menunggu QR…
              </p>
            </div>
            <ol class="text-start text-muted fs-2 mt-4 mb-0 mx-auto" style="max-width: 420px;">
              <li>Buka <b>WhatsApp</b> di ponsel → <b>Perangkat Tertaut</b>.</li>
              <li>Ketuk <b>Tautkan Perangkat</b> dan arahkan kamera ke QR di atas.</li>
              <li>Setelah tersambung, status berubah menjadi <b>Online</b>.</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
  @if ($isBaileys)
  <script src="{{ asset('vendor/mordenize/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
  <script>
    (function () {
      'use strict';

      const statusUrl   = @json(route('admin.whatsapp.status'));
      const qrUrl       = @json(route('admin.whatsapp.qr'));
      const logoutUrl   = @json(route('admin.whatsapp.logout'));
      const sendUrl     = @json(route('admin.whatsapp.send-test'));
      const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      const dot      = document.getElementById('wa-dot');
      const statusEl = document.getElementById('wa-status-text');
      const phoneEl  = document.getElementById('wa-phone');
      const lastEl   = document.getElementById('wa-last');
      const onlineBox = document.getElementById('wa-qr-online');
      const qrBox    = document.getElementById('wa-qr-box');
      const qrEmpty  = document.getElementById('wa-qr-empty');
      let qrPolling  = false;

      function fmtLast(v) {
        if (!v) return '—';
        try {
          const d = new Date(v);
          return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
        } catch (_) { return v; }
      }

      function renderStatus(data) {
        const online = !!data.online;
        dot.className = 'wa-status-dot ' + (online ? 'online' : 'offline');
        statusEl.textContent = online ? 'Online' : 'Offline';
        phoneEl.textContent = data.phone || '—';
        lastEl.textContent = fmtLast(data.last_connected_at);
        onlineBox.classList.toggle('d-none', !online);
        qrBox.classList.toggle('d-none', online);
      }

      async function refreshStatus() {
        try {
          const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
          const json = await res.json();
          if (json.success) renderStatus(json.data);
        } catch (_) { /* gateway mati — biarkan state lama */ }
      }

      async function pollQr() {
        if (qrPolling) return;
        qrPolling = true;
        try {
          const res = await fetch(qrUrl, { headers: { 'Accept': 'application/json' } });
          const json = await res.json();
          if (!json.success) return;
          const data = json.data;
          if (data.online) {
            renderStatus({ online: true });
            qrBox.innerHTML = '';
            qrEmpty = document.getElementById('wa-qr-empty');
            return;
          }
          if (data.qr) {
            qrBox.innerHTML = '<img src="' + data.qr + '" alt="QR Code WhatsApp" width="240" height="240">';
            qrEmpty = null;
          }
        } catch (_) {}
        finally { qrPolling = false; }
      }

      // Polling: status tiap 5s; QR tiap 3s saat belum online.
      setInterval(refreshStatus, 5000);
      setInterval(() => {
        const online = statusEl.textContent === 'Online';
        if (!online) pollQr();
      }, 3000);

      document.getElementById('wa-refresh').addEventListener('click', () => { refreshStatus(); pollQr(); });

      document.getElementById('wa-logout').addEventListener('click', () => {
        Swal.fire({
          title: 'Cabut sesi WhatsApp?',
          text: 'Nomor akan terputus dan QR baru diminta. OTP tidak terkirim sampai scan ulang.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          confirmButtonText: 'Ya, cabut',
          cancelButtonText: 'Batal',
        }).then(async (result) => {
          if (!result.isConfirmed) return;
          const res = await fetch(logoutUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          });
          const json = await res.json();
          if (json.success) {
            Swal.fire({ title: 'Sesi dicabut', text: json.message, icon: 'success', timer: 2500, showConfirmButton: false });
            renderStatus({ online: false });
            qrBox.innerHTML = '<p class="text-muted fs-3 px-3 text-center mb-0">Menunggu QR…</p>';
            setTimeout(pollQr, 1500);
          } else {
            Swal.fire({ title: 'Gagal', text: json.message || 'Terjadi kesalahan.', icon: 'error' });
          }
        });
      });

      document.getElementById('wa-send-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('wa-send-btn');
        const result = document.getElementById('wa-send-result');
        btn.disabled = true;
        result.innerHTML = '<span class="text-muted">Mengirim…</span>';
        try {
          const res = await fetch(sendUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
              phone: document.getElementById('wa-send-phone').value.trim(),
              text: document.getElementById('wa-send-text').value.trim(),
            }),
          });
          const json = await res.json();
          result.innerHTML = json.success
            ? '<span class="text-success">✓ ' + json.message + '</span>'
            : '<span class="text-danger">✗ ' + (json.message || 'Gagal') + '</span>';
        } catch (_) {
          result.innerHTML = '<span class="text-danger">✗ Gagal terhubung ke gateway.</span>';
        } finally {
          btn.disabled = false;
        }
      });

      refreshStatus();
      pollQr();
    })();
  </script>
  @endif
@endpush
