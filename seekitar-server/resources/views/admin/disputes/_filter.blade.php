<div class="d-flex flex-wrap align-items-center gap-3">
    <div>
        <label for="disputes-filter-status" class="visually-hidden">Saring status</label>
        <select class="form-select js-select2" id="disputes-filter-status" name="status"
                data-dt-filter="{{ $tableId }}">
            <option value="">Semua Status</option>
            <option value="open">Terbuka (Open)</option>
            <option value="resolved">Selesai (Resolved)</option>
        </select>
    </div>
</div>
