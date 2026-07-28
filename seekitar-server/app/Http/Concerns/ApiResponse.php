<?php

namespace App\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Amplop respons JSON yang seragam (API_DOCUMENTATION.md §1).
 *
 * Tanpa trait ini tiap controller menyusun struktur sendiri, dan klien mobile
 * harus menangani beberapa bentuk respons untuk hal yang sama.
 *
 * Bentuk baku:
 *   sukses → { success: true, data: ..., message: "OK" }
 *   gagal  → { success: false, message: "...", errors: {...}|null }
 */
trait ApiResponse
{
    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        // `array_filter` dengan pengecekan !== null, bukan truthy: tanpa itu
        // data yang sah seperti 0, '', atau [] ikut terbuang dari respons.
        return response()->json(array_filter([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], static fn ($v) => $v !== null), $status);
    }

    protected function created(mixed $data, string $message = 'Data berhasil dibuat'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    /** 204 tidak boleh punya body — Laravel menolak isi apa pun di sini. */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function fail(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Paginator → data + meta seragam (§12.2).
     *
     * @param  class-string<JsonResource>|null  $resource
     */
    protected function paginated(LengthAwarePaginator $paginator, ?string $resource = null): JsonResponse
    {
        $items = $paginator->items();

        return response()->json([
            'success' => true,
            'data'    => $resource ? $resource::collection($items)->resolve() : $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Jumlah baris per halaman, dibatasi maksimum (§12.2).
     *
     * Tanpa batas atas, `?per_page=100000` menarik seluruh tabel dalam satu
     * request — cara termudah menjatuhkan server.
     */
    protected function perPage(int $default = 15, int $max = 50): int
    {
        $requested = (int) request()->integer('per_page', $default);

        return max(1, min($requested ?: $default, $max));
    }
}
