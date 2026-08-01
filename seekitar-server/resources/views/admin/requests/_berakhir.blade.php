<div class="text-nowrap">
  @if ($request->expires_at)
    <span class="fs-3 {{ $request->expires_at->isPast() ? 'text-danger' : 'text-dark' }}">
      {{ $request->expires_at->format('d M Y') }}
    </span>
    @if ($request->extension_count > 0)
      <div class="fs-2 text-muted mt-1">
        <i class="ti ti-clock" aria-hidden="true"></i>
        Diperpanjang {{ $request->extension_count }}×
        @if ($request->extended_at)
          · {{ $request->extended_at->format('d M Y') }}
        @endif
      </div>
    @endif
  @else
    <span class="text-muted">—</span>
  @endif
</div>
