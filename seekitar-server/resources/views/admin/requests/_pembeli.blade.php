<div class="d-flex align-items-center gap-2">
  @if ($request->user)
    <img src="{{ $request->user->avatar_url }}" class="rounded-circle border" width="36" height="36" style="object-fit: cover;" alt="Avatar {{ $request->user->name }}">
  @endif
  <div>
    <div class="d-flex align-items-center gap-1">
      <span class="text-dark fw-semibold text-nowrap">{{ $request->user?->name ?? '—' }}</span>
      @if ($request->user?->verified_at)
        <span class="text-success" title="KTP Terverifikasi">
          <i class="ti ti-circle-check fs-3" role="img" aria-label="Terverifikasi"></i>
        </span>
      @endif
    </div>
    @if ($request->user?->phone)
      <span class="fs-2 text-muted text-nowrap">{{ $request->user->phone }}</span>
    @endif
  </div>
</div>
