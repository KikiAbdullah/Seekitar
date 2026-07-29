<?php
/**
 * Jalankan SELURUH seeder sungguhan terhadap SQLite in-memory.
 *
 * KENAPA PERLU
 * ------------
 * Seeder hanya bisa dipercaya kalau benar-benar dijalankan. Kesalahan yang
 * paling sering terjadi di lapisan ini semuanya lolos pemeriksaan statis:
 * relasi yang salah nama, kolom wajib yang tidak diisi, UNIQUE yang tertabrak
 * di baris ke-300, dan factory yang menyentuh basis data di definition().
 *
 * Sandbox ini tidak punya MySQL, jadi skemanya dibangun ulang dalam SQLite
 * dengan bentuk yang SEDEKAT MUNGKIN: tipe kolom, NOT NULL, UNIQUE, dan FK
 * ditegakkan sungguhan.
 *
 * ⚠️ YANG TIDAK DIBUKTIKAN DI SINI
 * --------------------------------
 * SQLite tidak punya POINT SRID 4326, SPATIAL INDEX, SET, maupun CHECK
 * bergaya MySQL. Kolom `location` karenanya ditampung sebagai TEXT, dan
 * pelanggaran CHECK constraint TIDAK akan tertangkap. Yang dibuktikan:
 * seeder berjalan sampai selesai, urutan FK benar, UNIQUE tidak tertabrak,
 * jumlah baris masuk akal, dan relasi antar-tabel tersambung.
 *
 * Verifikasi CHECK constraint tetap harus dilakukan di MySQL 8 sungguhan.
 *
 * Pemakaian:  ./tools/dev/php tools/dev/run-seeders.php
 */

$root = __DIR__.'/../../seekitar-server';

putenv('APP_ENV=testing');       $_ENV['APP_ENV']       = $_SERVER['APP_ENV']       = 'testing';
putenv('CACHE_STORE=array');     $_ENV['CACHE_STORE']   = $_SERVER['CACHE_STORE']   = 'array';
putenv('SESSION_DRIVER=array');  $_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'array';
putenv('QUEUE_CONNECTION=sync'); $_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';
putenv('DB_CONNECTION=sqlite');  $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
putenv('DB_DATABASE=:memory:');  $_ENV['DB_DATABASE']   = $_SERVER['DB_DATABASE']   = ':memory:';

// Volume kecil: yang diuji adalah JALANNYA seeder, bukan ketahanan mesin.
putenv('SEEKITAR_DEMO_SCALE='.(getenv('SKALA') ?: '0.12'));
$_ENV['SEEKITAR_DEMO_SCALE'] = $_SERVER['SEEKITAR_DEMO_SCALE'] = (getenv('SKALA') ?: '0.12');

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

DB::statement('PRAGMA foreign_keys = ON');

/*
 * ST_GeomFromText() adalah fungsi MySQL; SQLite tidak mengenalnya dan seeder
 * mati dengan "no such function".
 *
 * Didaftarkan sebagai fungsi buatan yang MENGEMBALIKAN WKT-nya apa adanya.
 * Dengan begitu isi kolom `location` tetap bisa diperiksa — termasuk apakah
 * opsi 'axis-order=long-lat' benar-benar terpasang, yang justru merupakan
 * salah satu hal terpenting yang ingin dibuktikan skrip ini.
 */
$pdo = DB::connection()->getPdo();

if (method_exists($pdo, 'sqliteCreateFunction')) {
    $pdo->sqliteCreateFunction(
        'ST_GeomFromText',
        static fn (?string $wkt = null, $srid = null, ?string $axis = null): string
            => sprintf('%s|srid=%s|%s', (string) $wkt, (string) $srid, (string) $axis),
        -1,
    );
} else {
    echo "PERINGATAN: sqliteCreateFunction tidak tersedia; kolom lokasi tidak bisa diperiksa.\n";
}

/*
 * Skema tiruan.
 *
 * Ditulis ulang, BUKAN dijalankan dari migrasi asli: migrasi memakai
 * SpatialSchema yang sengaja melempar bila drivernya bukan MySQL, serta
 * `$table->set()` yang tidak ada di SQLite. Menyalin bentuknya di sini
 * membuat perbedaan itu eksplisit alih-alih ditambal diam-diam.
 */
