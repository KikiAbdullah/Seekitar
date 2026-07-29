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
                'id', 'phone', 'name',
                'rating_avg', 'total_reviews',
                'is_blocked', 'ktp_submitted_at', 'created_at',
            ]);

        if ($request->filled('is_blocked')) {
            $query->where('is_blocked', $request->boolean('is_blocked'));
        }

        return DataTables::eloquent($query)
            ->editColumn('created_at', fn (User $u) => $u->created_at?->format('d M Y H:i'))
            // ★ teks, bukan HTML — kolom ini lolos escaping DataTables apa adanya.
            ->addColumn('rating', fn (User $u) => (int) $u->total_reviews > 0
                ? sprintf('★ %s (%d)', number_format((float) $u->rating_avg, 1, ',', '.'), $u->total_reviews)
                : '—')
            ->addColumn('status', fn (User $u) => $u->is_blocked ? 'Diblokir' : 'Aktif')
            ->addColumn('action', fn (User $u) => view('admin.users._actions', ['user' => $u])->render())
            // Kolom hasil render HTML tidak boleh di-escape ulang; sisanya
            // TETAP di-escape oleh Blade.
            ->rawColumns(['action'])
            // Kolom aksi tidak mewakili data, jadi mengurutkannya tidak
            // bermakna dan hanya menghasilkan SQL yang salah.
            ->toJson();
    }
}
