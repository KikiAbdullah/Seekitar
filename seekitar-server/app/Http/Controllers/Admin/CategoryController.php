<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::with('children')
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(),
            'parents'  => Category::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori dibuat.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            // Kategori tidak boleh menjadi induk dirinya sendiri.
            'parents'  => Category::whereNull('parent_id')
                ->whereKeyNot($category->id)
                ->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori diperbarui.');
    }

    /**
     * `stores.category_ids` berupa JSON dan TIDAK dilindungi foreign key,
     * jadi pemakaiannya diperiksa manual (DATABASE.md §4.3).
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Pindahkan atau hapus subkategori terlebih dahulu.');
        }

        if ($category->customerRequests()->exists()) {
            return back()->with('error', 'Kategori masih dipakai permintaan.');
        }

        if (Store::whereRaw('JSON_CONTAINS(category_ids, ?)', [(string) $category->id])->exists()) {
            return back()->with('error', 'Kategori masih dipakai toko.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori dihapus.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/',
                       Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id'  => ['nullable', 'integer', 'exists:categories,id',
                             Rule::notIn([$category?->id])],
            'icon'       => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
