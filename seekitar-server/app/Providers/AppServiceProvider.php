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
use App\Models\CustomerRequest;
use App\Models\Order;
use App\Models\Review;
use App\Policies\CustomerRequestPolicy;
use App\Observers\OrderObserver;
use App\Observers\ReviewObserver;
use App\Services\ActivityLogger;
use App\Services\CacheService;
use App\Services\Contracts\NotificationSender;
use App\Services\Contracts\WhatsAppGateway;
use App\Services\Notifications\LogNotificationSender;
use App\Services\WhatsApp\BaileysGateway;
use App\Services\WhatsApp\EmailOtpGateway;
use App\Services\WhatsApp\KirimWaGateway;
use App\Services\WhatsApp\LogWhatsAppGateway;
use App\View\Composers\SidebarComposer;
use App\Support\PhoneNumber;
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
        // Driver WhatsApp: log (dev), email, kirimwa (provider berbayar),
        // atau baileys (gateway WhatsApp Web lokal — lihat config/whatsapp.php
        // dan seekitar-server/whatsapp-gateway/README.md).
        $this->app->bind(WhatsAppGateway::class, function () {
            $driver = strtolower((string) config('whatsapp.driver', 'log'));

            if ($this->app->isProduction()) {
                // Gagal cepat: tanpa konfigurasi yang benar, setiap permintaan
                // OTP akan gagal dan tidak ada yang bisa masuk.
                return match ($driver) {
                    'baileys' => $this->requireBaileysConfig(new BaileysGateway()),
                    'kirimwa' => $this->requireKirimwaConfig(new KirimWaGateway()),
                    default   => throw new RuntimeException(
                        "WHATSAPP_DRIVER='{$driver}' tidak valid untuk produksi; pakai 'baileys' atau 'kirimwa'."
                    ),
                };
            }

            return match ($driver) {
                'baileys' => new BaileysGateway(),
                'email'   => new EmailOtpGateway(),
                'kirimwa' => new KirimWaGateway(),
                default   => new LogWhatsAppGateway(),
            };
        });

        // Pengirim notifikasi. Implementasi FCM sungguhan belum ada — lihat
        // catatan di LogNotificationSender; kontraknya sudah tetap sehingga
        // penggantinya cukup di-bind di sini.
        $this->app->bind(NotificationSender::class, LogNotificationSender::class);

        // Pencatat aktivitas — dapat diresolusi lewat DI di mana saja.
        $this->app->singleton(ActivityLogger::class);

        // Cache terpusat dengan dukungan grup — singleton agar state
        // (daftar key per grup) konsisten sepanjang request.
        $this->app->singleton(CacheService::class);

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
        // URL di-generate dengan skema HTTPS di produksi (asset, redirect,
        // signed route, dsb.) — memastikan tidak ada tautan http:// bocor.
        if (app()->isProduction()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

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
        Gate::policy(CustomerRequest::class, CustomerRequestPolicy::class);

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

        // OTP dibatasi PER NOMOR (ternormalisasi), bukan per IP: kalau per IP,
        // satu orang bisa memanen OTP dengan berganti jaringan, dan sebaliknya
        // pengguna satu WiFi kantor akan saling memblokir. Kunci memakai
        // PhoneNumber::normalize supaya `0812…` dan `62812…` dihitung sebagai
        // nomor yang SAMA — throttle berjalan SEBELUM FormRequest menormalkan.
        //
        // Di luar produksi batas dilonggarkan supaya pengembangan tidak gampang
        // kena 429 (mis. tes alur OTP berulang); produksi tetap 3/menit & 10/hari.
        if (app()->isProduction()) {
            $otpPerMinute = 3;
            $otpPerDay    = 10;
            $verifyPerMinute = 5;
        } else {
            $otpPerMinute = 10;
            $otpPerDay    = 100;
            $verifyPerMinute = 20;
        }

        $phone = static fn (Request $request) => PhoneNumber::normalize($request->input('phone'))
            ?? (string) $request->input('phone');

        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute($otpPerMinute)->by('otp:'.$phone($request)),
            Limit::perDay($otpPerDay)->by('otp-daily:'.$phone($request)),
        ]);

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute($verifyPerMinute)
            ->by('otp-verify:'.$phone($request)));

        RateLimiter::for('offers', fn (Request $request) => Limit::perMinute(30)
            ->by('offers:'.$request->user()?->id));

        // Contact form: limit per IP to prevent spam
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)
            ->by('contact:'.$request->ip()));

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

    /** Produksi + Baileys: URL & token wajib terisi agar OTP bisa terkirim. */
    private function requireBaileysConfig(BaileysGateway $gateway): BaileysGateway
    {
        foreach (['whatsapp.baileys.url', 'whatsapp.baileys.token'] as $key) {
            if (blank(config($key))) {
                throw new RuntimeException("Konfigurasi {$key} kosong; OTP produksi (Baileys) tidak bisa dikirim.");
            }
        }

        return $gateway;
    }

    /** Produksi + Kirim WA: kredensial wajib terisi agar OTP bisa terkirim. */
    private function requireKirimwaConfig(KirimWaGateway $gateway): KirimWaGateway
    {
        foreach (['services.kirimwa.url', 'services.kirimwa.token'] as $key) {
            if (blank(config($key))) {
                throw new RuntimeException("Konfigurasi {$key} kosong; OTP produksi tidak bisa dikirim.");
            }
        }

        return $gateway;
    }
}
