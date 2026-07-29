{{--
    Modal berkas & aksi verifikasi satu toko.

    Isinya disusun mengikuti SOP pemeriksaan admin:
      langkah 1 — alamat & identitas pemilik,
      langkah 2 — foto toko,
      langkah 3 — koordinat dibandingkan dengan Google Maps.
    Tombol "Verifikasi Toko" terkunci sampai ketiganya dicentang — persetujuan
    tanpa memeriksa tidak bisa terjadi "tanpa sengaja". Ditambah gerbang
    syarat pokok (pemilik terverifikasi + foto benar-benar terunggah) yang
    tidak bisa dibuka checklist, dan diperiksa ULANG oleh server.

    Penolakan memakai collapse DI DALAM modal, bukan modal kedua —
    Bootstrap tidak mendukung modal bersarang.

    $index  nomor baris (menyamakan id dengan target pemicu pada tabel)
    $store  model Store antrian (sudah memuat owner + latitude/longitude)
--}}

@php
    /*
     * Dua keputusan dibaca SEKALI lalu dipakai konsisten di seluruh modal —
     * badge, status SOP, dan kunci tombol tidak boleh berbeda pendapat.
     *
     * Foto dibaca dari KOLOM MENTAH: aksesor photo menjatuhkan nilai kosong
     * ke placeholder hiasan demi tampilan publik, sedangkan keputusan
     * verifikasi tidak boleh berpijak pada gambar yang tidak pernah
     * diunggah pemilik — bukti harus bukti, bukan dekorasi.
     */
    $fotoAsli             = $store->getRawOriginal('photo');
    $pemilikTerverifikasi = $store->owner?->canOpenStore() ?? false;
    $syaratPokokTerpenuhi = $pemilikTerverifikasi && filled($fotoAsli);
@endphp

