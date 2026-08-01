<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

/**
 * Kategori publik — dipakai mobile app untuk picker & filter.
 *
 * Endpoint ini tidak butuh token karena kategori dibutuhkan bahkan sebelum
 * pengguna login: layar "pilih kategori" muncul di form pencarian katalog,
 * form pasang listing, dan form permintaan.
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /** GET /categories — publik, tanpa token. */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        return $this->ok(CategoryResource::collection($categories));
    }
}
