{{--
    Partial tabel server-side.

    $tableId  id elemen
    $columns  [['data' => 'name', 'label' => 'Nama', 'orderable' => false], ...]
    $ajax     URL endpoint data
    $order    (opsional) [[index, 'asc'|'desc']]
--}}
@php
    // Disusun di blok @php, BUKAN di dalam @json(): Blade memotong argumen
    // direktif pada kurung penutup pertama, sehingga arrow function
    // multi-baris menghasilkan PHP yang tidak bisa di-parse.
    $dtColumns = array_map(static fn (array $c): array => [
        'data'       => $c['data'],
        'orderable'  => $c['orderable'] ?? true,
        'searchable' => $c['searchable'] ?? true,
    ], $columns);
@endphp

<table id="{{ $tableId }}" class="table table-striped w-100">
    <thead>
    <tr>
        @foreach ($columns as $col)
            <th>{{ $col['label'] }}</th>
        @endforeach
    </tr>
    </thead>
</table>

@push('scripts')
<script>
    (function () {
        const columns = @json($dtColumns);

        $('#' + @js($tableId)).DataTable({
            processing: true,
            serverSide: true,   // tabel besar tidak dikirim utuh ke browser
            ajax: @js($ajax),
            order: @json($order ?? [[0, 'desc']]),
            columns: columns,
        });
    })();
</script>
@endpush
