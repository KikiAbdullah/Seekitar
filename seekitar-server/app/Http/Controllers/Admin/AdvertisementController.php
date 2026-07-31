<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdvertisementController extends Controller
{
    public function index(): View
    {
        return view('admin.advertisements.index');
    }

    public function data(Request $request): JsonResponse
    {
        $q = Advertisement::with(['buyer']);

        if ($request->filled('position')) {
            $q->where('position', $request->position);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        return DataTables::of($q)
            ->addColumn('buyer_name', fn (Advertisement $a) => $a->buyer?->name ?? '—')
            ->addColumn('position_label', fn (Advertisement $a) => $a->positionLabel())
            ->addColumn('action', fn (Advertisement $a) => view('admin.advertisements._actions', compact('a'))->render())
            ->editColumn('price_per_day', fn (Advertisement $a) => 'Rp ' . number_format((int) $a->price_per_day, 0, ',', '.'))
            ->editColumn('created_at', fn (Advertisement $a) => $a->created_at?->format('d M Y H:i'))
            ->editColumn('starts_at', fn (Advertisement $a) => $a->starts_at?->format('d M Y') ?: '—')
            ->editColumn('ends_at', fn (Advertisement $a) => $a->ends_at?->format('d M Y') ?: '—')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(): View
    {
        return view('admin.advertisements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'image_url'    => ['nullable', 'url', 'max:500'],
            'link_url'     => ['nullable', 'url', 'max:500'],
            'position'     => ['required', 'string', 'in:feed,sidebar,search,category'],
            'price_per_day'=> ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'string', 'in:available,active,inactive'],
        ]);

        Advertisement::create($validated);

        return redirect()->route('admin.advertisements.index')
            ->with('success', 'Iklan berhasil dibuat.');
    }

    public function edit(Advertisement $advertisement): View
    {
        return view('admin.advertisements.edit', ['ad' => $advertisement]);
    }

    public function update(Request $request, Advertisement $advertisement): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'image_url'    => ['nullable', 'url', 'max:500'],
            'link_url'     => ['nullable', 'url', 'max:500'],
            'position'     => ['required', 'string', 'in:feed,sidebar,search,category'],
            'price_per_day'=> ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'string', 'in:available,active,inactive'],
        ]);

        $advertisement->update($validated);

        return redirect()->route('admin.advertisements.index')
            ->with('success', 'Iklan diperbarui.');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        $advertisement->delete();

        return redirect()->route('admin.advertisements.index')
            ->with('success', 'Iklan dihapus.');
    }
}
