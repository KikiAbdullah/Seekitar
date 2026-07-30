<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <span class="modal-title text-white fs-4" id="lightboxLabel"></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="lightboxImg" src="" alt="" class="img-fluid rounded"
                     style="max-height: 80vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    if (!window.__lightboxInited) {
        window.__lightboxInited = true;
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('lightboxModal');
            if (!modalEl) return;
            var modal = new bootstrap.Modal(modalEl);
            var imgEl = document.getElementById('lightboxImg');
            var labelEl = document.getElementById('lightboxLabel');

            document.addEventListener('click', function (e) {
                var tautan = e.target.closest('[data-lightbox]');
                if (!tautan) return;
                e.preventDefault();
                var src = tautan.href || tautan.dataset.src;
                if (src) {
                    imgEl.src = src;
                    labelEl.textContent = tautan.dataset.title || '';
                    modal.show();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                imgEl.src = '';
            });
        });
    }
</script>
@endpush