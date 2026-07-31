<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Sumber data tabel pengguna di panel admin.
 *
 * Dipisahkan dari controller supaya definisi kolom, filter, dan penyaringan
 * bisa diuji tanpa melewati lapisan HTTP.
 */
class UsersDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = User::query()
            ->select([
                'id', 'phone', 'name', 'email', 'address',
                'rating_avg', 'total_reviews',
                // status & verified_at ikut dipilih untuk lencana kedudukan
                // dan ikon centang pada nama — bukan kolom tampilan sendiri.
                'status', 'ktp_submitted_at', 'verified_at', 'created_at',
            ]);

        // Filter kedudukan dari select tabel: nilai = nilai enum mentahnya,
        // sehingga tidak ada pemetaan kedua yang bisa menyimpang.
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return DataTables::eloquent($query)
            ->editColumn('created_at', fn (User $u) => $u->created_at?->format('d M Y H:i'))
            // Nama dirender lewat Blade (bukan konkatenasi string) supaya
            // nama tetap lolos escaping — kolom ini masuk rawColumns demi
            // ikon centang terverifikasinya.
            ->editColumn('name', fn (User $u) => view('admin.users._nama', ['user' => $u])->render())
            ->editColumn('email', fn (User $u) => $u->email ?? '—')
            ->editColumn('address', fn (User $u) => $u->address ?? '—')
            // ★ teks, bukan HTML — kolom ini lolos escaping DataTables apa adanya.
            ->addColumn('rating', fn (User $u) => (int) $u->total_reviews > 0
                ? sprintf('★ %s (%d)', number_format((float) $u->rating_avg, 1, ',', '.'), $u->total_reviews)
                : '—')
            // Lencana kedudukan dirender Blade supaya warna enum tidak
            // diduplikasi di PHP — satu sumber: UserStatus::color().
            ->editColumn('status', fn (User $u) => view('admin.users._status', ['user' => $u])->render())

            // Kolom hasil render HTML tidak boleh di-escape ulang; sisanya
            // TETAP di-escape oleh Blade.
            ->rawColumns(['name', 'status'])
            // Kolom aksi tidak mewakili data, jadi mengurutkannya tidak
            // bermakna dan hanya menghasilkan SQL yang salah.
            ->toJson();
    }
}
