<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        return view('admin.subscriptions.index');
    }

    public function data(Request $request): JsonResponse
    {
        $q = Subscription::with(['user', 'store']);

        if ($request->filled('plan')) {
            $q->where('plan', $request->plan);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        return DataTables::of($q)
            ->addColumn('user', fn (Subscription $s) => view('admin.subscriptions._user', compact('s'))->render())
            ->addColumn('plan_label', fn (Subscription $s) => $s->planLabel())
            ->addColumn('status_badge', fn (Subscription $s) => view('admin.subscriptions._status', compact('s'))->render())
            ->addColumn('action', fn (Subscription $s) => view('admin.subscriptions._actions', compact('s'))->render())
            ->editColumn('amount', fn (Subscription $s) => 'Rp ' . number_format((int) $s->amount, 0, ',', '.'))
            ->editColumn('created_at', fn (Subscription $s) => $s->created_at?->format('d M Y H:i'))
            ->editColumn('starts_at', fn (Subscription $s) => $s->starts_at?->format('d M Y'))
            ->editColumn('ends_at', fn (Subscription $s) => $s->ends_at?->format('d M Y'))
            ->filterColumn('user', fn ($q, $keyword) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$keyword}%")))
            ->rawColumns(['user', 'status_badge', 'action'])
            ->make(true);
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['user', 'store']);

        return view('admin.subscriptions.show', ['subscription' => $subscription]);
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => 'cancelled']);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Langganan dibatalkan.');
    }
}