Schema::create('users', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->string('phone', 15)->unique();
    $t->string('email')->nullable()->unique();
    $t->timestamp('email_verified_at')->nullable();
    $t->string('password')->nullable();
    $t->string('name', 100);
    $t->string('avatar_url', 500)->nullable();
    $t->string('address', 255)->nullable();
    // TIDAK ADA verification_level: level turunan dari stempel (DATABASE.md §4.1).
    $t->string('ktp_image', 500)->nullable();
    $t->string('selfie_image', 500)->nullable();
    $t->timestamp('ktp_submitted_at')->nullable();
    $t->text('ktp_rejected_reason')->nullable();
    // Stempel dua tahap (FK longgar — mock tidak memaksa constrained()).
    $t->char('verified1_by', 36)->nullable();
    $t->timestamp('verified1_at')->nullable();
    $t->char('verified2_by', 36)->nullable();
    $t->timestamp('verified2_at')->nullable();
    // Reputasi pembeli — ditulis ReviewObserver (DATABASE.md §4.1).
    $t->decimal('rating_avg', 3, 2)->default(0);
    $t->unsignedInteger('total_reviews')->default(0);
    $t->string('nik', 255)->nullable();
    $t->char('nik_hash', 64)->nullable()->unique();
    $t->boolean('is_blocked')->default(false);
    $t->string('blocked_reason', 255)->nullable();
    $t->timestamp('blocked_at')->nullable();
    $t->text('location')->nullable();          // POINT di MySQL
    $t->rememberToken();
    $t->softDeletes();
    $t->timestamps();
});

Schema::create('categories', function (Blueprint $t) {
    $t->increments('id');
    $t->string('name', 50);
    $t->string('slug', 50)->unique();
    $t->unsignedInteger('parent_id')->nullable();
    $t->string('icon', 50)->nullable();
    $t->unsignedSmallInteger('sort_order')->default(0);
    $t->timestamps();
});

Schema::create('stores', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('user_id');
    $t->string('name', 100);
    $t->string('regency', 100);
    $t->char('regency_code', 4)->nullable();
    $t->string('store_type');                  // SET di MySQL
    $t->json('category_ids');
    $t->string('address', 255)->nullable();
    $t->decimal('service_radius_km', 5, 2)->default(5);
    $t->boolean('accepts_cod')->default(true);
    $t->boolean('offers_delivery')->default(false);
    $t->boolean('allows_pickup')->default(true);
    $t->json('operating_hours')->nullable();
    $t->string('npwp', 20)->nullable();
    $t->string('bank_account', 100)->nullable();
    $t->decimal('rating_avg', 3, 2)->default(0);
    $t->unsignedInteger('total_reviews')->default(0);
    $t->boolean('is_active')->default(true);
    $t->string('verification_status')->default('pending');
    $t->text('rejected_reason')->nullable();
    $t->timestamp('verified_at')->nullable();
    $t->char('verified_by', 36)->nullable();
    $t->text('location');                      // NOT NULL, seperti MySQL
    $t->softDeletes();
    $t->timestamps();
    $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
});

Schema::create('listings', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('store_id');
    $t->string('title', 200);
    $t->text('description');
    $t->string('listing_type');
    $t->decimal('price', 12, 2)->nullable();
    $t->unsignedInteger('stock_qty')->nullable();
    $t->unsignedTinyInteger('slot')->nullable();
    $t->json('images');
    $t->string('status')->default('active');
    $t->softDeletes();
    $t->timestamps();
    $t->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
});

Schema::create('customer_requests', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('user_id');
    $t->string('title', 200);
    $t->text('description');
    $t->unsignedInteger('category_id');
    $t->decimal('budget_min', 12, 2)->nullable();
    $t->decimal('budget_max', 12, 2)->nullable();
    $t->json('images')->nullable();
    $t->decimal('radius_km', 5, 2)->default(15);
    $t->timestamp('required_date')->nullable();
    $t->timestamp('expires_at');
    $t->timestamp('extended_at')->nullable();
    $t->unsignedTinyInteger('extension_count')->default(0);
    $t->string('status')->default('open');
    $t->uuid('accepted_offer_id')->nullable();
    $t->text('location');
    $t->timestamps();
    $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $t->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
});

