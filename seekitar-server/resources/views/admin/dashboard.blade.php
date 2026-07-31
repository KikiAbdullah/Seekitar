@extends('admin.layouts.admin')

@section('title', 'Dashboard Admin — Seekitar')

@section('content')
  <!-- Welcome Card & Quick Stats -->
  <div class="row">
    <div class="col-lg-8 d-flex align-items-stretch">
      <div class="card w-100 bg-light-info overflow-hidden border-0">
        <div class="card-body p-4">
          <h4 class="card-title mb-2">Selamat datang kembali, {{ Auth::user()->name }}!</h4>
          <p class="card-subtitle mb-4">Hari ini adalah {{ now()->translatedFormat('l, d F Y') }}.</p>
          <div class="d-flex align-items-center justify-content-between mt-3">
            <div>
              <h3 class="fw-semibold mb-1">{{ $sorotan['nilai'] }}</h3>
              <p class="text-muted mb-0 fs-3">{{ $sorotan['label'] }}</p>
            </div>
            <div class="d-none d-sm-block">
              <img src="{{ asset('vendor/mordenize/images/backgrounds/welcome-bg.svg') }}" alt="welcome" class="img-fluid" width="200">
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Urgent Action Queue -->
    <div class="col-lg-4 d-flex align-items-stretch">
      <div class="card w-100">
        <div class="card-body p-4">
          <h4 class="card-title mb-3">Antrian Tindakan</h4>
          <p class="card-subtitle mb-4">Hal-hal yang membutuhkan tindakan peninjauan Anda segera.</p>
          
          @if (empty($antrian))
            <div class="d-flex align-items-center justify-content-center py-4">
              <div class="text-center">
                <i class="ti ti-circle-check text-success fs-9 mb-2"></i>
                <p class="mb-0 fs-3">Semua tugas selesai!</p>
              </div>
            </div>
          @else
            <div class="d-flex flex-column gap-3">
              @foreach ($antrian as $item)
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
                  <div class="d-flex align-items-center gap-3">
                    <span class="rounded bg-{{ $item['tone'] }}-subtle text-{{ $item['tone'] }} p-2">
                      <i class="{{ $item['icon'] }} fs-5"></i>
                    </span>
                    <div>
                      <h6 class="mb-0 fw-semibold fs-3">{{ $item['label'] }}</h6>
                    </div>
                  </div>
                  <div class="text-end">
                    <a href="{{ $item['url'] }}" class="badge bg-{{ $item['tone'] }}-subtle text-{{ $item['tone'] }} fw-bold px-3 py-2 text-decoration-none">
                      {{ $item['value'] }} Tindakan
                    </a>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Cards Grid -->
  <div class="row">
    @foreach ($stats as $key => $card)
      <div class="col-sm-6 col-xl-3 d-flex align-items-stretch">
        <div class="card w-100 card-lift border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div>
                <h6 class="card-title mb-1 fs-3 text-muted">{{ $card['label'] }}</h6>
                <h3 class="fw-bold mb-0">{{ number_format($card['value']) }}</h3>
              </div>
              <span class="rounded-circle bg-{{ $card['tone'] }}-subtle text-{{ $card['tone'] }} p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="{{ $card['icon'] }} fs-5"></i>
              </span>
            </div>
            <div class="d-flex align-items-center justify-content-between fs-3">
              <span class="text-muted">{{ $card['hint'] }}</span>
              @if ($card['url'])
                <a href="{{ $card['url'] }}" class="text-primary text-decoration-none fw-semibold">Lihat Detail <i class="ti ti-arrow-right"></i></a>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <!-- Chart Area -->
  @if (Gate::allows('manage-requests') || Gate::allows('manage-orders'))
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="d-md-flex align-items-center justify-content-between mb-4">
              <div>
                <h4 class="card-title">Aktivitas Pasar Seekitar</h4>
                <p class="card-subtitle">Grafik permintaan baru dan pesanan baru ({{ $chartHari }} hari terakhir)</p>
              </div>
            </div>
            <div id="market-activity-chart"></div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- Recent Tables Section -->
  <div class="row">
    <!-- Recent Requests -->
    @if (Gate::allows('manage-requests') && isset($ringkas['requests']) && $ringkas['requests']->isNotEmpty())
      <div class="col-lg-6 d-flex align-items-stretch">
        <div class="card w-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h4 class="card-title">Permintaan Terbaru</h4>
              <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-light text-primary fw-semibold">Lihat Semua</a>
            </div>
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">Judul</th>
                    <th scope="col">Pembeli</th>
                    <th scope="col">Kategori</th>
                    <th scope="col" class="text-end">Penawaran</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($ringkas['requests'] as $req)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div>
                            <h6 class="fw-semibold mb-1 fs-3">{{ Str::limit($req->title, 30) }}</h6>
                            <span class="fs-2 text-muted">Dibuat {{ $req->created_at->diffForHumans() }}</span>
                          </div>
                        </div>
                      </td>
                      <td>
                        <p class="mb-0 fs-3">{{ $req->user?->name ?: 'Pengguna' }}</p>
                      </td>
                      <td>
                        <span class="badge bg-light-primary text-primary fw-semibold fs-2">{{ $req->category?->name ?: 'Umum' }}</span>
                      </td>
                      <td class="text-end">
                        <span class="badge bg-light-secondary text-secondary fw-bold">{{ $req->offers_count }}</span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @endif

    <!-- Recent Offers -->
    @if (Gate::allows('manage-offers') && isset($ringkas['offers']) && $ringkas['offers']->isNotEmpty())
      <div class="col-lg-6 d-flex align-items-stretch">
        <div class="card w-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h4 class="card-title">Penawaran Terbaru</h4>
              <a href="{{ route('admin.offers.index') }}" class="btn btn-sm btn-light text-primary fw-semibold">Lihat Semua</a>
            </div>
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">Toko Penawar</th>
                    <th scope="col">Permintaan</th>
                    <th scope="col">Nilai Penawaran</th>
                    <th scope="col" class="text-end">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($ringkas['offers'] as $off)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div>
                            <h6 class="fw-semibold mb-0 fs-3">{{ $off->store?->name ?: 'Toko' }}</h6>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="fs-3 text-muted">{{ Str::limit($off->request?->title ?: 'Permintaan', 25) }}</span>
                      </td>
                      <td>
                        <h6 class="fw-semibold mb-0 fs-3">Rp {{ number_format($off->price + $off->additional_cost) }}</h6>
                      </td>
                      <td class="text-end">
                        @php
                          $status_class = $off->status->value === 'accepted' ? 'success' : ($off->status->value === 'rejected' ? 'danger' : 'warning');
                          $status_label = $off->status->value === 'accepted' ? 'Diterima' : ($off->status->value === 'rejected' ? 'Ditolak' : 'Menunggu');
                        @endphp
                        <span class="badge bg-light-{{ $status_class }} text-{{ $status_class }} fw-semibold fs-2">{{ $status_label }}</span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
