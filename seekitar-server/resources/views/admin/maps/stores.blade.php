@extends('admin.layout')
@section('title', 'Peta Toko')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-2">Peta Toko</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Peta Toko</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end">
                    <i class="fa-regular fa-map fs-9 text-primary" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card w-100">
        <div class="card-body py-4">

            <div class="d-sm-flex d-block align-items-center justify-content-between mb-3">
                <div class="mb-3 mb-sm-0">
                    <h5 class="card-title fw-semibold mb-0">Sebaran toko terdaftar</h5>
                    <p class="card-subtitle fs-2 mb-0">
                        Seluruh toko {{ config('seekitar.regency') }} yang punya titik koordinat
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label for="filterStatusPeta" class="form-label mb-0 fs-2 text-muted">Verifikasi</label>
                    <select id="filterStatusPeta" class="form-select form-select-sm js-select2"
                            data-min-search="20" aria-label="Saring menurut status verifikasi">
                        <option value="">Semua status</option>
                        @foreach ($status as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="petaToko" class="admin-peta" role="application"
                 aria-label="Peta sebaran toko di {{ config('seekitar.regency') }}"></div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    @foreach ([
                        'verified' => ['success', 'Terverifikasi'],
                        'pending'  => ['warning', 'Menunggu'],
                        'rejected' => ['danger',  'Ditolak'],
                    ] as [$tone, $teks])
                        <span class="d-inline-flex align-items-center gap-2 fs-2 text-muted">
                            <span class="admin-peta-titik bg-{{ $tone }}" aria-hidden="true"></span>
                            {{ $teks }}
                        </span>
                    @endforeach
                </div>

                <p id="petaStatus" class="fs-2 text-muted mb-0" role="status"></p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
(function () {
    const wadah = document.getElementById('petaToko');
    const status = document.getElementById('petaStatus');
    if (!wadah || typeof L === 'undefined') return;

    const BATAS = L.latLngBounds(@js($batas['sw']), @js($batas['ne']));

    const peta = L.map(wadah, {
        center: @js($pusat),
        zoom: 10,
        minZoom: 9,
        maxBounds: BATAS,
        maxBoundsViscosity: 0.9,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(peta);

    peta.on('click', () => peta.scrollWheelZoom.enable());
    peta.on('mouseout', () => peta.scrollWheelZoom.disable());

    const WARNA = { verified: '#16A34A', pending: '#EA580C', rejected: '#DC2626' };

    let lapisan = L.layerGroup().addTo(peta);

    // Nama & alamat toko diisi pemilik toko, jadi tidak boleh dirangkai
    // langsung ke HTML popup. Leaflet TIDAK menyediakan pelolos HTML sendiri
    // (sudah diperiksa: L.Util.escapeHtml tidak ada), jadi isinya dipasang
    // lewat textContent pada elemen DOM — bukan innerHTML.
    const teks = (el, isi) => { el.textContent = isi ?? ''; return el; };

    function isiPopup(p) {
        const kotak = document.createElement('div');

        kotak.appendChild(teks(document.createElement('strong'), p.nama));

        if (p.alamat) {
            const alamat = teks(document.createElement('div'), p.alamat);
            alamat.className = 'text-muted fs-2';
            kotak.appendChild(alamat);
        }

        const lencana = teks(document.createElement('span'), p.label ?? '-');
        lencana.className = 'badge bg-light-primary text-primary mt-1';
        kotak.appendChild(lencana);

        if (!p.aktif) {
            const mati = teks(document.createElement('div'), 'Toko nonaktif');
            mati.className = 'text-danger fs-2 mt-1';
            kotak.appendChild(mati);
        }

        return kotak;
    }

    function gambar(fitur) {
        lapisan.clearLayers();

        if (!fitur.length) {
            status.textContent = 'Tidak ada toko dengan titik koordinat untuk filter ini.';
            return;
        }

        const titik = [];

        for (const f of fitur) {
            const [lng, lat] = f.geometry.coordinates;
            const p = f.properties;

            const penanda = L.circleMarker([lat, lng], {
                radius: 7,
                color: '#fff',
                weight: 2,
                fillColor: WARNA[p.status] ?? '#6B7280',
                fillOpacity: p.aktif ? 0.95 : 0.45,
            });

            penanda.bindPopup(isiPopup(p));

            penanda.addTo(lapisan);
            titik.push([lat, lng]);
        }

        peta.fitBounds(L.latLngBounds(titik).pad(0.15), { maxZoom: 13 });
        status.textContent = `${fitur.length} toko digambar.`;
    }

    async function muat(statusFilter) {
        status.textContent = 'Memuat titik toko…';

        try {
            const url = new URL(@js(route('admin.maps.stores.data')), window.location.origin);
            if (statusFilter) url.searchParams.set('status', statusFilter);

            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const { features } = await res.json();
            gambar(features ?? []);
        } catch (e) {
            status.textContent = 'Titik toko gagal dimuat. Muat ulang halaman untuk mencoba lagi.';
        }
    }

    $('#filterStatusPeta').on('change', function () { muat(this.value); });

    muat('');
})();
</script>
@endpush
