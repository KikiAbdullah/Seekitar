<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('admin.blog.index', [
            'posts' => BlogPost::latest()->paginate(20),
        ]);
    }

    public function data(Request $request)
    {
        $posts = BlogPost::query();

        if ($search = $request->input('search.value')) {
            $posts->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $posts->where('category', $category);
        }

        $recordsTotal = BlogPost::count();
        $recordsFiltered = $posts->count();

        $posts->orderBy(
            $request->input('columns.' . $request->input('order.0.column') . '.data', 'created_at'),
            $request->input('order.0.dir', 'desc')
        );

        $perPage = $request->input('length', 10);
        $start = $request->input('start', 0);

        $data = $posts->skip($start)->take($perPage)->get()->map(function ($post) {
            return [
                'id'           => $post->id,
                'title'        => $post->title,
                'category'     => $post->category,
                'author'       => $post->author,
                'published_at' => $post->published_at?->format('d M Y H:i'),
                'status'       => $post->published_at && $post->published_at <= now()
                    ? '<span class="badge bg-success-subtle text-success">Terbit</span>'
                    : ($post->published_at
                        ? '<span class="badge bg-warning-subtle text-warning">Terjadwal</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary">Draf</span>'),
                'created_at'   => $post->created_at->format('d M Y H:i'),
                'action'       => view('admin.blog._actions', ['post' => $post])->render(),
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 0),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.form', ['post' => new BlogPost()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        BlogPost::create($data);

        return redirect()->route('admin.blog.index')
            ->with('success', 'Artikel blog dibuat.');
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.form', ['post' => $post]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $post->update($this->validated($request, $post));

        return redirect()->route('admin.blog.index')
            ->with('success', 'Artikel blog diperbarui.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.blog.index')
            ->with('success', 'Artikel blog dihapus.');
    }

    private function validated(Request $request, ?BlogPost $post = null): array
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'slug'         => ['nullable', 'string', 'max:220',
                               Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'excerpt'      => ['nullable', 'string', 'max:500'],
            'body'         => ['required', 'string'],
            'author'       => ['nullable', 'string', 'max:100'],
            'category'     => ['required', 'string', 'max:100'],
            'image'        => ['nullable', 'string', 'max:500'],
            'image_alt'    => ['nullable', 'string', 'max:200'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Jika body diberikan tanpa excerpt, excerpt diisi otomatis
        // dari potongan body — model accessor sudah menanganinya.

        $data['author'] ??= 'Tim Seekitar';

        return $data;
    }
}
