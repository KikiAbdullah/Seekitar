{{--
  Banner persetujuan cookie (UU PDP). Simpati ke localStorage agar tidak
  muncul lagi setelah pengunjung menekan "Setuju".
--}}
<div id="cookieConsent" class="position-fixed bottom-0 start-0 end-0 d-none" style="z-index: 1080;">
  <div class="container pb-3">
    <div class="card border-0 shadow-lg">
      <div class="card-body d-flex flex-wrap align-items-center gap-3 p-4">
        <span class="icon-soft"><i class="ti ti-cookie"></i></span>
        <p class="mb-0 text-muted flex-fill">
          Kami menggunakan cookie untuk memastikan situs ini berfungsi dengan baik.
          Baca detailnya di <a class="text-primary fw-semibold" href="{{ route('web.privacy') }}">Kebijakan Privasi</a>.
        </p>
        <button type="button" class="btn btn-primary px-4 btn-hover-shadow" id="cookieConsentBtn">Setuju</button>
      </div>
    </div>
  </div>
</div>

@once
  <script>
    (function () {
      var KEY = 'seekitar_cookie_consent';
      if (localStorage.getItem(KEY)) return;
      var el = document.getElementById('cookieConsent');
      el.classList.remove('d-none');
      document.getElementById('cookieConsentBtn').addEventListener('click', function () {
        localStorage.setItem(KEY, '1');
        el.classList.add('d-none');
      });
    })();
  </script>
@endonce
