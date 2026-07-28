{{--
    Bintang 1–5 PECAHAN.

    Dua lapis karakter yang identik: baris "☆☆☆☆☆" di dasar, baris "★★★★★"
    di atasnya yang dipotong selebar (rating/5 × 100%). Jadi 4,3 tampil
    tepat 4,3 bintang — bukan dibulatkan ke 4 atau disembunyikan pecahannya.
    Karakter Unicode dipilih, bukan ikon font: tidak ada dependensi versi
    pustaka ikon dan selalu konsisten lebar antar-lapisnya.

    $rating  float 0–5 (rating_avg)
    $total   int|null — jumlah ulasan; null berarti disembunyikan
--}}
@php
    $persen = max(0, min(100, ((float) $rating / 5) * 100));
@endphp
<span class="d-inline-flex align-items-baseline gap-1 text-nowrap">
    <span class="position-relative d-inline-block" style="line-height: 1;"
          role="img" aria-label="Rating {{ number_format((float) $rating, 1, ',', '.') }} dari 5">
        <span class="text-warning" style="letter-spacing: 1px;">☆☆☆☆☆</span>
        <span class="position-absolute top-0 start-0 overflow-hidden text-nowrap"
              style="width: {{ $persen }}%;" aria-hidden="true">
            <span class="text-warning" style="letter-spacing: 1px;">★★★★★</span>
        </span>
    </span>
    <span class="fw-semibold">{{ number_format((float) $rating, 1, ',', '.') }}</span>
    @if (($total ?? null) !== null)
        <span class="text-muted fs-2">({{ $total }} ulasan)</span>
    @endif
</span>
