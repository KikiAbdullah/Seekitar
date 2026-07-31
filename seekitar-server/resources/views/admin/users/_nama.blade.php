<div class="d-flex align-items-center gap-2">
  <span class="fw-semibold text-dark">{{ $user->name }}</span>
  @if ($user->verified_at)
    <span class="text-success" title="Terverifikasi (KTP)">
      <i class="fa-solid fa-circle-check fs-3"></i>
    </span>
  @endif
</div>
