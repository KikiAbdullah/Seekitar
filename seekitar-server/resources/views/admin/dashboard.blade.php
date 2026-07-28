@extends('admin.layout')
@section('title', 'Dasbor')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <h4 class="fw-semibold mb-1">Selamat datang, {{ auth()->user()?->name }}</h4>
                    <p class="mb-0 fs-3 text-muted">
                        {{ now()->translatedFormat('l, d F Y') }} &middot; {{ config('seekitar.regency') }}
                    </p>
                </div>
                <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                    @foreach (auth()->user()?->getRoleNames() ?? [] as $role)
                        <span class="badge bg-primary-subtle text-primary fs-2 px-3 py-2">{{ $role }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if (count($antrian) > 0)
        <div class="row">
            @foreach ($antrian as $item)
                <div class="col-6 col-lg-3">
                    <a href="{{ $item['url'] }}"
                       class="card border-0 zoom-in bg-light-{{ $item['tone'] }} shadow-none text-decoration-none">
                        <div class="card-body">
                            <div class="text-center">
                                <span class="round-40 d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $item['tone'] }} text-white mb-3">
                                    <i class="ti {{ $item['icon'] }} fs-6" aria-hidden="true"></i>
                                </span>
                                <h5 class="fw-semibold text-{{ $item['tone'] }} mb-1">
                                    {{ \App\Support\Angka::bulat($item['value']) }}
                                </h5>
                                <p class="fw-semibold fs-3 text-{{ $item['tone'] }} mb-0">{{ $item['label'] }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @if (count($stats) > 0)
        <div class="row">
            @foreach ($stats as $card)
                <div class="col-sm-6 col-xl-3">
                    <div class="card w-100">
                        <div class="card-body">
                            <div class="row align-items-start">
                                <div class="col-8">
                                    <h5 class="card-title mb-3 fw-semibold">{{ $card['label'] }}</h5>
                                    <h4 class="fw-semibold mb-3">{{ \App\Support\Angka::bulat($card['value']) }}</h4>
                                    <p class="fs-3 mb-0 text-muted">{{ $card['hint'] }}</p>
                                </div>
                                <div class="col-4">
                                    <div class="d-flex justify-content-end">
                                        <div class="text-white bg-{{ $card['tone'] }} rounded-circle p-6 d-flex align-items-center justify-content-center">
                                            <i class="ti {{ $card['icon'] }} fs-6" aria-hidden="true"></i>
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
            <div class="col-lg-6 d-flex align-items-strech">
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
            <div class="col-lg-6 d-flex align-items-strech">
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
