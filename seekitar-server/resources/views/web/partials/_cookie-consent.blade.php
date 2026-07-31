<aside class="lp-cookie" id="cookieBanner" role="alert" aria-label="Pemberitahuan cookie">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <p class="mb-0 text-secondary" style="max-width: 720px;">
            Kami memakai cookie dan teknologi sejenis untuk memastikan situs
            berfungsi dengan baik. Dengan melanjutkan, kamu menyetujui
            <a href="{{ route('web.privacy') }}">Kebijakan Privasi</a> kami.
        </p>
        <button type="button" class="btn btn-seekitar btn-sm px-3 flex-shrink-0"
                id="cookieAcceptBtn" aria-label="Setuju dan tutup pemberitahuan">
            Setuju
        </button>
    </div>
</aside>

@push('scripts')
    <script>
        (function () {
            var key = 'seekitar_cookie_consent';
            if (localStorage.getItem(key)) return;
            document.getElementById('cookieBanner').classList.add('lp-cookie--tampil');
            document.getElementById('cookieAcceptBtn').addEventListener('click', function () {
                localStorage.setItem(key, '1');
                document.getElementById('cookieBanner').classList.remove('lp-cookie--tampil');
            });
        })();
    </script>
@endpush