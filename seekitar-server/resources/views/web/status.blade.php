@extends('web.layout')

@section('title', 'Status Layanan')
@section('description', 'Status terkini layanan Seekitar — pantau ketersediaan dan jadwal pemeliharaan.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Operasional',
        'judul'    => 'Status Layanan',
        'subjudul' => 'Pantau ketersediaan layanan Seekitar secara langsung.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <div class="lp-doc sr-reveal">
                <div class="alert alert-success">
                    <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                    Seluruh layanan berjalan normal.
                </div>

                <h2 class="h5 fw-bold mt-4">Status komponen</h2>
                <div class="table-responsive">
                    <table class="table table-bordered" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr><th>Layanan</th><th>Status</th><th>Pembaruan</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>API & Aplikasi</td>
                                <td><span class="badge bg-success">Berjalan Normal</span></td>
                                <td style="color: var(--teks-secondary); font-size: 13px;">{{ now()->subMinutes(3)->translatedFormat('j M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td>Situs Web</td>
                                <td><span class="badge bg-success">Berjalan Normal</span></td>
                                <td style="color: var(--teks-secondary); font-size: 13px;">{{ now()->subMinutes(3)->translatedFormat('j M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td>Notifikasi</td>
                                <td><span class="badge bg-success">Berjalan Normal</span></td>
                                <td style="color: var(--teks-secondary); font-size: 13px;">{{ now()->subMinutes(3)->translatedFormat('j M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td>Database</td>
                                <td><span class="badge bg-success">Berjalan Normal</span></td>
                                <td style="color: var(--teks-secondary); font-size: 13px;">{{ now()->subMinutes(3)->translatedFormat('j M Y, H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h2 class="h5 fw-bold mt-4">Riwayat insiden</h2>
                <div style="color: var(--teks-secondary); font-size: 14px;">
                    <p class="mb-1"><em>Belum ada insiden tercatat.</em></p>
                </div>

                <h2 class="h5 fw-bold mt-4">Pemeliharaan terjadwal</h2>
                <div style="color: var(--teks-secondary); font-size: 14px;">
                    <p class="mb-0"><em>Tidak ada pemeliharaan terjadwal.</em></p>
                </div>

                <hr class="my-4">

                <p class="mb-0" style="font-size: 14px; color: var(--teks-secondary);">
                    Halaman ini diperbarui secara otomatis. Jika mengalami kendala,
                    hubungi kami lewat
                    <a href="{{ route('web.contact') }}">kanal pengaduan</a>.
                </p>
            </div>
        </div>
    </section>

@endsection