@endsection

@push('scripts')
  @if (Gate::allows('manage-requests') || Gate::allows('manage-orders'))
    <!-- Apex Charts JS -->
    <script src="{{ asset('vendor/mordenize/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script>
      $(function () {
        $.ajax({
          url: "{{ route('admin.dashboard.chart') }}",
          method: 'GET',
          data: { days: {{ $chartHari }} },
          success: function (res) {
            var options = {
              chart: {
                height: 350,
                type: 'area',
                fontFamily: "Plus Jakarta Sans', sans-serif",
                foreColor: '#adb5bd',
                toolbar: {
                  show: false
                }
              },
              dataLabels: {
                enabled: false
              },
              stroke: {
                curve: 'smooth',
                width: 2
              },
              grid: {
                borderColor: '#e5e5e5',
                strokeDashArray: 3
              },
              colors: res.datasets.map(function(d) { return d.color; }),
              series: res.datasets.map(function(d) {
                return {
                  name: d.label,
                  data: d.data
                };
              }),
              xaxis: {
                categories: res.labels,
                axisBorder: {
                  show: false
                },
                axisTicks: {
                  show: false
                }
              },
              tooltip: {
                theme: 'dark'
              }
            };

            var chart = new ApexCharts(document.querySelector("#market-activity-chart"), options);
            chart.render();
          }
        });
      });
    </script>
  @endif
@endpush
