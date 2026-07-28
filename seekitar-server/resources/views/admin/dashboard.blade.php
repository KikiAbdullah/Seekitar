@extends('admin.layout')
@section('title', 'Dasbor')

@section('content')

    <div class="row">
        <div class="col-lg-8 d-flex align-items-stretch">
            <div class="card w-100 bg-light-primary overflow-hidden shadow-none">
                <div class="card-body position-relative">
                    <div class="row">
                        <div class="col-sm-7">
                            <div class="d-flex align-items-center mb-7">
                                <span class="admin-avatar me-6" aria-hidden="true">
                                    {{ Str::upper(Str::substr(auth()->user()?->name ?? '?', 0, 1)) }}
                                </span>
                                <h5 class="fw-semibold mb-0 fs-5">
                                    Selamat datang, {{ auth()->user()?->name }}
                                </h5>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="border-end pe-4 border-muted border-opacity-10">
                                    <h3 class="mb-1 fw-semibold fs-8">{{ \App\Support\Angka::bulat($sorotan['nilai']) }}</h3>
                                    <p class="mb-0 text-dark">{{ $sorotan['label'] }}</p>
                                </div>
                                <div class="ps-4">
                                    <h3 class="mb-1 fw-semibold fs-8">{{ now()->translatedFormat('d M') }}</h3>
                                    <p class="mb-0 text-dark">{{ config('seekitar.regency') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="welcome-bg-img text-end">
                                <img src="{{ asset('vendor/modernize/images/backgrounds/welcome-bg2.png') }}"
                                     alt="" class="img-fluid" width="282" height="196">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-flex align-items-stretch">
            <div class="card w-100">
                <div class="card-body">
                    <div class="mb-4">
                        <h5 class="card-title fw-semibold">Peran &amp; Wilayah</h5>
                        <p class="card-subtitle mb-0">Hak akses akun Anda</p>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex">
                            <div class="p-6 bg-light-primary rounded-2 me-6 d-flex align-items-center justify-content-center">
                                <i class="ti ti-shield-check text-primary fs-6" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fs-4 fw-semibold">Peran</h6>
                                <p class="fs-3 mb-0 text-muted">
                                    {{ (auth()->user()?->getRoleNames() ?? collect())->join(', ') ?: '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex">
                            <div class="p-6 bg-light-secondary rounded-2 me-6 d-flex align-items-center justify-content-center">
                                <i class="ti ti-map-pin text-secondary fs-6" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fs-4 fw-semibold">{{ config('seekitar.regency') }}</h6>
                                <p class="fs-3 mb-0 text-muted">Kode BPS {{ config('seekitar.regency_code') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (count($antrian) > 0)
        <div class="row">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="mb-7">
                            <h5 class="card-title fw-semibold">Antrian Kerja</h5>
                            <p class="card-subtitle mb-0">Hal yang menunggu tindakan Anda</p>
                        </div>

                        <div class="row">
                            @foreach ($antrian as $item)
                                <div class="col-md-6 col-xl-3">
                                    <a href="{{ $item['url'] }}"
                                       class="d-flex align-items-center justify-content-between mb-4 text-decoration-none">
                                        <div class="d-flex">
                                            <div class="p-8 bg-light-{{ $item['tone'] }} rounded-2 d-flex align-items-center justify-content-center me-6">
                                                <i class="ti {{ $item['icon'] }} text-{{ $item['tone'] }} fs-6" aria-hidden="true"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fs-4 fw-semibold text-dark">
                                                    {{ \App\Support\Angka::bulat($item['value']) }}
                                                </h6>
                                                <p class="fs-3 mb-0 text-muted">{{ $item['label'] }}</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (count($stats) > 0)
        <div class="row">
            @foreach ($stats as $card)
                <div class="col-sm-6 col-xl-3 d-flex align-items-stretch">
                    <div class="card w-100">
                        <div class="card-body">
                            <div class="row align-items-start">
                                <div class="col-8">
                                    <h5 class="card-title mb-9 fw-semibold">{{ $card['label'] }}</h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <h4 class="fw-semibold mb-0 me-8">
                                            {{ \App\Support\Angka::bulat($card['value']) }}
                                        </h4>
                                    </div>
                                    <p class="fs-3 mb-0 text-muted">{{ $card['hint'] }}</p>
                                </div>
                                <div class="col-4">
                                    <div class="d-flex justify-content-end">
                                        <div class="p-6 bg-light-{{ $card['tone'] }} rounded-2 d-flex align-items-center justify-content-center">
                                            <i class="ti {{ $card['icon'] }} text-{{ $card['tone'] }} fs-6" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                </div>
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
        <div class="row">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="d-sm-flex d-block align-items-center justify-content-between mb-9">
                            <div class="mb-3 mb-sm-0">
                                <h5 class="card-title fw-semibold">Aktivitas Harian</h5>
                                <p class="card-subtitle mb-0">Permintaan &amp; pesanan baru per hari</p>
                            </div>
                            <div>
                                <select id="rentangGrafik" class="form-select js-select2" data-min-search="20"
                                        aria-label="Rentang grafik">
                                    @foreach ([7 => '7 hari terakhir', 14 => '14 hari terakhir', 30 => '30 hari terakhir'] as $hari => $label)
                                        <option value="{{ $hari }}" @selected($hari === $chartHari)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="position: relative; height: 300px;">
                            <canvas id="grafikAktivitas" role="img"
                                    aria-label="Grafik permintaan dan pesanan baru per hari"></canvas>
                        </div>

                        <p id="grafikStatus" class="fs-2 text-muted mb-0 mt-3" role="status"></p>
                    </div>
                </div>
            </div>
        </div>
    @endcanany

    <div class="row">

        @can('manage-requests')
            <div class="col-12 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="d-sm-flex d-block align-items-center justify-content-between mb-4">
                            <div>
                                <h5 class="card-title fw-semibold">Permintaan Terbaru</h5>
                                <p class="card-subtitle mb-0">5 permintaan yang baru masuk</p>
                            </div>
                            <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat semua
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead>
                                    <tr class="text-muted fw-semibold">
                                        <th scope="col" class="ps-0">Judul</th>
                                        <th scope="col">Pembeli</th>
                                        <th scope="col" class="text-center">Tawaran</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top">
                                @forelse ($ringkas['requests'] as $item)
                                    <tr>
                                        <td class="ps-0">
                                            <a href="{{ route('admin.requests.show', $item) }}"
                                               class="text-decoration-none">
                                                <h6 class="fw-semibold mb-1">{{ Str::limit($item->title, 34) }}</h6>
                                            </a>
                                            <p class="fs-2 mb-0 text-muted">{{ $item->created_at?->diffForHumans() }}</p>
                                        </td>
                                        <td><p class="mb-0 fs-3">{{ $item->user?->displayName() ?? '—' }}</p></td>
                                        <td class="text-center"><p class="mb-0 fs-3">{{ $item->offers_count }}</p></td>
                                        <td>
                                            <span class="badge fw-semibold py-1 bg-light-primary text-primary">
                                                {{ $item->status?->label() ?? '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">Belum ada permintaan.</td>
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
            <div class="col-12 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="d-sm-flex d-block align-items-center justify-content-between mb-4">
                            <div>
                                <h5 class="card-title fw-semibold">Penawaran Terbaru</h5>
                                <p class="card-subtitle mb-0">5 penawaran yang baru dikirim</p>
                            </div>
                            <a href="{{ route('admin.offers.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat semua
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead>
                                    <tr class="text-muted fw-semibold">
                                        <th scope="col" class="ps-0">Permintaan</th>
                                        <th scope="col">Toko</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top">
                                @forelse ($ringkas['offers'] as $offer)
                                    <tr>
                                        <td class="ps-0">
                                            <h6 class="fw-semibold mb-1">
                                                {{ Str::limit($offer->request?->title ?? '—', 28) }}
                                            </h6>
                                            <p class="fs-2 mb-0 text-muted">{{ $offer->created_at?->diffForHumans() }}</p>
                                        </td>
                                        <td><p class="mb-0 fs-3">{{ Str::limit($offer->store?->name ?? '—', 20) }}</p></td>
                                        <td>
                                            <p class="fs-3 text-dark mb-0">
                                                {{ \App\Support\Angka::rupiah((int) $offer->price + (int) $offer->additional_cost) }}
                                            </p>
                                        </td>
                                        <td>
                                            <span class="badge fw-semibold py-1 bg-light-warning text-warning">
                                                {{ $offer->status?->label() ?? '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">Belum ada penawaran.</td>
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
                <i class="ti ti-info-circle fs-9 text-info d-block mb-3" aria-hidden="true"></i>
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
                pointRadius: 3,
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
                                labels: { usePointStyle: true, boxWidth: 8, padding: 16 },
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                grid: { borderDash: [4, 4] },
                            },
                            x: { grid: { display: false } },
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
