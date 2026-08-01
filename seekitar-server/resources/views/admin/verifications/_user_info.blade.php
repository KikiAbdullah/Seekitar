<div class="d-flex align-items-center gap-3">
  <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 45px; height: 45px;">
    {{ $user->initials }}
  </div>
  <div>
    <h6 class="fw-bold mb-0 text-dark">{{ $user->name }}</h6>
    <span class="fs-2 text-muted">ID: {{ $user->id }}</span>
  </div>
</div>
