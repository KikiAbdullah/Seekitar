# `app/DataTables/`

Kelas pembangun query untuk tabel admin (Yajra Datatables).

## Kenapa memakai gaya facade, bukan `Services\DataTable`

`Server_Implementation_Guide.md` §10 mencontohkan kelas turunan
`Yajra\DataTables\Services\DataTable`. Kelas itu **tidak ada** di paket
`yajra/laravel-datatables-oracle` — ia berasal dari paket terpisah
`yajra/laravel-datatables-buttons`, yang fungsinya mengekspor CSV/Excel.

Seekitar tidak membutuhkan ekspor, jadi paket tambahan itu tidak dipasang.
Sebagai gantinya dipakai `DataTables::eloquent($query)` yang tersedia di
paket inti, dibungkus kelas biasa agar tetap terpusat dan mudah diuji.
