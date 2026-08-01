@if ($user->rejected_at)
  <span class="badge bg-light-warning text-warning fw-bold px-2 py-1" title="Pernah ditolak sebelumnya oleh {{ $user->rejectedBy?->name }}">Pengajuan Ulang</span>
@else
  <span class="badge bg-light-info text-info fw-bold px-2 py-1">Pengajuan Baru</span>
@endif
