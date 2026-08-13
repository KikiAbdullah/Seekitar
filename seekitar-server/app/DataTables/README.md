# `app/DataTables/`

Kelas pembangun query untuk tabel admin (Yajra Datatables).

## Kenapa memakai gaya facade, bukan `Services\DataTable`

`Server_Implementation_Guide.md` §10 mencontohkan kelas turunan
`Yajra\DataTables\Services\DataTable`. Kelas itu **tidak ada** di paket
`yajra/laravel-datatables-oracle` — ia berasal dari paket terpisah
`yajra/laravel-datatables-buttons`, yang fungsinya mengekspor CSV/Excel.

Seekitar tidak memakai paket Buttons maupun ekspor Excel. Ekspor **CSV** sudah
tersedia untuk pengguna, toko, pesanan, dan penawaran melalui
`App\Exports\DataTableExport`, sehingga tidak bergantung pada paket tambahan.
Untuk tabel interaktif dipakai `DataTables::eloquent($query)` dari paket inti,
dibungkus kelas biasa agar query tetap terpusat dan mudah diuji.
