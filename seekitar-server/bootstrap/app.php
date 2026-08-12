<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
        then: function (): void {
            // Route admin dipisah agar prefix & middleware-nya tidak
            // tercampur dengan web publik.
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias Spatie — inilah yang membuat 'permission:manage-users'
        // bisa dipakai langsung di definisi route.
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'store.owner'        => \App\Http\Middleware\EnsureStoreOwner::class,
            'profile.complete'   => \App\Http\Middleware\EnsureProfileComplete::class,
            'user.active'        => \App\Http\Middleware\EnsureUserNotBlocked::class,
            'https'              => \App\Http\Middleware\EnsureHttps::class,
        ]);

        // Terminasi HTTPS di produksi (redirect + HSTS). Aman di dev (no-op).
        $middleware->append(\App\Http\Middleware\EnsureHttps::class);

        // Di belakang reverse proxy/load balancer (nginx, Cloudflare, dsb.):
        // percayai header X-Forwarded-* agar isSecure()/ip()/scheme benar.
        // Ganti dengan daftar IP proxy bila perlu; '*' = percaya semua proxy.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

        // Autentikasi berbasis cookie HANYA untuk domain di
        // SANCTUM_STATEFUL_DOMAINS (web admin). Aplikasi mobile memakai
        // Bearer token dan TIDAK boleh masuk daftar itu — kalau masuk,
        // Sanctum menuntut CSRF token dan request gagal dengan 419.
        $middleware->statefulApi();

        $middleware->api(append: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        // Tamu yang membuka halaman admin diarahkan ke login panel, bukan
        // ke route 'login' bawaan Laravel yang tidak ada di proyek ini.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Sebaliknya, admin yang sudah masuk tidak perlu melihat halaman
        // login lagi.
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * Semua error API memakai amplop yang sama seperti respons sukses
         * (API_DOCUMENTATION.md §1). Tanpa ini, klien harus menangani dua
         * bentuk berbeda: milik aplikasi dan milik Laravel.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;   // biarkan web admin memakai halaman error biasa
            }

            [$status, $message, $errors] = match (true) {
                $e instanceof ValidationException => [
                    422, 'Data yang dikirim tidak valid.', $e->errors(),
                ],
                $e instanceof AuthenticationException => [
                    401, 'Silakan masuk terlebih dahulu.', null,
                ],
                $e instanceof AuthorizationException => [
                    403, $e->getMessage() ?: 'Anda tidak berhak melakukan tindakan ini.', null,
                ],
                // Model tidak ditemukan tampil sebagai 404 biasa; membocorkan
                // nama kelas model tidak berguna bagi klien.
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => [
                    404, 'Data tidak ditemukan.', null,
                ],
                // Business rule violations — let this match BEFORE generic
                // HttpExceptionInterface since BusinessException extends HttpException.
                $e instanceof \App\Exceptions\BusinessException => [
                    $e->getStatusCode(), $e->getMessage(), null,
                ],
                // Rate limiting
                $e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException => [
                    429, 'Terlalu banyak permintaan. Silakan coba beberapa saat lagi.', null,
                ],
                // HTTP 409 Conflict
                $e instanceof \Symfony\Component\HttpKernel\Exception\ConflictHttpException => [
                    409, $e->getMessage() ?: 'Konflik data.', null,
                ],
                // HTTP 410 Gone
                $e instanceof \Symfony\Component\HttpKernel\Exception\GoneHttpException => [
                    410, $e->getMessage() ?: 'Data sudah tidak tersedia.', null,
                ],
                // HTTP 423 Locked
                $e instanceof \Symfony\Component\HttpKernel\Exception\LockedHttpException => [
                    423, $e->getMessage() ?: 'Sumber daya terkunci.', null,
                ],
                // HTTP 429 Too Many Requests (backup)
                $e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException => [
                    429, 'Terlalu banyak permintaan.', null,
                ],
                // Generic HTTP exception catch-all — must come after all
                // specific Symfony/Illuminate HTTP exceptions above.
                $e instanceof HttpExceptionInterface => [
                    $e->getStatusCode(), $e->getMessage() ?: 'Permintaan gagal diproses.', null,
                ],
                // Database query error
                $e instanceof \Illuminate\Database\QueryException => [
                    500, app()->isProduction() ? 'Kesalahan basis data.' : $e->getMessage(), null,
                ],
                default => [500, 'Terjadi kesalahan pada server.', null],
            };

            // Detail kesalahan tak terduga hanya dibuka di luar produksi.
            if ($status === 500 && ! app()->isProduction()) {
                $errors = ['exception' => [$e::class.': '.$e->getMessage()]];
            }

            // 429: simpan header Retry-After / X-RateLimit-* dari exception
            $response = response()->json([
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ], $status);

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
                || $e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                foreach ($e->getHeaders() as $k => $v) {
                    $response->headers->set($k, $v);
                }
            }

            return $response;
        });

        $exceptions->report(function (Throwable $e) {
            if ($e instanceof \Illuminate\Database\QueryException && $e->getCode() === '23000') {
                Log::channel('security')->warning('Integrity constraint violation', [
                    'message' => $e->getMessage(),
                    'sql'     => $e->getSql(),
                ]);
            }
        });
    })->create();
