<div class="d-flex align-items-center">
  @can('manage-disputes')
    @if ($dispute->status === \App\Enums\DisputeStatus::Open)
      <a href="#" class="btn btn-outline-warning table-action-btn" id="action-resolve" title="Selesaikan Laporan">
        <i class="ti ti-check fs-4" aria-hidden="true"></i>
        <span>Selesaikan</span>
      </a>
    @else
      <a href="#" class="btn btn-outline-info table-action-btn" id="action-resolve" title="Lihat Detail Laporan">
        <i class="ti ti-search fs-4" aria-hidden="true"></i>
        <span>Detail</span>
      </a>
    @endif
  @endcan
</div>
