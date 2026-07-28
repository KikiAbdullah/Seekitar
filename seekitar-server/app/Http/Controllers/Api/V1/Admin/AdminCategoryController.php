<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    use ApiResponse;

    /** GET /admin/categories */
    public function index(): JsonResponse
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return $this->ok(['categories' => CategoryResource::collection($categories)]);
    }

    /** POST /admin/categories */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        return $this->created(['category' => new CategoryResource(Category::create($data))]);
    }

    /** PUT /admin/categories/{category} */
    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $this->validated($request, $category);

        // Kategori tidak boleh menjadi induk dirinya sendiri.
        if (($data['parent_id'] ?? null) === $category->id) {
            return $this->fail('Kategori tidak bisa menjadi induk dirinya sendiri.', 422);
        }

        $category->update($data);

        return $this->ok(['category' => new CategoryResource($category)]);
    }

    /**
     * DELETE /admin/categories/{category}
     *
     * `stores.category_ids` berupa JSON dan TIDAK terlindungi foreign key,
     * jadi pemakaiannya harus diperiksa manual sebelum menghapus
     * (DATABASE.md §4.3).
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return $this->fail('Pindahkan atau hapus subkategori terlebih dahulu.', 422);
        }

        if ($category->customerRequests()->exists()) {
            return $this->fail('Kategori masih dipakai permintaan.', 422);
        }

        $usedByStore = Store::whereRaw('JSON_CONTAINS(category_ids, ?)', [(string) $category->id])->exists();

        if ($usedByStore) {
            return $this->fail('Kategori masih dipakai toko.', 422);
        }

        $category->delete();

        return $this->ok(null, 'Kategori dihapus.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'slug' => [
                'required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ],
            'parent_id'  => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'icon'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
    }
}