<div class="modal fade text-start" id="verifikasiToko{{ $index }}" tabindex="-1"
     aria-labelledby="verifikasiToko{{ $index }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="verifikasiToko{{ $index }}Label">
                        Verifikasi {{ $store->name }}
                    </h5>
                    <span class="badge bg-warning-subtle text-warning">Menunggu persetujuan</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">

                {{-- LANGKAH 1 — Alamat & identitas pemilik. --}}
                <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">
                    LANGKAH 1 · ALAMAT &amp; PEMILIK
                </div>
                <dl class="row mb-3 fs-3">
                    <dt class="col-sm-3">Alamat</dt>
                    <dd class="col-sm-9 fw-semibold">{{ $store->address ?? '—' }}</dd>
                    <dt class="col-sm-3">Kabupaten</dt>
                    <dd class="col-sm-9">
                        {{ $store->regency }}
                        @if ($store->regency_code)
                            <span class="text-muted">· kode BPS {{ $store->regency_code }}</span>
                        @endif
                    </dd>
                    <dt class="col-sm-3">Pemilik</dt>
                    <dd class="col-sm-9">
                        {{ $store->owner?->name ?? '—' }}
                        <span class="font-monospace text-muted">{{ $store->owner?->phone }}</span>
                        @if ($pemilikTerverifikasi)
                            <span class="badge bg-success-subtle text-success ms-1">
                                <i class="ti ti-circle-check" aria-hidden="true"></i>
                                {{ $store->owner->verification_level->label() }}
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger ms-1">
                                KTP belum terverifikasi
                            </span>
                        @endif
                    </dd>
                    <dt class="col-sm-3">Jenis</dt>
                    <dd class="col-sm-9">
                        @foreach ($store->store_type ?? [] as $tipe)
                            <span class="badge text-bg-light border">{{ $tipe->label() }}</span>
                        @endforeach
                    </dd>
                    <dt class="col-sm-3">Radius layanan</dt>
                    <dd class="col-sm-9">{{ rtrim(rtrim(number_format((float) $store->service_radius_km, 2), '0'), '.') }} km</dd>
                    <dt class="col-sm-3">Rating</dt>
                    <dd class="col-sm-9">
                        @if ((int) $store->total_reviews > 0)
                            @include('admin.partials._stars', [
                                'rating' => $store->rating_avg,
                                'total'  => $store->total_reviews,
                            ])
                        @else
                            <span class="text-muted">Belum ada ulasan</span>
                        @endif
                    </dd>
                    @if ($store->npwp)
                        <dt class="col-sm-3">NPWP</dt>
                        <dd class="col-sm-9 font-monospace">{{ $store->npwp }}</dd>
                    @endif
                    <dt class="col-sm-3">Diajukan</dt>
                    <dd class="col-sm-9">
                        {{ $store->created_at->format('d M Y H:i') }}
                        <span class="text-muted">({{ $store->created_at->diffForHumans(null, true) }} menunggu)</span>
                    </dd>
                </dl>

                {{-- Kapabilitas toko — sumber badge di hasil pencarian. --}}
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if ($store->accepts_cod)
                        <span class="badge bg-success-subtle text-success">Bisa COD</span>
                    @endif
                    @if ($store->offers_delivery)
                        <span class="badge bg-info-subtle text-info">Bisa Diantar</span>
                    @endif
                    @if ($store->allows_pickup)
                        <span class="badge bg-primary-subtle text-primary">Ambil di Tempat</span>
                    @endif
                </div>

                {{-- LANGKAH 2 — Foto etalase: yang dinilai pembeli pertama kali. --}}
                <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">
                    LANGKAH 2 · FOTO TOKO
                </div>
                <div class="mb-3">
                    @if ($fotoAsli)
                        <a href="{{ $fotoAsli }}" target="_blank" rel="noopener"
                           title="Buka ukuran penuh di tab baru">
                            <img src="{{ $fotoAsli }}" alt="Foto toko {{ $store->name }}"
                                 class="img-fluid rounded border" style="max-height: 220px; object-fit: cover;">
                        </a>
                    @else
                        <div class="alert alert-warning py-2 fs-3 mb-0">
                            <i class="ti ti-photo-off" aria-hidden="true"></i>
                            Foto toko belum diunggah — syarat belum terpenuhi sehingga pengajuan
                            ini tidak bisa disetujui; tolak agar pemilik memperbaikinya.
                        </div>
                    @endif
                </div>

                {{-- LANGKAH 3 — Koordinat dibandingkan dengan Google Maps.
                     Dua tautan: titik persis (apakah ada bangunan/usaha di
                     sana?) dan pencarian nama (apakah toko ini memang tercatat
                     di Google?). Keduanya format resmi Google, tanpa API key. --}}
                <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">
                    LANGKAH 3 · KOORDINAT DI PETA
                </div>
                <div class="mb-3">
                    <div class="rounded border"
                         style="height: 240px; width: 100%;"
                         data-peta-toko
                         data-lat="{{ $store->latitude }}"
                         data-lng="{{ $store->longitude }}"
                         data-radius="{{ (float) $store->service_radius_km }}"
                         role="img" aria-label="Peta lokasi {{ $store->name }}"></div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <span class="font-monospace text-muted fs-2 me-auto">
                            {{ number_format((float) $store->latitude, 6) }}, {{ number_format((float) $store->longitude, 6) }}
                        </span>
                        <a class="btn btn-sm btn-outline-primary"
                           href="https://www.google.com/maps/search/?api=1&query={{ $store->latitude }},{{ $store->longitude }}"
                           target="_blank" rel="noopener">
                            <i class="ti ti-map-pin" aria-hidden="true"></i>
                            Cek Titik di Google Maps
                        </a>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode(trim($store->name.' '.($store->address ?? '').' '.$store->regency)) }}"
                           target="_blank" rel="noopener">
                            <i class="ti ti-search" aria-hidden="true"></i>
                            Cari Nama Toko
                        </a>
                    </div>
                </div>

                {{-- Konfirmasi SOP: ketiga centang wajib sebelum tombol
                     Verifikasi terbuka (lihat skrip di stores.blade). --}}
                <div class="border rounded p-3 bg-light" data-checklist>
                    <div class="text-muted fw-semibold mb-2" style="font-size: 11px;">KONFIRMASI PEMERIKSAAN</div>

                    {{-- Syarat paling hulu BUKAN centang: status pemilik adalah
                         fakta data, bukan penilaian admin. Ditampilkan read-only;
                         server memeriksanya ulang saat persetujuan, jadi tidak
                         bisa dilompat lewat DevTools. --}}
                    <div class="d-flex align-items-center gap-2 fs-3 mb-3">
                        @if ($pemilikTerverifikasi)
                            <i class="ti ti-circle-check text-success" aria-hidden="true"></i>
                            <span>Pemilik terverifikasi (nomor HP + KTP)</span>
                        @else
                            <i class="ti ti-alert-triangle text-danger" aria-hidden="true"></i>
                            <span class="text-danger fw-semibold">Pemilik belum terverifikasi — toko belum bisa disetujui</span>
                        @endif
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="cekAlamat{{ $index }}">
                        <label class="form-check-label fs-3" for="cekAlamat{{ $index }}">
                            Alamat &amp; kabupaten sesuai berkas pengajuan
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="cekFoto{{ $index }}">
                        <label class="form-check-label fs-3" for="cekFoto{{ $index }}">
                            Foto toko jelas dan benar memperlihatkan tempat usaha
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="cekKoordinat{{ $index }}">
                        <label class="form-check-label fs-3" for="cekKoordinat{{ $index }}">
                            Koordinat sudah dicocokkan dengan Google Maps dan sesuai alamat
                        </label>
                    </div>
                </div>

                {{-- Konsekuensi persetujuan harus terlihat SEBELUM tombol
                     ditekan: klik ini bukan hanya mengubah status toko,
                     melainkan juga lencana pemiliknya. --}}
                <div class="text-muted fs-3 mt-2">
                    <i class="ti ti-rosette" aria-hidden="true"></i>
                    Menyetujui juga menaikkan pemilik ke
                    <strong>Level 3 · Usaha Terverifikasi</strong> secara otomatis.
                </div>

                {{-- Form tolak (collapse — bukan modal di dalam modal). --}}
                <div class="collapse mt-3" id="tolakToko{{ $index }}">
                    <form method="POST" action="{{ route('admin.verifications.stores.reject', $store) }}"
                          class="border border-danger-subtle rounded p-3">
                        @csrf
                        <label for="tolakToko{{ $index }}Reason" class="form-label">Alasan penolakan</label>
                        <textarea id="tolakToko{{ $index }}Reason" name="reason" class="form-control"
                                  rows="3" required minlength="10" maxlength="500"
                                  placeholder="Contoh: Foto toko tidak jelas, lokasi pin tidak sesuai alamat."></textarea>
                        <div class="form-text">Pemilik dapat memperbaiki data lalu mengajukan ulang.</div>
                        <button type="submit" class="btn btn-danger btn-sm mt-2">Kirim penolakan</button>
                    </form>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="collapse" data-bs-target="#tolakToko{{ $index }}"
                            aria-expanded="false">
                        Tolak
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Tutup</button>
                    <form method="POST" action="{{ route('admin.verifications.stores.approve', $store) }}">
                        @csrf
                        @if ($syaratPokokTerpenuhi)
                            <button type="submit" class="btn btn-success" disabled
                                    data-tombol-verifikasi
                                    title="Centang ketiga konfirmasi pemeriksaan dulu">
                                <i class="ti ti-circle-check" aria-hidden="true"></i>
                                Verifikasi Toko
                            </button>
                        @else
                            {{-- Terkunci permanen: sengaja TANPA atribut
                                 data-tombol-verifikasi supaya skrip checklist
                                 tidak pernah bisa membukanya. --}}
                            <button type="button" class="btn btn-success" disabled
                                    title="{{ $pemilikTerverifikasi ? 'Foto toko belum diunggah pemilik' : 'Pemilik belum terverifikasi (nomor HP + KTP)' }}">
                                <i class="ti ti-lock" aria-hidden="true"></i>
                                Verifikasi Toko
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
