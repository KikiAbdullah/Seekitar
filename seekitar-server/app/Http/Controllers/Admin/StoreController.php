<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\StoresDataTable;
use App\Enums\StoreType;
use App\Exports\DataTableExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\Services\VerifikasiTokoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreController extends Controller
{
    public function __construct(private readonly VerifikasiTokoService $verifikasi) {}

    public function index(): View
    {
        return view('admin.stores.index');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $stores = Store::query()
            ->with('owner:id,name')
            ->select(['id', 'user_id', 'name', 'regency', 'status',
                      'rating_avg', 'total_reviews', 'is_active', 'created_at'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->get();

        $headers = ['ID', 'Nama', 'Pemilik', 'Kabupaten', 'Status', 'Rating', 'Aktif', 'Dibuat'];
        $rows = $stores->map(fn (Store $s) => [
            $s->id,
            $s->name,
            $s->owner?->name ?? '—',
            $s->regency ?? '—',
            $s->status?->label() ?? '—',
            (int) $s->total_reviews > 0
                ? sprintf('★ %s (%d)', number_format((float) $s->rating_avg, 1, ',', '.'), $s->total_reviews)
                : '—',
            $s->is_active ? 'Ya' : 'Tidak',
            $s->created_at?->format('d M Y H:i') ?? '—',
        ]);

        return app(DataTableExport::class)->csv('toko-'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }

    public function data(Request $request, StoresDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    /**
     * Detail satu toko.
     *
     * Seluruh konteks keputusan ada di satu layar: identitas & status KTP
     * pemilik, JEJAK AUDIT lengkap (disetujui / ditolak / diblokir — siapa
     * & kapan), rekening bank beserta atas namanya, rating bintang, serta
     * peta lokasi + lingkaran radius — tanpa berpindah antarmenu.
     */
    public function show(Store $store): View
    {
        $toko = Store::query()
            ->with([
                // Stempel KTP pemilik — dasar status layak.
                'owner:id,name,phone,verified_at',
                'verifiedBy:id,name',
                'rejectedBy:id,name',
                'blockedBy:id,name',
            ])
            ->withCount(['listings', 'offers', 'orders', 'reviews'])
            ->findOrFail($store->getKey());

        return view('admin.stores.show', [
            'store'    => $toko,
            // Nama kategori di-resolve dari category_ids (JSON array id).
            'kategori' => Category::query()
                ->whereIn('id', $toko->category_ids ?? [])
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    /**
     * Formulir sunting toko — halaman penuh (pola yang sama dengan
     * sunting pengguna: strip identitas anti-salah-orang di atas).
     */
    public function edit(Store $store): View
    {
        return view('admin.stores.edit', [
            'store'     => $store->load('owner:id,name,phone'),
            // Kategori hanya yang teratas (DATABASE.md §3 — maks 2 level,
            // dan toko memilih induknya), diurutkan supaya nyaman dipindai.
            'kategori'  => Category::query()->whereNull('parent_id')->orderBy('name')->get(['id', 'name']),
            'tipeToko'  => StoreType::cases(),
        ]);
    }

    /**
     * Menyimpan perubahan toko.
     *
     * Aturan 5 berlaku di sini juga: penyuntingan admin TIDAK pernah
     * menyentuh stempel maupun kedudukan — yang bisa berubah hanya data
     * profil tokonya. Satu-satunya pengecualian sadar: foto tampak depan
     * boleh diganti admin (mis. laporan foto tidak pantas), dan karena
     * foto adalah bahan penilaian, menggantinya pada toko pending adalah
     * bagian dari peninjauan itu sendiri.
     */
    public function update(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'min:3', 'max:100'],

            // SELALU array, meski satu nilai — model yang mengubahnya jadi
            // SET comma-separated (sama dengan kontrak API §3.1).
            'store_type'   => ['required', 'array', 'min:1'],
            'store_type.*' => ['required', Rule::enum(StoreType::class)],

            'category_ids'   => ['required', 'array', 'min:1', 'max:10'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'address'   => ['sometimes', 'nullable', 'string', 'max:255'],
            // Pinpoint WAJIB bagi toko (PRD §5.3.3) — tidak ada toko tanpa
            // titik, jadi di sini required, bukan nullable seperti pengguna.
            'latitude'  => ['required', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['required', 'numeric', 'between:-180,180', 'required_with:latitude'],

            'service_radius_km' => ['sometimes', 'numeric', 'min:0.1', 'max:50'],

            'accepts_cod'     => ['sometimes', 'boolean'],
            'offers_delivery' => ['sometimes', 'boolean'],
            'allows_pickup'   => ['sometimes', 'boolean'],

            'npwp'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'bank_account'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_name' => ['sometimes', 'nullable', 'string', 'max:100'],

            // Foto tampak depan — TEPAT satu berkas, bukan album.
            'photo' => ['sometimes', 'image', 'mimes:jpeg,png,webp', 'max:5120'],

            // Editor 7 hari: checkbox "buka" + dua input jam per hari.
            'operating_hours'        => ['sometimes', 'array'],
            'operating_hours.*.open'  => ['nullable', 'date_format:H:i'],
            'operating_hours.*.close' => ['nullable', 'date_format:H:i'],
        ], [
            'latitude.required'  => 'Titik toko wajib dipasang di peta.',
            'latitude.between'   => 'Lintang harus di antara -90 sampai 90.',
            'longitude.between'  => 'Bujur harus di antara -180 sampai 180.',
        ]);

        $store->fill(collect($data)->except(['photo', 'operating_hours'])->all());

        // Hari tak dicentang = TUTUP (null), bukan dihilangkan dari JSON —
        // bentuknya harus konsisten dengan yang ditulis aplikasi.
        $jamMentah = $request->input('operating_hours', []);
        foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari) {
            $terisi = $jamMentah[$hari] ?? null;
            if (is_array($terisi) && ! empty($terisi['is_open'])
                && ($terisi['open'] ?? '') >= ($terisi['close'] ?? '')) {
                return back()->withInput()->withErrors([
                    "operating_hours.$hari.close" => 'Jam tutup harus setelah jam buka.',
                ]);
            }
        }

        $store->operating_hours = collect(
            ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']
        )->mapWithKeys(function (string $hari) use ($jamMentah): array {
            $terisi = $jamMentah[$hari] ?? null;
            $buka   = is_array($terisi) && ! empty($terisi['is_open']);
            return [$hari => $buka
                ? ['open' => $terisi['open'] ?? '08:00', 'close' => $terisi['close'] ?? '17:00']
                : null];
        })->all();

        // Toggle yang tidak dicentang TIDAK terkirim oleh HTML — absen
        // berarti false, bukan "biarkan".
        $store->accepts_cod     = $request->boolean('accepts_cod');
        $store->offers_delivery = $request->boolean('offers_delivery');
        $store->allows_pickup   = $request->boolean('allows_pickup');

        if (! $store->offers_delivery && ! $store->allows_pickup) {
            // CHECK stores_fulfilment_chk akan menolak 500-an; tangkap di
            // sini supaya admin mendapat pesan yang bisa dibaca.
            return back()->withInput()->withErrors([
                'allows_pickup' => 'Toko harus melayani antar atau ambil di tempat.',
            ]);
        }

        if ($request->hasFile('photo')) {
            // Foto lama diganti SETELAH yang baru tersimpan — salah hapus
            // lebih mahal daripada berkas sisa.
            $store->photo = $request->file('photo')->store('stores', 'public');
        }

        $store->save();

        return redirect()
            ->route('admin.stores.edit', $store)
            ->with('success', "Toko {$store->name} berhasil disimpan.");
    }

    /**
     * Aksi cepat dari tabel — aturannya PERSIS antrian verifikasi:
     * service yang sama menjaga syarat & stempel tulis-sekali.
     */
    public function approve(Request $request, Store $store): RedirectResponse
    {
        return $this->terjemahkanHasil(
            $this->verifikasi->setujui($store, $request->user()->id),
            $store,
        );
    }

    public function reject(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            // Alasan wajib: tanpa itu pemilik tidak tahu apa yang salah.
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $ditolak = $this->verifikasi->tolak($store, $request->user()->id, $data['reason']);

        return $ditolak
            ? back()->with('success', "Toko {$store->name} ditolak — alasan terkirim ke pemilik.")
            : back()->with('error', "Toko {$store->name} sudah diproses sebelumnya — tidak bisa ditolak lagi.");
    }

    /** Satu penerjemah hasil agar kanal mana pun bicara dengan bahasa sama. */
    private function terjemahkanHasil(string $hasil, Store $store): RedirectResponse
    {
        return match ($hasil) {
            'bukan-antrian' => back()->with('error',
                "Toko {$store->name} sudah diproses sebelumnya — stempel persetujuan tidak bisa ditimpa."),
            'pemilik-belum-terverifikasi' => back()->with('error',
                "Toko {$store->name} belum bisa diverifikasi: pemiliknya belum terverifikasi identitas. Selesaikan dulu di antrian Verifikasi Pengguna."),
            'foto-belum-diunggah' => back()->with('error',
                "Toko {$store->name} belum bisa diverifikasi: foto tampak depan belum diunggah pemilik. Tolak pengajuannya agar pemilik memperbaiki."),
            default => back()->with('success', "Toko {$store->name} disetujui."),
        };
    }
}
