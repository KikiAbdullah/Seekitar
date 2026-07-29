<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerRequest;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Dasbor panel admin (Server_Implementation_Guide.md §9.1).
 *
 * PRINSIP: dasbor menampilkan APA YANG BOLEH DILIHAT, bukan semuanya.
 *
 * Sebuah kartu KPI adalah data — "ada 4.812 pengguna" tetap membocorkan
 * ukuran basis pengguna kepada admin yang tidak punya `manage-users`.
 * Karena itu setiap blok dihitung hanya bila izinnya ada, dan query-nya pun
 * TIDAK dijalankan bila tidak berhak: menyembunyikannya di Blade saja berarti
 * server tetap membayar biaya COUNT untuk data yang tidak akan ditampilkan.
 *
 * `Gate::allows()` dipakai alih-alih `$user->can()` supaya pemeriksaannya
 * lewat pintu yang sama dengan `@can` di Blade — termasuk `Gate::before`
 * milik super-admin.
 */
class DashboardController extends Controller
{
    /** Jumlah baris pada tabel ringkas. Cukup untuk sekilas pandang. */
    private const RINGKAS_LIMIT = 5;

    public function index(): View
    {
        $stats = $this->stats();

        return view('admin.dashboard', [
            'stats'      => $stats,
            'sorotan'    => $this->sorotan($stats),
            'antrian'    => $this->antrian(),
            'ringkas'    => $this->ringkas(),
            'chartHari'  => $this->chartRange(),
        ]);
    }

    /**
     * Satu angka untuk kartu sambutan.
     *
     * Kartu itu selalu tampil, termasuk bagi admin yang izinnya sedikit, jadi
     * angkanya TIDAK boleh dipatok ke satu metrik tertentu — `stats()` bisa
     * saja kosong sama sekali. Yang dipakai adalah KPI pertama yang memang
     * berhak dilihat pengguna ini; kalau tidak ada satu pun, kartu tetap utuh
     * dengan nilai nol dan label netral, bukan variabel yang tidak terdefinisi.
     *
     * Dihitung di sini, bukan di Blade, supaya view tidak perlu tahu urutan
     * prioritas KPI.
     *
     * @param  array<string, array{label: string, value: int, icon: string, tone: string, hint: string, url: string|null}>  $stats
     * @return array{label: string, nilai: int}
     */
    private function sorotan(array $stats): array
    {
        $pertama = reset($stats);

        if ($pertama === false) {
            return ['label' => 'Belum ada data', 'nilai' => 0];
        }

        return ['label' => $pertama['label'], 'nilai' => $pertama['value']];
    }

