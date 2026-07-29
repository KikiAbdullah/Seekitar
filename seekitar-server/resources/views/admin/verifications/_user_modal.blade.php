{{--
    Modal berkas & aksi verifikasi satu pengguna (tiga tahap).

    Kenapa dirender per baris, bukan satu modal tunggal yang diisi JS:
    berkas identitas disajikan lewat URL berizin per pengguna, dan merender
    per baris berarti HTML halaman ini tidak pernah memuat path/pengenal
    pengguna lain.

    Penolakan memakai collapse DI DALAM modal, bukan modal kedua —
    Bootstrap tidak mendukung modal bersarang.

    $index  nomor baris (menyamakan id dengan target pemicu pada tabel)
    $user   model User antrian
--}}
@php
    $tahap = $user->nextVerificationStep();
@endphp

<div class="modal fade text-start" id="verifikasiUser{{ $index }}" tabindex="-1"
     aria-labelledby="verifikasiUser{{ $index }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="verifikasiUser{{ $index }}Label">
                        Verifikasi {{ $user->name }}
                    </h5>
                    <span class="badge text-bg-secondary">Tahap {{ $tahap ?? '—' }} · Menunggu</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">

                {{-- Berkas identitas. Foto wajah disandingkan dengan
                     KTP supaya wajahnya bisa dibandingkan dalam satu pandang. --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">FOTO USER</div>
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

                {{-- Data yang dipakai untuk validasi KTP. --}}
                <dl class="row mb-3 fs-3">
                    <dt class="col-sm-3">Nomor HP</dt>
                    <dd class="col-sm-9">{{ $user->phone }}</dd>
                    <dt class="col-sm-3">NIK</dt>
                    <dd class="col-sm-9 font-monospace">{{ $user->nik ?? '— (belum diisi)' }}</dd>
                    <dt class="col-sm-3">Diajukan</dt>
                    <dd class="col-sm-9">
                        {{ $user->ktp_submitted_at?->format('d M Y H:i') ?? '—' }}
                        @if ($user->ktp_submitted_at)
                            <span class="text-muted">({{ $user->ktp_submitted_at->diffForHumans(null, true) }} menunggu)</span>
                        @endif
                    </dd>
                    <dt class="col-sm-3">Bergabung</dt>
                    <dd class="col-sm-9">{{ $user->created_at->format('d M Y') }}</dd>
                </dl>

                @if ($user->ktp_rejected_reason)
                    <div class="alert alert-warning py-2 fs-3 mb-3">
                        Pernah ditolak: {{ $user->ktp_rejected_reason }}
                    </div>
                @endif

                <div class="border rounded p-3 mb-1">
                    <div class="text-muted fw-semibold mb-2" style="font-size: 11px;">RIWAYAT TAHAP</div>
                    <ul class="list-unstyled mb-0 fs-3">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="ti {{ $user->verified1_at ? 'ti-circle-check text-success' : 'ti-clock-hour-4 text-warning' }} mt-1" aria-hidden="true"></i>
                            <span>
                                <strong>Tahap 1 · Nomor HP</strong><br>
                                @if ($user->verified1_at)
                                    {{ $user->verified1By?->name ?? '—' }} · {{ $user->verified1_at->format('d M Y H:i') }}
                                @else
                                    <span class="text-muted">Menunggu verifikasi</span>
                                @endif
                            </span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="ti {{ $user->verified2_at ? 'ti-circle-check text-success' : 'ti-clock-hour-4 text-warning' }} mt-1" aria-hidden="true"></i>
                            <span>
                                <strong>Tahap 2 · KTP &amp; NIK</strong><br>
                                @if ($user->verified2_at)
                                    {{ $user->verified2By?->name ?? '—' }} · {{ $user->verified2_at->format('d M Y H:i') }}
                                @else
                                    <span class="text-muted">Menunggu verifikasi</span>
                                @endif
                            </span>
                        </li>
                    </ul>
                </div>

                {{-- Form tolak (collapse — bukan modal di dalam modal). --}}
                @if ($user->ktp_submitted_at !== null)
                    <div class="collapse mt-3" id="tolakUser{{ $index }}">
                        <form method="POST" action="{{ route('admin.verifications.users.reject', $user) }}"
                              class="border border-danger-subtle rounded p-3">
                            @csrf
                            <label for="tolakUser{{ $index }}Reason" class="form-label">Alasan penolakan</label>
                            <textarea id="tolakUser{{ $index }}Reason" name="reason" class="form-control"
                                      rows="3" required minlength="10" maxlength="500"
                                      placeholder="Contoh: Foto KTP buram, NIK tidak terbaca."></textarea>
                            <div class="form-text">
                                Antrian KTP ditutup dan pengguna bisa mengirim ulang berkas.
                                Tahap yang sudah lolos tidak dicabut — pengguna mengulang dari tahap yang gagal.
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm mt-2">Kirim penolakan</button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="modal-footer justify-content-between">
                <div>
                    @if ($user->ktp_submitted_at !== null)
                        <button type="button" class="btn btn-outline-danger"
                                data-bs-toggle="collapse" data-bs-target="#tolakUser{{ $index }}"
                                aria-expanded="false">
                            Tolak
                        </button>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Tutup</button>
                    @if ($tahap !== null)
                        <form method="POST" action="{{ route('admin.verifications.users.verify', $user) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-circle-check" aria-hidden="true"></i>
                                Verifikasi Tahap {{ $tahap }} · {{ \App\Models\User::verificationStepLabel($tahap) }}
                            </button>
                        </form>
                    @else
                        <span class="text-success fs-3">
                            <i class="ti ti-circle-check" aria-hidden="true"></i> Semua tahap selesai
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
