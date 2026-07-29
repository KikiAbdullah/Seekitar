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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    /**
     * Menyimpan suntingan profil.
     *
     * ATURAN 5: formulir admin TIDAK PERNAH menyentuh stempel verifikasi
     * (verified1/2_by/at). Stempel adalah fakta audit "admin X menyetujui
     * pada waktu Y" — penyuntingan data oleh admin lain tidak membatalkan
     * fakta itu. Kebalikannya (perubahan oleh pengguna sendiri lewat API)
     * WAJIB verifikasi ulang: lihat Api\V1\VerificationController::uploadKtp
     * dan AuthController::verifyPhoneChangeOtp.
     *
     * Nomor HP juga tidak bisa diubah di sini: nomor adalah kredensial masuk
     * (OTP); menggantinya tanpa bukti kepemilikan = menyerahkan akun.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // String kosong dari input teks ≠ null — tanpa normalisasi ini,
        // isian yang SENGAJA dikosongkan admin justru gagal di rule
        // email/digits: kolomnya tidak pernah bisa dikosongkan lagi.
        $request->merge(array_map(
            fn ($nilai) => $nilai === '' ? null : $nilai,
            $request->only(['email', 'address', 'nik']),
        ));

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'nik'     => ['nullable', 'digits:16'],
            // Avatar publik; KTP/selfie divalidasi TERPISAH di bawah —
            // hanya pemegang izin verifikasi yang boleh menyentuhnya.
            'avatar'  => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max'      => 'Nama maksimal 100 karakter.',
            'email.email'   => 'Format email tidak valid.',
            'email.unique'  => 'Email ini sudah dipakai akun lain.',
            'address.max'   => 'Alamat maksimal 255 karakter.',
            'nik.digits'    => 'NIK harus tepat 16 digit angka.',
            'avatar.image'  => 'Foto profil harus berupa gambar.',
            'avatar.mimes'  => 'Foto profil hanya boleh JPEG atau PNG.',
            'avatar.max'    => 'Foto profil maksimal 2 MB.',
        ]);

        // Hanya kolom yang benar-benar DIKIRIM yang ditimpa: request
        // sebagian (formulir apa pun, atau curl rakitan berisi name saja)
        // tidak boleh menghapus email/alamat/NIK yang sudah ada.
        $user->fill(collect($data)->only(['name', 'email', 'address'])->all());

        if (array_key_exists('nik', $data)) {
            if ($data['nik'] === null) {
                $user->nik      = null;
                $user->nik_hash = null;
            } else {
                // Keunikan NIK diperiksa lewat nik_hash: kolom nik-nya
                // terenkripsi sehingga mustahil di-WHERE (DATABASE.md §4.1).
                $hash = hash_hmac('sha256', $data['nik'], config('app.key'));

                if (User::where('nik_hash', $hash)->whereKeyNot($user->id)->exists()) {
                    throw ValidationException::withMessages([
                        'nik' => 'NIK ini sudah terdaftar pada akun lain.',
                    ]);
                }

                $user->nik      = $data['nik'];
                $user->nik_hash = $hash;
            }
        }

        $avatarLama = $user->getRawOriginal('avatar_url');

        if ($request->hasFile('avatar')) {
            // Kolomnya menyimpan URL, bukan path (mengikuti updateProfile
            // API) — keduanya harus setuju agar penghapusan file lama benar.
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = Storage::disk('public')->url($path);
        }

        /*
         * Berkas identitas adalah hak `verify-users`, bukan `manage-users`
         * (UU PDP) — field-nya memang disembunyikan di blade tanpa izin itu,
         * tetapi "tidak terlihat" bukan kontrol akses: request palsu mudah
         * dibuat, jadi gerbang sebenarnya di sini.
         */
        $ktpLama    = null;
        $selfieLama = null;

        if ($request->user()->can('verify-users')) {
            $request->validate([
                'ktp_image'    => ['nullable', 'image', 'mimes:jpeg,png', 'max:5120'],
                'selfie_image' => ['nullable', 'image', 'mimes:jpeg,png', 'max:5120'],
            ], [
                'ktp_image.image'      => 'Foto KTP harus berupa gambar.',
                'ktp_image.max'        => 'Foto KTP maksimal 5 MB.',
                'selfie_image.image'   => 'Foto wajah harus berupa gambar.',
                'selfie_image.max'     => 'Foto wajah maksimal 5 MB.',
            ]);

            $ktpLama    = $user->ktp_image;
            $selfieLama = $user->selfie_image;

            // Disk PRIVAT (bukan public): KTP tidak boleh bisa diakses
            // lewat URL tebakan.
            if ($request->hasFile('ktp_image')) {
                $user->ktp_image = $request->file('ktp_image')->store("ktp/{$user->id}", 'local');
            }
            if ($request->hasFile('selfie_image')) {
                $user->selfie_image = $request->file('selfie_image')->store("ktp/{$user->id}", 'local');
            }
        }

        $user->save();

        // Berkas lama baru dibuang SETELAH save berhasil — kalau dihapus
        // duluan dan save gagal, satu-satunya salinan berkas ikut hilang.
        if ($ktpLama && $ktpLama !== $user->ktp_image) {
            Storage::disk('local')->delete($ktpLama);
        }
        if ($selfieLama && $selfieLama !== $user->selfie_image) {
            Storage::disk('local')->delete($selfieLama);
        }
        if ($avatarLama && $avatarLama !== $user->avatar_url) {
            // Hanya hapus bila URL-nya menunjuk file LOKAL: avatar bisa
            // berisi URL placeholder luar, yang bukan milik disk kita.
            $prefixLokal = Storage::disk('public')->url('');
            if (str_starts_with($avatarLama, $prefixLokal)) {
                Storage::disk('public')->delete(substr($avatarLama, strlen($prefixLokal)));
            }
        }

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
