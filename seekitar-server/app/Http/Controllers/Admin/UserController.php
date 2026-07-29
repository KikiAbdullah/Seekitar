<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index');
    }

    /** Endpoint AJAX Datatables. */
    public function data(Request $request, UsersDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            // Statistik & jejak verifikasi ikut dimuat: formulir sunting tidak
            // boleh memaksa admin memutuskan "buta" tanpa konteks si pengguna.
            'user' => $user->loadMissing('verified1By:id,name', 'verified2By:id,name')
                ->loadCount(['stores', 'customerRequests', 'orders']),
        ]);
    }

    /**
     * Detail satu pengguna.
     *
     * Jejak verifikasi (siapa & kapan tiap tahap) dan toko miliknya dimuat
     * sekaligus — halaman ini adalah satu-satunya tempat admin melihat
     * riwayat lengkap satu akun tanpa berpindah-pindah layar.
     *
     * Berkas KTP/selfie TIDAK dimuat di sini: izin halaman ini `manage-users`,
     * sedangkan berkas identitas adalah hak `verify-users`. Blade menampilkan
     * bagian itu hanya bila izinnya ada (route media pun memagarainya).
     */
    public function show(User $user): View
    {
        return view('admin.users.show', [
            // withCoordinates(): latitude/longitude dibaca dari kolom POINT
            // lewat ST_Latitude/ST_Longitude — properti biasa isinya WKB
            // biner, dan strict mode melempar error bila kolomnya tak dipilih.
            'user' => User::query()
                ->withCoordinates()
                ->with([
                    'verified1By:id,name',
                    'verified2By:id,name',
                    'stores:id,user_id,name,photo,verification_status,is_active,rating_avg,total_reviews,created_at',
                ])
                ->withCount(['stores', 'customerRequests', 'orders'])
                ->findOrFail($user->getKey()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'verification_level' => ['required', 'integer', 'between:1,3'],
        ], [
            'name.required'               => 'Nama wajib diisi.',
            'name.max'                    => 'Nama maksimal 100 karakter.',
            'verification_level.required' => 'Pilih level verifikasi.',
            'verification_level.between'  => 'Level verifikasi tidak dikenal.',
        ]);

        $user->update($data);

        // Kembali ke detail, bukan daftar: admin biasanya ingin memastikan
        // hasil suntingannya tampil benar tepat setelah menyimpan.
        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Memblokir/membuka blokir pengguna.
     *
     * Token WAJIB dicabut saat memblokir — tanpa itu sesi yang sudah
     * berjalan tetap hidup sampai tokennya kedaluwarsa sendiri.
     */
    public function block(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required_if:action,block', 'nullable', 'string', 'max:255'],
        ]);

        $blocking = $request->input('action') === 'block';

        DB::transaction(function () use ($user, $blocking, $data): void {
            $user->is_blocked     = $blocking;
            $user->blocked_reason = $blocking ? $data['reason'] : null;
            $user->blocked_at     = $blocking ? now() : null;
            $user->save();

            if ($blocking) {
                $user->tokens()->delete();
                $user->stores()->update(['is_active' => false]);
            }
        });

        return back()->with('success', $blocking ? 'Pengguna diblokir.' : 'Blokir dicabut.');
    }
}