    /**
     * Kartu KPI.
     *
     * Bentuknya array berisi label, nilai, ikon, dan nada warna — bukan angka
     * telanjang — supaya Blade tidak perlu memetakan ulang nama kunci ke label
     * dan ikon. Menaruh pemetaan itu di view berarti menambah KPI harus
     * menyentuh dua berkas.
     *
     * @return array<string, array{label: string, value: int, icon: string, tone: string, hint: string, url: string|null}>
     */
    private function stats(): array
    {
        $cards = [];

        if (Gate::allows('manage-users')) {
            $cards['users'] = [
                'label' => 'Total Pengguna',
                'value' => User::count(),
                'icon'  => 'ti-users',
                'tone'  => 'primary',
                'hint'  => User::whereDate('created_at', today())->count().' baru hari ini',
                'url'   => route('admin.users.index'),
            ];
        }

        if (Gate::allows('manage-stores')) {
            $cards['stores'] = [
                'label' => 'Toko Aktif',
                'value' => Store::where('is_active', true)
                    ->where('verification_status', VerificationStatus::Verified)
                    ->count(),
                'icon'  => 'ti-building-store',
                'tone'  => 'primary',
                'hint'  => Store::count().' toko terdaftar',
                'url'   => route('admin.stores.index'),
            ];
        }

        if (Gate::allows('manage-orders')) {
            // "Bulan ini" sesuai §9.1 — angka harian terlalu berfluktuasi
            // untuk dijadikan ukuran sekilas.
            $bulanIni = Order::where('created_at', '>=', now()->startOfMonth())->count();

            $cards['orders'] = [
                'label' => 'Pesanan Bulan Ini',
                'value' => $bulanIni,
                'icon'  => 'ti-shopping-cart',
                'tone'  => 'info',
                'hint'  => Order::whereDate('created_at', today())->count().' hari ini',
                'url'   => route('admin.orders.index'),
            ];
        }

        if (Gate::allows('manage-requests')) {
            $cards['requests'] = [
                'label' => 'Permintaan Terbuka',
                'value' => CustomerRequest::where('status', RequestStatus::Open)
                    ->where('expires_at', '>', now())
                    ->count(),
                'icon'  => 'ti-clipboard-list',
                'tone'  => 'secondary',
                'hint'  => 'kedaluwarsa otomatis 24 jam',
                'url'   => route('admin.requests.index'),
            ];
        }

        if (Gate::allows('manage-disputes')) {
            // Yang ditonjolkan adalah yang SUDAH lewat SLA, bukan semua yang
            // terbuka: kalau semuanya dihitung, angka ini selalu merah dan
            // berhenti menjadi peringatan.
            $lewatSla = $this->laporanLewatSla();

            $cards['disputes'] = [
                'label' => 'Laporan Lewat SLA',
                'value' => $lewatSla,
                'icon'  => 'ti-alert-triangle',
                'tone'  => $lewatSla > 0 ? 'danger' : 'primary',
                'hint'  => Dispute::where('status', DisputeStatus::Open)->count().' laporan terbuka',
                'url'   => route('admin.disputes.index'),
            ];
        }

        if (Gate::allows('manage-reviews')) {
            $cards['reviews'] = [
                'label' => 'Ulasan Masuk',
                'value' => Review::where('created_at', '>=', now()->startOfMonth())->count(),
                'icon'  => 'ti-star',
                'tone'  => 'warning',
                'hint'  => 'bulan berjalan',
                'url'   => route('admin.reviews.index'),
            ];
        }

        if (Gate::allows('manage-listings')) {
            $cards['listings'] = [
                'label' => 'Listing Aktif',
                'value' => Listing::where('status', \App\Enums\ListingStatus::Active)->count(),
                'icon'  => 'ti-package',
                'tone'  => 'info',
                'hint'  => Listing::count().' total listing',
                'url'   => route('admin.listings.index'),
            ];
        }

        if (Gate::allows('manage-offers')) {
            $cards['offers'] = [
                'label' => 'Penawaran Menunggu',
                'value' => Offer::where('status', OfferStatus::Pending)
                    ->where('expires_at', '>', now())
                    ->count(),
                'icon'  => 'ti-discount-2',
                'tone'  => 'secondary',
                'hint'  => 'belum diputuskan pembeli',
                'url'   => route('admin.offers.index'),
            ];
        }

        return $cards;
    }

    /**
     * Antrian kerja — hal yang MENUNGGU TINDAKAN admin.
     *
     * Dipisahkan dari kartu KPI karena maknanya berbeda: KPI menjawab
     * "bagaimana keadaannya", antrian menjawab "apa yang harus saya kerjakan
     * sekarang".
     *
     * @return array<int, array{label: string, value: int, url: string, tone: string, icon: string}>
     */
    private function antrian(): array
    {
        $items = [];

        if (Gate::allows('verify-users')) {
            $items[] = [
                'label' => 'KTP menunggu verifikasi',
                'value' => $this->ktpMenunggu(),
                'url'   => route('admin.verifications.users'),
                'tone'  => 'warning',
                'icon'  => 'ti-id',
            ];
        }

        if (Gate::allows('verify-stores')) {
            $items[] = [
                'label' => 'Toko menunggu peninjauan',
                'value' => Store::where('verification_status', VerificationStatus::Pending)->count(),
                'url'   => route('admin.verifications.stores'),
                'tone'  => 'warning',
                'icon'  => 'ti-building-store',
            ];
        }

        if (Gate::allows('manage-disputes')) {
            $items[] = [
                'label' => 'Laporan terbuka',
                'value' => Dispute::where('status', DisputeStatus::Open)->count(),
                'url'   => route('admin.disputes.index'),
                'tone'  => 'danger',
                'icon'  => 'ti-alert-triangle',
            ];
        }

        if (Gate::allows('manage-orders')) {
            $items[] = [
                'label' => 'Pesanan dalam sengketa',
                'value' => Order::where('status', OrderStatus::Dispute)->count(),
                'url'   => route('admin.orders.index').'?status='.OrderStatus::Dispute->value,
                'tone'  => 'danger',
                'icon'  => 'ti-shopping-cart',
            ];
        }

        return $items;
    }

