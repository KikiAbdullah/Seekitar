<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use App\Events\CustomerRequestCreated;
use App\Events\OfferAccepted;
use App\Events\OrderStatusChanged;
use App\Listeners\DispatchRequestBroadcast;
use App\Listeners\SendOfferAcceptedNotification;
use App\Listeners\SendOrderStatusNotification;
use App\Models\Order;
use App\Models\Review;
use App\Observers\OrderObserver;
use App\Observers\ReviewObserver;
use App\Services\Contracts\NotificationSender;
use App\Services\Contracts\WhatsAppGateway;
use App\Services\Notifications\LogNotificationSender;
use App\Services\WhatsApp\KirimWaGateway;
use App\Services\WhatsApp\LogWhatsAppGateway;
use App\View\Composers\SidebarComposer;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Di luar produksi, OTP ditulis ke log alih-alih dikirim — supaya
        // pengembang tidak butuh kredensial provider berbayar hanya untuk
        // bisa masuk (Server_Implementation_Guide.md §15.2).
        $this->app->bind(WhatsAppGateway::class, function () {
            if (! $this->app->isProduction()) {
                return new LogWhatsAppGateway();
            }

            // Gagal cepat: tanpa kredensial, setiap permintaan OTP akan
            // menghasilkan 401 dari provider dan tidak ada yang bisa masuk.
            // Lebih baik ketahuan saat boot daripada saat pengguna login.
            foreach (['services.kirimwa.url', 'services.kirimwa.token'] as $key) {
                if (blank(config($key))) {
                    throw new RuntimeException("Konfigurasi {$key} kosong; OTP produksi tidak bisa dikirim.");
                }
            }

            return new KirimWaGateway();
        });

        // Pengirim notifikasi. Implementasi FCM sungguhan belum ada — lihat
        // catatan di LogNotificationSender; kontraknya sudah tetap sehingga
        // penggantinya cukup di-bind di sini.
        $this->app->bind(NotificationSender::class, LogNotificationSender::class);

        /*
         * Migrasi bawaan Sanctum memakai tokenable_id BIGINT — tidak cocok
         * dengan users UUID, sehingga proyek ini membawa versi uuidMorphs-nya
         * sendiri: 2026_07_27_100050_create_personal_access_tokens_table.php.
         *
         * Tidak ada Sanctum::ignoreMigrations() di sini: metode itu DIHAPUS
         * di Sanctum 4.x karena paketnya kini tidak pernah memuat migrasinya
         * sendiri — hanya MENERBITKAN (publish). Artinya tabel UUID kita
         * otomatis menjadi satu-satunya yang dijalankan migrator.
         *
         * Konsekuensinya: JANGAN jalankan
         * `php artisan vendor:publish --tag=sanctum-migrations` — perintah itu
         * menyalin migrasi BIGINT bawaan ke database/migrations dan membuat
         * tabel personal_access_tokens ganda bertabrakan dengan milik kita.
         */
    }

    public function boot(): void
    {
        /*
         * Laravel sejak v11 merender ->links() dengan view Tailwind — panel
         * ini Bootstrap 5, hasilnya pagination "rusak": daftar tanpa gaya
         * dan panah SVG raksasa (contoh: /admin/verifications/users).
         * Saklar global ini mengganti SELURUH ->links() ke view Bootstrap,
         * sehingga halaman pagination baru di masa depan otomatis ikut benar
         * tanpa perlu diingat satu per satu.
         */
        Paginator::useBootstrapFive();

        $this->configureRateLimiting();
        $this->registerObservers();
        $this->registerEventListeners();
        $this->registerAuthorization();
        $this->registerViewComposers();

        // Tanggal berbahasa Indonesia di seluruh antarmuka.
        //
        // config('app.locale') sengaja TIDAK diubah ke 'id': Laravel akan
        // mencari berkas terjemahan lang/id/*.php yang tidak ada, dan seluruh
        // pesan validasi bawaan berubah menjadi kunci mentah seperti
        // "validation.required". Yang dibutuhkan hanya nama hari & bulan,
        // dan itu milik Carbon — bukan milik locale aplikasi.
        Carbon::setLocale('id');

        // Mass-assignment tak terdefinisi dan akses relasi malas ditolak di
        // pengembangan, sehingga bug ketahuan lebih awal — bukan di produksi
        // sebagai query N+1 yang diam-diam memperlambat.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Query lambat dicatat agar regresi indeks tidak lolos tanpa jejak.
        // Ambangnya longgar; yang dicari adalah query yang jelas bermasalah.
        if (! $this->app->isProduction()) {
            DB::whenQueryingForLongerThan(500, function ($connection, $event) {
                logger()->warning('Query lambat', [
                    'sql' => $event->sql,
                    'ms'  => $event->time,
                ]);
            });
        }
    }

    /**
     * Otorisasi lintas-Policy.
     *
     * Tanpa `Gate::before`, setiap Policy harus mengulang pengecualian untuk
     * super-admin. Satu pintu lebih aman daripada belasan pemeriksaan yang
     * bisa terlupa saat Policy baru ditambahkan
     * (Server_Implementation_Guide.md §6.3).
     */
    private function registerAuthorization(): void
    {
        Gate::before(function ($user, string $ability) {
            // WAJIB null, BUKAN false. Mengembalikan false memutus rantai:
            // Policy tidak akan pernah dipanggil dan SEMUA orang selain
            // super-admin ditolak, termasuk pemilik datanya sendiri.
            //
            // hasRole() berasal dari Spatie; pengguna API bisa saja model
            // lain, jadi keberadaan methodnya diperiksa dulu.
            if (! method_exists($user, 'hasRole')) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });
    }

    /**
     * Data yang dibutuhkan SETIAP halaman admin.
     *
     * Sidebar tampil di semua halaman; mengirim angka lencananya dari tiap
     * controller berarti 12 tempat mengulang query yang sama, dan satu yang
     * lupa membuat lencana hilang di halaman itu saja.
     */
    private function registerViewComposers(): void
    {
        View::composer(
            ['admin.partials.sidebar', 'admin.partials.header'],
            SidebarComposer::class,
        );
    }

    /**
     * Observer dipakai untuk efek samping yang SELALU terjadi, apa pun jalur
     * masuknya — API, panel admin, seeder, atau tinker.
     */
    private function registerObservers(): void
    {
        Order::observe(OrderObserver::class);
        Review::observe(ReviewObserver::class);
    }

    /** Pemetaan event → listener (Server_Implementation_Guide §13). */
    private function registerEventListeners(): void
    {
        // Audit login admin (§18A.5). Dicatat ke kanal terpisah supaya jejak
        // serangan tidak tenggelam di antara log debug aplikasi.
        Event::listen(Failed::class, function (Failed $event): void {
            Log::channel('security')->warning('Login admin gagal', [
                'email' => $event->credentials['email'] ?? null,
                'ip'    => request()->ip(),
            ]);
        });

        Event::listen(Lockout::class, function () : void {
            Log::channel('security')->alert('Login admin diblokir sementara', [
                'ip' => request()->ip(),
            ]);
        });

        /*
         * Rating DITANGANI ReviewObserver secara sinkron — jangan tambahkan
         * listener rating di sini. Event ReviewSubmitted beserta job-nya
         * pernah menghitung rating kedua kalinya secara asinkron, dan
         * event itu sendiri tidak pernah dipancarkan dari mana pun.
         */
        Event::listen(CustomerRequestCreated::class, DispatchRequestBroadcast::class);
        Event::listen(OfferAccepted::class, SendOfferAcceptedNotification::class);
        Event::listen(OrderStatusChanged::class, SendOrderStatusNotification::class);
    }

    /**
     * Batas laju sesuai API_DOCUMENTATION.md §1.
     *
     * OTP dibatasi PER NOMOR, bukan per IP: kalau per IP, satu orang bisa
     * memanen OTP dengan berganti jaringan, dan sebaliknya pengguna satu
     * WiFi kantor akan saling memblokir.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(3)->by('otp:'.$request->input('phone')),
            Limit::perDay(10)->by('otp-daily:'.$request->input('phone')),
        ]);

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(5)
            ->by('otp-verify:'.$request->input('phone')));

        RateLimiter::for('offers', fn (Request $request) => Limit::perMinute(30)
            ->by('offers:'.$request->user()?->id));

        // Login admin memakai kata sandi — sasaran empuk tebak-paksa, jadi
        // dibatasi DUA sumbu (§18A.5):
        //   - per akun+IP: menahan serangan pada satu akun
        //   - per IP saja: menahan password spraying ke banyak akun
        // Batas per-IP lebih longgar karena beberapa admin bisa berbagi satu
        // IP kantor.
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by('admin-login:'.$request->input('email').'|'.$request->ip()),
            Limit::perMinute(20)->by('admin-login-ip:'.$request->ip()),
        ]);
    }
}
