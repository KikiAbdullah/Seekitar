@extends('admin.layout')
@section('title', 'Dasbor')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Dasbor</li>
@endsection

@section('content')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 mb-1">Selamat datang, {{ auth()->user()?->name }}</h2>
            <p class="text-muted small mb-0">
                {{ now()->translatedFormat('l, d F Y') }} · {{ config('seekitar.regency') }}
            </p>
        </div>
    </div>

    {{-- ── Antrian kerja ────────────────────────────────────────────────
         Ditaruh PALING ATAS, sebelum KPI: dasbor pertama-tama harus menjawab
         "apa yang harus saya kerjakan", baru "bagaimana keadaannya". --}}
    @if (count($antrian) > 0)
        <div class="row g-2 mb-4">
            @foreach ($antrian as $item)
                <div class="col-6 col-lg-3">
                    <a href="{{ $item['url'] }}"
                       class="text-decoration-none d-block border rounded-3 bg-white p-3 h-100">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti {{ $item['icon'] }} text-{{ $item['tone'] }}" aria-hidden="true"></i>
                            <span class="fs-5 fw-bold text-{{ $item['value'] > 0 ? $item['tone'] : 'body' }}">
                                {{ \App\Support\Angka::bulat($item['value']) }}
                            </span>
                        </div>
                        <div class="small text-muted mt-1">{{ $item['label'] }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Kartu KPI ─────────────────────────────────────────────────────
         Isi $stats sudah disaring per permission di controller — query-nya
         pun tidak dijalankan bila admin tidak berhak, sehingga tidak ada
         angka yang bocor lewat halaman ini. --}}
    @if (count($stats) > 0)
        <div class="row g-3 mb-4">
            @foreach ($stats as $card)
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card p-3" data-tone="{{ $card['tone'] }}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="stat-label">{{ $card['label'] }}</div>
                                {{-- Angka::bulat(), BUKAN number_format() polos:
                                     tanpa argumen pemisah, 4812 tampil "4,812"
                                     yang dibaca sebagai bilangan desimal. --}}
                                <div class="stat-value">{{ \App\Support\Angka::bulat($card['value']) }}</div>
                                <div class="stat-hint">{{ $card['hint'] }}</div>
                            </div>
                            <div class="stat-icon">
                                <i class="ti {{ $card['icon'] }}" aria-hidden="true"></i>
                            </div>
                        </div>

                        @if ($card['url'])
                            <a href="{{ $card['url'] }}" class="stretched-link" aria-label="Buka {{ $card['label'] }}"></a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Grafik ────────────────────────────────────────────────────────
         Hanya dirender bila admin punya izin atas salah satu deretnya;
         kanvas kosong dengan sumbu tanpa data lebih membingungkan daripada
         tidak ada grafik sama sekali. --}}
    @canany(['manage-requests', 'manage-orders'])
        <div class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold">
                    <i class="ti ti-chart-line me-1" aria-hidden="true"></i> Aktivitas Harian
                </span>

                <div class="btn-group btn-group-sm" role="group" aria-label="Rentang grafik">
                    @foreach ([7 => '7 hari', 14 => '14 hari', 30 => '30 hari'] as $hari => $label)
                        <button type="button"
                                class="btn btn-outline-secondary chart-range {{ $hari === $chartHari ? 'active' : '' }}"
                                data-days="{{ $hari }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="card-body">
                {{-- Tinggi dipatok lewat wrapper, bukan atribut height pada
                     <canvas>: Chart.js responsive menimpa atribut itu dan
                     grafik ikut memanjang tanpa batas saat layar diperkecil. --}}
                <div style="position: relative; height: 280px;">
                    <canvas id="grafikAktivitas"
                            aria-label="Grafik permintaan dan pesanan baru per hari" role="img"></canvas>
                </div>

                <p id="grafikStatus" class="text-muted small mb-0 mt-2" role="status"></p>
            </div>
        </div>
    @endcanany

    {{-- ── Tabel ringkas (§9.1) ──────────────────────────────────────── --}}
    <div class="row g-3">

        @can('manage-requests')
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="ti ti-clipboard-list me-1" aria-hidden="true"></i> Permintaan Terbaru
                        </span>
                        <a href="{{ route('admin.requests.index') }}" class="small">Lihat semua</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Judul</th>
                                    <th scope="col">Pembeli</th>
                                    <th scope="col" class="text-center">Tawaran</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($ringkas['requests'] as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.requests.show', $item) }}">
                                            {{ Str::limit($item->title, 38) }}
                                        </a>
                                        <div class="text-muted" style="font-size: 12px;">
                                            {{ $item->created_at?->diffForHumans() }}
                                        </div>
                                    </td>
                                    {{-- Nama disingkat, sama seperti yang dilihat
                                         penyedia sebelum penawaran diterima
                                         (PRD §5.2.3). --}}
                                    <td>{{ $item->user?->displayName() ?? '—' }}</td>
                                    <td class="text-center">{{ $item->offers_count }}</td>
                                    <td>
                                        <span class="badge text-bg-light border">
                                            {{ $item->status?->label() ?? '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada permintaan.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endcan

        @can('manage-offers')
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="ti ti-discount-2 me-1" aria-hidden="true"></i> Penawaran Terbaru
                        </span>
                        <a href="{{ route('admin.offers.index') }}" class="small">Lihat semua</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Permintaan</th>
                                    <th scope="col">Toko</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($ringkas['offers'] as $offer)
                                <tr>
                                    <td>{{ Str::limit($offer->request?->title ?? '—', 30) }}</td>
                                    <td>{{ Str::limit($offer->store?->name ?? '—', 22) }}</td>
                                    {{-- Total, bukan price saja: yang mengikat
                                         pembeli adalah harga + ongkos
                                         (DATABASE.md §4.6). --}}
                                    <td class="text-nowrap">
                                        {{ \App\Support\Angka::rupiah((int) $offer->price + (int) $offer->additional_cost) }}
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light border">
                                            {{ $offer->status?->label() ?? '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada penawaran.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    {{-- Admin tanpa satu pun izin data: jangan tampilkan halaman kosong tanpa
         penjelasan — ia akan tampak seperti panel yang rusak. --}}
    @if (count($stats) === 0 && count($antrian) === 0)
        <div class="alert alert-info" role="alert">
            <i class="ti ti-info-circle me-1" aria-hidden="true"></i>
            Akun Anda belum diberi izin apa pun di panel ini. Hubungi super-admin
            untuk mendapatkan akses.
        </div>
    @endif
@endsection

@canany(['manage-requests', 'manage-orders'])
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const canvas = document.getElementById('grafikAktivitas');
    const status = document.getElementById('grafikStatus');
    if (!canvas || typeof Chart === 'undefined') return;

    /* URL disisipkan lewat direktif @@js, bukan perangkaian string: nilainya
       di-escape untuk konteks JavaScript.

       Perhatikan `@@js` bertanda at ganda di komentar ini. Blade memproses
       direktif DI DALAM komentar JavaScript juga — menulisnya sekali membuat
       Blade memanggil Js::from() tanpa argumen dan halaman mati dengan
       "Too few arguments to function Illuminate\Support\Js::from()".
       Sama persis dengan jebakan @@php di dalam komentar {{-- --}}. */
    const endpoint = @js(route('admin.dashboard.chart'));
    let chart = null;

    const warnaLatar = (hex) => hex + '1A';   // 10% alpha

    async function muat(days) {
        status.textContent = 'Memuat grafik…';

        try {
            const res = await fetch(`${endpoint}?days=${days}`, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const { labels, datasets } = await res.json();

            if (!datasets.length) {
                status.textContent = 'Tidak ada deret data yang bisa ditampilkan.';
                return;
            }

            const config = datasets.map(d => ({
                label: d.label,
                data: d.data,
                borderColor: d.color,
                backgroundColor: warnaLatar(d.color),
                fill: true,
                tension: 0.3,
                pointRadius: 2,
            }));

            if (chart) {
                // update() alih-alih membuat ulang: destroy/new pada tiap
                // pergantian rentang membocorkan listener resize.
                chart.data.labels = labels;
                chart.data.datasets = config;
                chart.update();
            } else {
                chart = new Chart(canvas, {
                    type: 'line',
                    data: { labels, datasets: config },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'bottom' } },
                        scales: {
                            // precision:0 — jumlah pesanan tidak punya pecahan,
                            // dan sumbu "2,5 pesanan" hanya membingungkan.
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            }

            const total = datasets.reduce((n, d) => n + d.data.reduce((a, b) => a + b, 0), 0);
            status.textContent = `${total} kejadian dalam ${days} hari terakhir.`;
        } catch (e) {
            // Dasbor tidak boleh tampak rusak hanya karena grafik gagal.
            status.textContent = 'Grafik gagal dimuat. Muat ulang halaman untuk mencoba lagi.';
        }
    }

    document.querySelectorAll('.chart-range').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.chart-range').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            muat(btn.dataset.days);
        });
    });

    muat(@js($chartHari));
})();
</script>
@endpush
@endcanany
