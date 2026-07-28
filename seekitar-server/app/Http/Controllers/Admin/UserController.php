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
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'verification_level' => ['required', 'integer', 'between:1,3'],
        ]);

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Data pengguna diperbarui.');
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
