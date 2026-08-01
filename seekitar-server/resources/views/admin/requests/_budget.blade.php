<div class="text-nowrap">
  @php
    $min = (float) ($request->budget_min ?? 0);
    $max = (float) ($request->budget_max ?? 0);
    $fmt = fn (float $v) => $v == round($v, 0)
        ? number_format($v, 0, ',', '.')
        : number_format($v, 1, ',', '.');
  @endphp
  @if ($min > 0 && $max > 0)
    <span class="fw-semibold text-dark">Rp {{ $fmt($min) }} – Rp {{ $fmt($max) }}</span>
  @elseif ($min > 0)
    <span class="fw-semibold text-dark">Mulai Rp {{ $fmt($min) }}</span>
  @elseif ($max > 0)
    <span class="fw-semibold text-dark">Sampai Rp {{ $fmt($max) }}</span>
  @else
    <span class="text-muted">—</span>
  @endif
</div>