    /**
     * Tabel ringkas: 5 permintaan & 5 penawaran terbaru (§9.1).
     *
     * Kolomnya dibatasi `select()` — dasbor tidak butuh deskripsi lengkap,
     * dan menariknya berarti memuat TEXT panjang untuk lima baris yang hanya
     * menampilkan judul.
     *
     * @return array{requests: \Illuminate\Support\Collection, offers: \Illuminate\Support\Collection}
     */
    private function ringkas(): array
    {
        $requests = collect();
        $offers   = collect();

        if (Gate::allows('manage-requests')) {
            $requests = CustomerRequest::query()
                ->with(['user:id,name', 'category:id,name'])
                ->select(['id', 'user_id', 'category_id', 'title', 'status', 'created_at'])
                ->withCount('offers')
                ->latest()
                ->limit(self::RINGKAS_LIMIT)
                ->get();
        }

        if (Gate::allows('manage-offers')) {
            $offers = Offer::query()
                ->with(['store:id,name', 'request:id,title'])
                ->select(['id', 'request_id', 'store_id', 'price', 'additional_cost', 'status', 'created_at'])
                ->latest()
                ->limit(self::RINGKAS_LIMIT)
                ->get();
        }

        return ['requests' => $requests, 'offers' => $offers];
    }

    /**
     * Data grafik permintaan & pesanan baru per hari.
     *
     * Endpoint TERPISAH dari halaman (§9.1) supaya dasbor tidak menunggu
     * agregasi selesai sebelum menampilkan apa pun.
     */
    public function chartData(Request $request): JsonResponse
    {
        // Rentang dibatasi: `?days=3650` akan memindai seluruh tabel dan
        // menghasilkan 3.650 label yang tak terbaca.
        $days = max(7, min(90, $request->integer('days', $this->chartRange())));

        $from   = now()->subDays($days - 1)->startOfDay();
        $labels = collect(range($days - 1, 0))->map(fn (int $i) => now()->subDays($i)->format('Y-m-d'));

        $datasets = [];

        if (Gate::allows('manage-requests')) {
            $datasets[] = [
                'label' => 'Permintaan Baru',
                'data'  => $this->seriesHarian(CustomerRequest::query(), $from, $labels),
                'color' => '#2563EB',
            ];
        }

        if (Gate::allows('manage-orders')) {
            $datasets[] = [
                'label' => 'Pesanan Baru',
                'data'  => $this->seriesHarian(Order::query(), $from, $labels),
                'color' => '#168A4A',
            ];
        }

        return response()->json([
            // Label dibaca manusia ("Sel, 28 Jul"), bukan tanggal ISO.
            // translatedFormat() butuh locale Carbon 'id' — disetel di
            // AppServiceProvider, karena config('app.locale') tetap 'en'.
            'labels'   => $labels->map(fn (string $d) => Carbon::parse($d)->translatedFormat('D, d M'))->values(),
            'datasets' => $datasets,
        ]);
    }

    /**
     * Satu deret harian, sudah terisi nol pada hari kosong.
     *
     * Tanpa pengisian ini grafik "melompat": Chart.js menyambungkan dua
     * tanggal yang berjauhan seolah keduanya berurutan.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $labels
     * @return array<int, int>
     */
    private function seriesHarian(\Illuminate\Database\Eloquent\Builder $query, Carbon $from, $labels): array
    {
        $rows = $query
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS total')
            ->groupBy('d')
            ->pluck('total', 'd');

        return $labels->map(fn (string $d) => (int) ($rows[$d] ?? 0))->values()->all();
    }

    /** Rentang default grafik, dalam hari. */
    private function chartRange(): int
    {
        return 14;
    }

    /**
     * KTP yang menunggu peninjauan.
     *
     * Memakai scope pendingVerification, SATU-SATUNYA definisi antrian:
     * berkas sudah dikirim tapi stempel tahap 2 belum ada. Penolakan
     * mengosongkan `ktp_submitted_at` (lihat VerificationController),
     * sehingga pengajuan yang ditolak tidak ikut terhitung sampai
     * dikirim ulang.
     */
    private function ktpMenunggu(): int
    {
        return User::pendingVerification()->count();
    }

    /**
     * Laporan yang SLA-nya sudah lewat.
     *
     * Sama persis dengan definisi `Dispute::isOverdue()`: terbuka, BELUM
     * pernah direspons, dan tenggatnya lewat. Menghilangkan syarat
     * `first_responded_at` akan menghitung laporan yang sudah ditangani
     * tetapi belum ditutup — itu bukan pelanggaran SLA.
     */
    private function laporanLewatSla(): int
    {
        return Dispute::where('status', DisputeStatus::Open)
            ->whereNull('first_responded_at')
            ->where('response_deadline', '<', now())
            ->count();
    }
}
