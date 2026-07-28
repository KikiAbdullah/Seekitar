<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Profil admin yang sedang masuk.
 *
 * Berbeda dari `UserController`, yang mengelola pengguna LAIN dan menuntut
 * `manage-users`. Halaman ini hanya menyentuh akun sendiri, jadi tidak
 * memerlukan permission apa pun — admin tanpa `manage-users` tetap harus bisa
 * memperbaiki namanya sendiri.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        // Mengganti email berarti mengganti nama pengguna untuk login panel.
        // Jejaknya dicatat: perubahan diam-diam pada kredensial admin adalah
        // hal pertama yang dicari saat menyelidiki insiden.
        if ($user->isDirty('email')) {
            logger()->channel('security')->notice('Email admin diubah', [
                'user_id' => $user->id,
                'dari'    => $user->getOriginal('email'),
                'ke'      => $user->email,
                'ip'      => $request->ip(),
            ]);
        }

        $user->save();

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Profil diperbarui.');
    }
}
