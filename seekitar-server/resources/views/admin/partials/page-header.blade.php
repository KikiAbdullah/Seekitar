<div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <h4 class="fw-semibold mb-2">{{ $judul }}</h4>

                <nav aria-label="Remah roti">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                        </li>

                        @foreach ($remah ?? [] as $teks => $tautan)
                            @if ($loop->last)
                                <li class="breadcrumb-item active" aria-current="page">{{ $teks }}</li>
                            @else
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ $tautan }}">{{ $teks }}</a>
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="position-absolute top-0 end-0 d-none d-lg-block" aria-hidden="true">
        <img src="{{ asset('vendor/mordenize/images/backgrounds/welcome-bg2.png') }}" alt="" width="160" height="120" style="opacity: .4;">
    </div>
</div>
