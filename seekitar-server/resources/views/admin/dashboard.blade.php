@extends('admin.layout')
@section('title', 'Dasbor')

@section('content')

    <div class="row">
        <div class="col-xl-5 d-flex align-items-stretch">
            <div class="card w-100 bg-light-primary overflow-hidden shadow-none">
                <div class="card-body position-relative py-4 admin-sambutan">
                    <p class="fs-2 text-muted mb-1">{{ now()->translatedFormat('l, d F Y') }}</p>
                    <h5 class="fw-semibold mb-1">Selamat datang,</h5>
                    <h5 class="fw-semibold mb-3 text-truncate">
                        {{ Str::before(auth()->user()?->name ?? '', ' ') }}
                    </h5>
                    <h3 class="fw-semibold fs-8 mb-0 lh-1">
                        {{ \App\Support\Angka::bulat($sorotan['nilai']) }}
                    </h3>
                    <p class="mb-0 fs-2 text-muted">{{ $sorotan['label'] }}</p>

                    <img src="{{ asset('vendor/mordenize/images/backgrounds/welcome-bg2.png') }}"
                         alt="" class="admin-sambutan-img" width="282" height="196">
                </div>
            </div>
        </div>

        @if (count($antrian) > 0)
            <div class="col-xl-7 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body py-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title fw-semibold mb-0">Antrian Kerja</h5>
                            <span class="fs-2 text-muted">Menunggu tindakan</span>
                        </div>

                        <div class="row g-3">
                            @foreach ($antrian as $item)
                                <div class="col-6 col-lg-3">
                                    <a href="{{ $item['url'] }}"
                                       class="admin-antrian d-block h-100 p-3 rounded-2 bg-light-{{ $item['tone'] }} text-decoration-none">
                                        <span class="d-flex align-items-center gap-2 mb-1">
                                            <i class="{{ $item['icon'] }} text-{{ $item['tone'] }} fs-5" aria-hidden="true"></i>
                                            <span class="fs-6 fw-semibold text-dark lh-1">
                                                {{ \App\Support\Angka::bulat($item['value']) }}
                                            </span>
                                        </span>
                                        <span class="d-block fs-2 text-muted lh-sm">{{ $item['label'] }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if (count($stats) > 0)
        <div class="row g-3 mb-3">
            @foreach ($stats as $card)
                <div class="col-6 col-lg-3 d-flex align-items-stretch">
                    <div class="card w-100 mb-0">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <span class="p-6 bg-light-{{ $card['tone'] }} rounded-2 d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="{{ $card['icon'] }} text-{{ $card['tone'] }} fs-6" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <h4 class="fw-semibold mb-0 lh-1">
                                    {{ \App\Support\Angka::bulat($card['value']) }}
                                </h4>
                                <p class="fs-3 fw-semibold text-dark mb-0 text-truncate">{{ $card['label'] }}</p>
                                <p class="fs-2 text-muted mb-0 text-truncate">{{ $card['hint'] }}</p>
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

    @canany(['manage-requests', 'manage-orders'])
        <div class="card w-100">
            <div class="card-body py-4">
                <div class="d-sm-flex d-block align-items-center justify-content-between mb-3">
                    <div class="mb-3 mb-sm-0">
                        <h5 class="card-title fw-semibold mb-0">Aktivitas Harian</h5>
                        <p class="card-subtitle fs-2 mb-0">Permintaan &amp; pesanan baru per hari</p>
                    </div>
                    <div>
                        <select id="rentangGrafik" class="form-select form-select-sm js-select2" data-min-search="20"
                                aria-label="Rentang grafik">
                            @foreach ([7 => '7 hari terakhir', 14 => '14 hari terakhir', 30 => '30 hari terakhir'] as $hari => $label)
                                <option value="{{ $hari }}" @selected($hari === $chartHari)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="admin-grafik">
                    <canvas id="grafikAktivitas" role="img"
                            aria-label="Grafik permintaan dan pesanan baru per hari"></canvas>
                </div>

                <p id="grafikStatus" class="fs-2 text-muted mb-0 mt-2" role="status"></p>
            </div>
        </div>
    @endcanany

    <div class="row">

        @can('manage-requests')
            <div class="col-xl-6 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body py-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title fw-semibold mb-0">Permintaan Terbaru</h5>
                            <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat semua
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0 admin-ringkas">
                                <thead>
                                    <tr class="text-muted fw-semibold">
                                        <th scope="col" class="ps-0">Permintaan</th>
                                        <th scope="col" class="text-center">Tawaran</th>
                                        <th scope="col" class="text-end pe-0">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top">
                                @forelse ($ringkas['requests'] as $item)
                                    <tr>
                                        <td class="ps-0">
                                            <a href="{{ route('admin.requests.show', $item) }}"
                                               class="text-decoration-none d-block">
                                                <h6 class="fw-semibold mb-0 text-truncate">{{ $item->title }}</h6>
                                            </a>
                                            <p class="fs-2 mb-0 text-muted text-truncate">
                                                {{ $item->user?->displayName() ?? '—' }} ·
                                                {{ $item->created_at?->diffForHumans(short: true) }}
                                            </p>
                                        </td>
                                        <td class="text-center"><span class="fs-3">{{ $item->offers_count }}</span></td>
                                        <td class="text-end pe-0">
                                            <span class="badge fw-semibold py-1 bg-light-primary text-primary">
                                                {{ $item->status?->label() ?? '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Belum ada permintaan.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('manage-offers')
            <div class="col-xl-6 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body py-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title fw-semibold mb-0">Penawaran Terbaru</h5>
                            <a href="{{ route('admin.offers.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat semua
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0 admin-ringkas">
                                <thead>
                                    <tr class="text-muted fw-semibold">
                                        <th scope="col" class="ps-0">Penawaran</th>
                                        <th scope="col" class="text-end">Total</th>
                                        <th scope="col" class="text-end pe-0">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top">
                                @forelse ($ringkas['offers'] as $offer)
                                    <tr>
                                        <td class="ps-0">
                                            <h6 class="fw-semibold mb-0 text-truncate">
                                                {{ $offer->request?->title ?? '—' }}
                                            </h6>
                                            <p class="fs-2 mb-0 text-muted text-truncate">
                                                {{ $offer->store?->name ?? '—' }} ·
                                                {{ $offer->created_at?->diffForHumans(short: true) }}
                                            </p>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-3 text-dark">
                                                {{ \App\Support\Angka::rupiah((int) $offer->price + (int) $offer->additional_cost) }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-0">
                                            <span class="badge fw-semibold py-1 bg-light-warning text-warning">
                                                {{ $offer->status?->label() ?? '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Belum ada penawaran.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    @if (count($stats) === 0 && count($antrian) === 0)
        <div class="card bg-light-info shadow-none">
            <div class="card-body text-center py-5">
                <i class="fa-regular fa-circle-question fs-9 text-info d-block mb-3" aria-hidden="true"></i>
                <h5 class="fw-semibold mb-1">Belum ada izin</h5>
                <p class="mb-0 text-muted">
                    Akun Anda belum diberi izin apa pun di panel ini.
                    Hubungi super-admin untuk mendapatkan akses.
                </p>
            </div>
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

    const endpoint = @js(route('admin.dashboard.chart'));
    let chart = null;

    const warnaLatar = (hex) => hex + '1A';

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
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4,
                pointBackgroundColor: d.color,
                borderWidth: 2,
            }));

            if (chart) {
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
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, boxWidth: 8, padding: 12 },
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, maxTicksLimit: 5 },
                                grid: { borderDash: [4, 4] },
                            },
                            x: {
                                grid: { display: false },
                                ticks: { maxRotation: 0, autoSkipPadding: 16 },
                            },
                        },
                    },
                });
            }

            const total = datasets.reduce((n, d) => n + d.data.reduce((a, b) => a + b, 0), 0);
            status.textContent = `${total} kejadian dalam ${days} hari terakhir.`;
        } catch (e) {
            status.textContent = 'Grafik gagal dimuat. Muat ulang halaman untuk mencoba lagi.';
        }
    }

    $('#rentangGrafik').on('change', function () { muat(this.value); });

    muat(@js($chartHari));
})();
</script>
@endpush
@endcanany