Schema::create('offers', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('request_id');
    $t->uuid('store_id');
    $t->decimal('price', 12, 2);
    $t->decimal('additional_cost', 12, 2)->default(0);
    $t->string('additional_cost_note', 150)->nullable();
    $t->string('estimation_time', 100);
    $t->unsignedSmallInteger('estimated_hours')->nullable();
    $t->text('notes')->nullable();
    $t->string('status')->default('pending');
    $t->timestamp('expires_at');
    $t->timestamps();
    $t->unique(['request_id', 'store_id']);
    $t->foreign('request_id')->references('id')->on('customer_requests')->cascadeOnDelete();
    $t->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
});

Schema::create('orders', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->string('order_number', 20)->unique();
    $t->uuid('buyer_id');
    $t->uuid('store_id');
    $t->uuid('offer_id')->nullable();
    $t->uuid('listing_id')->nullable();
    $t->string('order_type');
    $t->unsignedInteger('quantity')->default(1);
    $t->decimal('total_amount', 12, 2);
    $t->string('status')->default('menunggu_konfirmasi');
    $t->string('payment_method');
    $t->string('delivery_method')->default('pickup');
    $t->text('shipping_address')->nullable();
    $t->string('payment_proof_url', 500)->nullable();
    $t->timestamp('payment_confirmed_at')->nullable();
    $t->text('notes')->nullable();
    $t->timestamp('completed_at')->nullable();
    $t->timestamp('cancelled_at')->nullable();
    $t->uuid('cancelled_by')->nullable();
    $t->string('cancel_reason', 255)->nullable();
    $t->text('shipping_location')->nullable();
    $t->timestamps();
    $t->foreign('buyer_id')->references('id')->on('users');
    $t->foreign('store_id')->references('id')->on('stores');
});

Schema::create('reviews', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('order_id');
    $t->uuid('reviewer_id');
    $t->uuid('reviewee_id');
    $t->uuid('store_id')->nullable();
    $t->string('direction');
    $t->unsignedTinyInteger('rating');
    $t->text('comment')->nullable();
    $t->timestamp('created_at')->nullable();
    $t->unique(['order_id', 'direction']);
    $t->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
});

Schema::create('disputes', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('order_id');
    $t->uuid('reported_by');
    $t->string('reason');
    $t->text('description')->nullable();
    $t->string('status')->default('open');
    $t->timestamp('response_deadline');
    $t->timestamp('first_responded_at')->nullable();
    $t->timestamp('escalated_at')->nullable();
    $t->uuid('assigned_to')->nullable();
    $t->text('resolution_note')->nullable();
    $t->timestamp('resolved_at')->nullable();
    $t->timestamps();
    $t->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
});

Schema::create('user_devices', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('user_id');
    $t->string('device_id', 100)->unique();
    $t->string('fcm_token', 255);
    $t->string('platform', 10);
    $t->timestamp('last_used_at')->nullable();
    $t->timestamps();
    $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
});

Schema::create('favorites', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('user_id');
    $t->uuid('listing_id');
    $t->timestamps();
    $t->unique(['user_id', 'listing_id']);
    $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $t->foreign('listing_id')->references('id')->on('listings')->cascadeOnDelete();
});

Schema::create('settings', function (Blueprint $t) {
    $t->string('key')->primary();
    $t->text('value')->nullable();
    $t->string('type', 20)->default('string');
    $t->string('group', 50)->default('umum');
    $t->string('label', 150);
    $t->timestamps();
});

// Tabel Spatie (kolom morph UUID, seperti migrasi aslinya).
Schema::create('permissions', function (Blueprint $t) {
    $t->bigIncrements('id');
    $t->string('name', 125);
    $t->string('guard_name', 125);
    $t->timestamps();
    $t->unique(['name', 'guard_name']);
});
Schema::create('roles', function (Blueprint $t) {
    $t->bigIncrements('id');
    $t->string('name', 125);
    $t->string('guard_name', 125);
    $t->timestamps();
    $t->unique(['name', 'guard_name']);
});
Schema::create('model_has_permissions', function (Blueprint $t) {
    $t->unsignedBigInteger('permission_id');
    $t->string('model_type');
    $t->uuid('model_uuid');
    $t->primary(['permission_id', 'model_uuid', 'model_type']);
});
Schema::create('model_has_roles', function (Blueprint $t) {
    $t->unsignedBigInteger('role_id');
    $t->string('model_type');
    $t->uuid('model_uuid');
    $t->primary(['role_id', 'model_uuid', 'model_type']);
});
Schema::create('role_has_permissions', function (Blueprint $t) {
    $t->unsignedBigInteger('permission_id');
    $t->unsignedBigInteger('role_id');
    $t->primary(['permission_id', 'role_id']);
});

