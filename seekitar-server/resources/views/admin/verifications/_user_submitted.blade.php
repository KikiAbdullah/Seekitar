<div class="fw-semibold text-dark">{{ $user->ktp_submitted_at?->format('d M Y, H:i') ?? $user->updated_at->format('d M Y, H:i') }}</div>
<div class="fs-2 text-danger"><i class="ti ti-clock me-1" aria-hidden="true"></i>SLA: {{ $user->ktp_submitted_at ? $user->ktp_submitted_at->addHours(24)->diffForHumans() : '—' }}</div>
