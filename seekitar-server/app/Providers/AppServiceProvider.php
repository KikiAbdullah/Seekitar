<?php

namespace App\Providers;

use App\Services\Contracts\WhatsAppGateway;
use App\Services\WhatsApp\KirimWaGateway;
use App\Services\WhatsApp\LogWhatsAppGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
    }

    public function boot(): void
    {
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
}
