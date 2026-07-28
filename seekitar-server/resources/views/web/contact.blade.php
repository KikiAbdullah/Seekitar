@extends('web.layout')

@section('title', 'Kontak & Pengaduan')
@section('description', 'Kanal pengaduan resmi Seekitar beserta tenggat tanggapannya.')

@section('content')
<div class="container py-5" style="max-width: 860px">
    <h1 class="h2 fw-bold mb-3">Kontak &amp; Pengaduan</h1>
    <p class="text-secondary mb-4">
        Setiap kanal punya tenggat tanggapan yang mengikat. Sebutkan nomor
        pesanan bila laporanmu terkait transaksi.
    </p>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr><th scope="col">Keperluan</th><th scope="col">Alamat</th><th scope="col">Tenggat</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pengaduan umum</td>
                    <td><a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a></td>
                    <td>2×24 jam</td>
                </tr>
                <tr>
                    <td>Pelaporan konten ilegal</td>
                    <td><a href="mailto:{{ config('seekitar.contacts.abuse') }}">{{ config('seekitar.contacts.abuse') }}</a></td>
                    <td>1×24 jam</td>
                </tr>
                <tr>
                    <td>Permintaan data pribadi (UU PDP)</td>
                    <td><a href="mailto:{{ config('seekitar.contacts.privacy') }}">{{ config('seekitar.contacts.privacy') }}</a></td>
                    <td>3×24 jam</td>
                </tr>
                <tr>
                    <td>Laporan celah keamanan</td>
                    <td><a href="mailto:{{ config('seekitar.contacts.security') }}">{{ config('seekitar.contacts.security') }}</a></td>
                    <td>1×24 jam</td>
                </tr>
                <tr>
                    <td>Sengketa transaksi</td>
                    <td>Lewat aplikasi: buka pesanan → Laporkan Masalah</td>
                    <td>1×24 jam</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="alert alert-warning mt-4">
        <strong>Sengketa transaksi sebaiknya dilaporkan lewat aplikasi</strong>,
        bukan email. Laporan dari aplikasi otomatis membekukan pesanan
        sehingga statusnya tidak bisa berubah sampai admin memutuskan.
    </div>

    <h2 class="h5 fw-bold mt-5">Penyelenggara</h2>
    <p class="mb-0">
        {{ config('seekitar.company.name') }}<br>
        {{ config('seekitar.company.address') }}
    </p>
</div>
@endsection
