<div style="min-width: 220px;">
  <span class="fw-semibold text-dark">{{ $request->title }}</span>
  @if ($request->description)
    <div class="fs-2 text-muted" style="white-space: normal; max-width: 320px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
      {{ \Illuminate\Support\Str::limit($request->description, 140) }}
    </div>
  @endif
  <div class="d-flex flex-wrap align-items-center gap-2 mt-1 fs-2 text-muted">
    @php
      $radius = (float) ($request->radius_km ?? 0);
      $radiusTeks = $radius == round($radius, 0)
          ? number_format($radius, 0, ',', '.')
          : number_format($radius, 1, ',', '.');
    @endphp
    <span class="d-inline-flex align-items-center gap-1">
      <i class="ti ti-radar" aria-hidden="true"></i> Radius {{ $radiusTeks }} km
    </span>
    @if (is_array($request->images) && count($request->images) > 0)
      <span class="d-inline-flex align-items-center gap-1">
        <i class="ti ti-photo" aria-hidden="true"></i> {{ count($request->images) }} gambar
      </span>
    @endif
    @if ($request->required_date)
      <span class="d-inline-flex align-items-center gap-1">
        <i class="ti ti-calendar" aria-hidden="true"></i> Dibutuhkan {{ $request->required_date?->format('d M Y') }}
      </span>
    @endif
  </div>
</div>
