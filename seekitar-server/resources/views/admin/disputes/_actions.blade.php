{{-- Aksi baris laporan — tampil di bilah aksi sebelah judul. --}}
@can('manage-disputes')
    <a href="{{ route('admin.disputes.show', $dispute) }}" class="btn btn-sm btn-outline-primary">
        <i class="fa-solid fa-gavel me-1" aria-hidden="true"></i> Tinjau
    </a>
@endcan
