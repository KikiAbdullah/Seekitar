{{--
    Modal berkas & aksi verifikasi SATU pengguna.

    Susunannya mengikuti SOP sekali duduk: foto (wajah ↔ KTP berdampingan),
    data berkas, domisili (alamat ↔ titik), lalu gerbang checklist sebelum
    tombol Setujui terbuka — menyetujui tanpa memeriksa tidak bisa terjadi
    "tanpa sengaja".

    Penolakan memakai collapse DI DALAM modal, bukan modal kedua —
    Bootstrap tidak mendukung modal bersarang.

    $index  nomor baris (menyamakan id dengan target pemicu pada tabel)
    $user   model User antrian (koordinatnya sudah ikut dimuat)
--}}

<div class="modal fade text-start" id="verifikasiUser{{ $index }}" tabindex="-1"
     aria-labelledby="verifikasiUser{{ $index }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="verifikasiUser{{ $index }}Label">
                        Verifikasi {{ $user->name }}
                    </h5>
                    <span class="badge text-bg-secondary">Menunggu persetujuan</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">

                {{-- LANGKAH 1 — Wajah ↔ KTP berdampingan supaya bisa
                     dibandingkan dalam satu pandang. --}}
                <div class="text-muted fw-semibold mb-2" style="font-size: 11px;">
                    LANGKAH 1 · SAMAKAN WAJAH DENGAN KTP
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">FOTO PROFIL</div>
                        {{-- avatar_url accessor selalu mengembalikan URL
                             (placeholder bila kosong). --}}
                        <img src="{{ $user->avatar_url }}" alt="Foto profil {{ $user->name }}"
                             class="img-fluid rounded border" style="max-height: 180px; object-fit: cover;"
                             loading="lazy" decoding="async">
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">FOTO WAJAH</div>
                        @if ($user->selfie_image)
                            <a href="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                               target="_blank" rel="noopener" title="Buka ukuran penuh di tab baru">
                                <img src="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                                     alt="Foto wajah {{ $user->name }}" class="img-fluid rounded border"
                                     style="max-height: 180px; object-fit: cover;">
                            </a>
                        @else
                            <div class="border rounded text-muted text-center py-4 small">Belum diunggah</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">FOTO KTP</div>
                        @if ($user->ktp_image)
                            <a href="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                               target="_blank" rel="noopener" title="Buka ukuran penuh di tab baru">
                                <img src="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                                     alt="KTP {{ $user->name }}" class="img-fluid rounded border"
                                     style="max-height: 180px; object-fit: cover;">
                            </a>
                        @else
                            <div class="border rounded text-muted text-center py-4 small">Belum diunggah</div>
                        @endif
                    </div>
                </div>

                {{-- LANGKAH 2 — Data berkas: yang terbaca di KTP harus
                     sama dengan yang diklaim formulir. --}}
                <div class="text-muted fw-semibold mb-2" style="font-size: 11px;">
                    LANGKAH 2 · BANDINGKAN DATA KTP DENGAN KLAIM
                </div>
                <dl class="row mb-4 fs-3">
                    <dt class="col-sm-3">Nama akun</dt>
                    <dd class="col-sm-9">{{ $user->name }}</dd>
                    <dt class="col-sm-3">NIK</dt>
                    <dd class="col-sm-9 font-monospace">{{ $user->nik ?? '— (belum diisi)' }}</dd>
                    <dt class="col-sm-3">Nomor HP</dt>
                    <dd class="col-sm-9">
                        <span class="font-monospace">{{ $user->phone }}</span>
                        <span class="badge bg-success-subtle text-success ms-1">dibuktikan OTP</span>
                    </dd>
                    <dt class="col-sm-3">Diajukan</dt>
                    <dd class="col-sm-9">
                        {{ $user->ktp_submitted_at?->format('d M Y H:i') ?? '—' }}
                        @if ($user->ktp_submitted_at)
                            <span class="text-muted">({{ $user->ktp_submitted_at->diffForHumans(null, true) }} menunggu)</span>
                        @endif
                    </dd>
                </dl>

                {{-- LANGKAH 3 — Domisili: alamat ↔ titik peta + tautan Google
                     (tanpa API key) untuk memastikan titiknya masuk akal. --}}
                <div class="text-muted fw-semibold mb-2" style="font-size: 11px;">
                    LANGKAH 3 · PASTIKAN DOMISILI DI DALAM WILAYAH
                </div>
                <dl class="row mb-2 fs-3">
                    <dt class="col-sm-3">Alamat</dt>
                    <dd class="col-sm-9">{{ $user->address ?? '— (belum diisi)' }}</dd>
                </dl>
                @if ($user->latitude !== null)
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                        <span class="font-monospace text-muted fs-2 me-auto">
                            {{ \App\Support\Angka::desimal($user->latitude, 6) }}, {{ \App\Support\Angka::desimal($user->longitude, 6) }}
                        </span>
                        <a class="btn btn-sm btn-outline-primary"
                           href="https://www.google.com/maps/search/?api=1&query={{ $user->latitude }},{{ $user->longitude }}"
                           target="_blank" rel="noopener">
                            <i class="ti ti-map-pin" aria-hidden="true"></i>
                            Cek Titik di Google Maps
                        </a>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode(trim(($user->address ?? '').' '.config('seekitar.regency'))) }}"
                           target="_blank" rel="noopener">
                            <i class="ti ti-map-search" aria-hidden="true"></i>
                            Cari Alamatnya
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning py-2 fs-3 mb-4">
                        Titik domisili belum diisi pengguna — minta ia melengkapi
                        titik rumahnya di aplikasi, atau sunting lewat halaman Pengguna.
                    </div>
                @endif

                @if ($user->rejected_at)
                    <div class="alert alert-warning py-2 fs-3 mb-4">
                        Ini pengajuan ulang: berkas sebelumnya ditolak oleh
                        {{ $user->rejectedBy?->name ?? '—' }} — “{{ $user->rejected_reason }}”.
                        Pastikan masalah itu sudah beres pada berkas baru ini.
                    </div>
                @endif

                {{-- Gerbang checklist (js/checklist-gate.js): tombol Setujui
                     terkunci sampai keempat pemeriksaan dicentang. --}}
                <div class="border rounded p-3 bg-light" data-checklist>
                    <div class="text-muted fw-semibold mb-2" style="font-size: 11px;">
                        KONFIRMASI PEMERIKSAAN — WAJIB SEBELUM MENYETUJUI
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="cekWajah{{ $index }}">
                        <label class="form-check-label fs-3" for="cekWajah{{ $index }}">
                            Wajah pada foto wajah cocok dengan foto pada KTP
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="cekKtp{{ $index }}">
                        <label class="form-check-label fs-3" for="cekKtp{{ $index }}">
                            Foto KTP jelas — nama, NIK, dan tanggal lahir terbaca utuh
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="cekNik{{ $index }}">
                        <label class="form-check-label fs-3" for="cekNik{{ $index }}">
                            NIK di KTP sama dengan NIK yang diinput pengguna
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="cekDomisili{{ $index }}">
                        <label class="form-check-label fs-3" for="cekDomisili{{ $index }}">
                            Alamat dan titik petanya masuk akal dalam {{ config('seekitar.regency') }}
                        </label>
                    </div>
                </div>

                {{-- Form tolak (collapse — bukan modal di dalam modal). --}}
                <div class="collapse mt-3" id="tolakUser{{ $index }}">
                    <form method="POST" action="{{ route('admin.verifications.users.reject', $user) }}"
                          class="border border-danger-subtle rounded p-3">
                        @csrf
                        <label for="tolakUser{{ $index }}Reason" class="form-label">Alasan penolakan</label>
                        <textarea id="tolakUser{{ $index }}Reason" name="reason" class="form-control"
                                  rows="3" required minlength="10" maxlength="500"
                                  placeholder="Contoh: foto KTP buram, NIK tidak terbaca."></textarea>
                        <div class="form-text">
                            Alasan ini tampil di aplikasi pengguna agar ia tahu apa yang diperbaiki;
                            kedudukannya jatuh ke <strong>Ditolak</strong> dan keluar dari antrian.
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm mt-2">Kirim penolakan</button>
                    </form>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="collapse" data-bs-target="#tolakUser{{ $index }}"
                            aria-expanded="false">
                        Tolak
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Tutup</button>
                    <form method="POST" action="{{ route('admin.verifications.users.verify', $user) }}">
                        @csrf
                        <button type="submit" class="btn btn-success" disabled
                                data-tombol-verifikasi
                                title="Centang seluruh konfirmasi pemeriksaan dulu">
                            <i class="ti ti-circle-check" aria-hidden="true"></i>
                            Setujui Identitas
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
