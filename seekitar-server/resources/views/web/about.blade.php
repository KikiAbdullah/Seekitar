@extends('web.layout')

@section('title', 'Tentang Seekitar')
@section('description', 'Seekitar adalah marketplace hyperlocal dua arah yang dikunci dalam satu kabupaten.')

@section('content')
<div class="container py-5" style="max-width: 760px">
    <h1 class="h2 fw-bold mb-4">Tentang Seekitar</h1>

    <p class="lead">Yang kamu butuhkan, ada di sekitar.</p>

    <p>
        Seekitar adalah marketplace <strong>hyperlocal dua arah</strong> yang
        sengaja dibatasi pada satu wilayah kabupaten —
        {{ config('seekitar.regency') }}. Batasan itu bukan kekurangan,
        melainkan inti produknya: penjual dan pembeli cukup dekat untuk
        bertemu langsung, dan ongkos kirim tidak menghapus nilai transaksi.
    </p>

    <h2 class="h4 fw-bold mt-5 mb-3">Dua arah</h2>
    <p>
        Kebanyakan marketplace hanya satu arah: penjual memasang, pembeli
        mencari. Seekitar menambahkan arah sebaliknya — pembeli boleh
        <strong>memasang kebutuhan</strong>, lalu penyedia terdekat yang
        mengirim penawaran.
    </p>
    <p>
        Karena itu satu akun bisa menjadi keduanya. Pemilik warung yang
        menjual beras pagi ini bisa mencari tukang servis AC sore nanti,
        tanpa akun terpisah.
    </p>

    <h2 class="h4 fw-bold mt-5 mb-3">Kenapa satu kabupaten</h2>
    <p>
        Pencocokan dilakukan dua arah: toko harus berada dalam radius yang
        dipilih pembeli, <em>dan</em> pembeli harus berada dalam radius
        layanan toko. Warung dengan jangkauan 5 km tidak akan dibanjiri
        permintaan dari orang 12 km jauhnya.
    </p>

    <h2 class="h4 fw-bold mt-5 mb-3">Verifikasi bertingkat</h2>
    <p>
        Ada tiga tingkat: nomor terverifikasi, identitas terverifikasi (KTP),
        dan usaha terverifikasi. Membuka toko mensyaratkan tingkat kedua —
        agar setiap penjual punya jejak identitas yang bisa
        dipertanggungjawabkan.
    </p>
    <p class="text-secondary">
        Badge verifikasi tidak diperjualbelikan dan bukan jaminan mutlak atas
        kualitas barang atau jasa.
    </p>
</div>
@endsection