echo "Skema tiruan siap (", count(Schema::getTableListing()), " tabel)\n\n";

// ───────────────────────────────────────────────────── Jalankan seeder

$mulai = microtime(true);

try {
    $seeder = $app->make(Database\Seeders\DatabaseSeeder::class);
    $seeder->setContainer($app);
    $seeder->__invoke();
} catch (Throwable $e) {
    echo "SEEDER GAGAL: ", $e::class, "\n  ", $e->getMessage(), "\n";
    echo "  di ", str_replace($root.'/', '', $e->getFile()), ':', $e->getLine(), "\n";
    exit(1);
}

$detik = round(microtime(true) - $mulai, 2);

// ───────────────────────────────────────────────────── Periksa hasilnya

$tabel = [
    'users', 'categories', 'stores', 'listings', 'customer_requests',
    'offers', 'orders', 'reviews', 'disputes', 'user_devices',
    'favorites', 'settings', 'roles', 'permissions',
];

echo "Jumlah baris per tabel ({$detik}s):\n";
$kosong = [];
foreach ($tabel as $t) {
    $n = DB::table($t)->count();
    printf("  %-20s %6d%s\n", $t, $n, $n === 0 ? '   <-- KOSONG' : '');
    if ($n === 0) {
        $kosong[] = $t;
    }
}

// ───────────────────────────────────────────────────── Uji integritas

echo "\nPemeriksaan integritas:\n";
$masalah = 0;
$cek = function (string $label, bool $lulus, string $rincian = '') use (&$masalah): void {
    printf("  %-52s %s%s\n", $label, $lulus ? 'OK' : 'GAGAL', $rincian ? "  ({$rincian})" : '');
    if (! $lulus) {
        $masalah++;
    }
};

$cek('semua tabel terisi', $kosong === [], implode(', ', $kosong));

// Aturan yang ditegakkan CHECK constraint di MySQL — diperiksa manual di sini
// karena SQLite tidak menegakkannya.
$langgarQtySlot = DB::table('listings')
    ->where(function ($q) {
        $q->whereIn('listing_type', ['product', 'rental'])
          ->where(fn ($x) => $x->whereNull('stock_qty')->orWhereNotNull('slot'));
    })
    ->orWhere(function ($q) {
        $q->where('listing_type', 'service')
          ->where(fn ($x) => $x->whereNull('slot')->orWhereNotNull('stock_qty'));
    })
    ->count();
$cek('listings: stock_qty XOR slot sesuai tipe', $langgarQtySlot === 0, "{$langgarQtySlot} baris");

$hargaKosong = DB::table('listings')->where('listing_type', '!=', 'service')->whereNull('price')->count();
$cek('listings: harga wajib untuk non-jasa', $hargaKosong === 0, "{$hargaKosong} baris");

$antarTanpaAlamat = DB::table('orders')->where('delivery_method', 'delivery')->whereNull('shipping_address')->count();
$cek('orders: diantar selalu punya alamat', $antarTanpaAlamat === 0, "{$antarTanpaAlamat} baris");

$arahSalah = DB::table('reviews')
    ->where(fn ($q) => $q->where('direction', 'buyer_to_store')->whereNull('store_id'))
    ->orWhere(fn ($q) => $q->where('direction', 'store_to_buyer')->whereNotNull('store_id'))
    ->count();
$cek('reviews: store_id sesuai arah', $arahSalah === 0, "{$arahSalah} baris");

$tokoTanpaLokasi = DB::table('stores')->whereNull('location')->count();
$cek('stores: location terisi (NOT NULL)', $tokoTanpaLokasi === 0, "{$tokoTanpaLokasi} baris");

