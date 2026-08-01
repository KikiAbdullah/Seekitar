<div class="d-flex align-items-center gap-2">
  <span class="fw-semibold text-dark">{{ $user->name }}</span>
  @if ($user->verified_at)
    <span class="text-success" title="Terverifikasi (KTP)">
      <i class="ti ti-circle-check fs-3" role="img" aria-label="Terverifikasi"></i>
    </span>
  @endif
</div>