$reqTanpaLokasi = DB::table('customer_requests')->whereNull('location')->count();
$cek('customer_requests: location terisi', $reqTanpaLokasi === 0, "{$reqTanpaLokasi} baris");

// WKT harus memakai axis-order=long-lat, kalau tidak MySQL menolak bujur
// Indonesia dengan ERROR 3617.
$wktSalah = DB::table('stores')->where('location', 'not like', '%axis-order=long-lat%')->count();
$cek('stores: WKT memakai axis-order=long-lat', $wktSalah === 0, "{$wktSalah} baris");

$nomorGanda = DB::table('orders')->select('order_number')
    ->groupBy('order_number')->havingRaw('COUNT(*) > 1')->get()->count();
$cek('orders: order_number unik', $nomorGanda === 0, "{$nomorGanda} duplikat");

$tanpaNomor = DB::table('orders')->whereNull('order_number')->orWhere('order_number', '')->count();
$cek('orders: order_number selalu terisi observer', $tanpaNomor === 0, "{$tanpaNomor} baris");

// Rating toko dihitung ulang ReviewObserver — nilainya harus cocok.
$ratingMeleset = 0;
foreach (DB::table('stores')->where('total_reviews', '>', 0)->get() as $s) {
    $nyata = DB::table('reviews')
        ->where('store_id', $s->id)->where('direction', 'buyer_to_store')
        ->selectRaw('COUNT(*) n, COALESCE(AVG(rating),0) r')->first();

    if ((int) $nyata->n !== (int) $s->total_reviews
        || abs((float) $nyata->r - (float) $s->rating_avg) > 0.011) {
        $ratingMeleset++;
    }
}
$cek('stores: rating_avg cocok dengan ulasan', $ratingMeleset === 0, "{$ratingMeleset} toko");

$slaLewat = DB::table('disputes')
    ->where('status', 'open')->whereNull('first_responded_at')
    ->where('response_deadline', '<', now())->count();
$cek('disputes: ada laporan lewat SLA untuk dasbor', $slaLewat > 0, "{$slaLewat} baris");

// Definisi antrian TUNGGAL (User::pendingVerification): pengajuan terbuka,
// bukan filter level — level memang bukan kolom lagi.
$ktpAntri = DB::table('users')->whereNotNull('ktp_submitted_at')->whereNull('verified2_at')->count();
$cek('users: antrian verifikasi KTP terisi', $ktpAntri > 0, "{$ktpAntri} baris");

$tokoMenunggu = DB::table('stores')->where('verification_status', 'pending')->count();
$cek('stores: antrian verifikasi toko terisi', $tokoMenunggu > 0, "{$tokoMenunggu} baris");

$diblokir = DB::table('users')->where('is_blocked', true)->count();
$cek('users: ada yang diblokir (filter admin)', $diblokir > 0, "{$diblokir} baris");

// Setiap nilai ENUM status pesanan harus terwakili, kalau tidak filter panel
// admin punya pilihan yang selalu kosong.
$statusAda = DB::table('orders')->distinct()->pluck('status')->all();
$statusKurang = array_diff(App\Enums\OrderStatus::values(), $statusAda);
$cek('orders: semua status ENUM terwakili', $statusKurang === [], implode(', ', $statusKurang));

$statusReq = DB::table('customer_requests')->distinct()->pluck('status')->all();
$reqKurang = array_diff(App\Enums\RequestStatus::values(), $statusReq);
$cek('customer_requests: semua status terwakili', $reqKurang === [], implode(', ', $reqKurang));

// Idempoten: menjalankan ulang seeder produksi tidak boleh menggandakan.
$sebelum = ['categories' => DB::table('categories')->count(), 'settings' => DB::table('settings')->count()];
$app->make(Database\Seeders\CategorySeeder::class)->setContainer($app)->__invoke();
$app->make(Database\Seeders\SettingSeeder::class)->setContainer($app)->__invoke();
$sesudah = ['categories' => DB::table('categories')->count(), 'settings' => DB::table('settings')->count()];
$cek('seeder produksi idempoten', $sebelum === $sesudah,
    "kategori {$sebelum['categories']}→{$sesudah['categories']}, settings {$sebelum['settings']}→{$sesudah['settings']}");

echo "\n{$masalah} masalah\n";
exit($masalah === 0 ? 0 : 1);
