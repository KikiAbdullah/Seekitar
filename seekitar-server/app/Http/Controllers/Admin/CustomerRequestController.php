<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CustomerRequestsDataTable;
use App\Http\Controllers\Controller;
use App\Models\CustomerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.requests.index');
    }

    public function data(Request $request, CustomerRequestsDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function show(CustomerRequest $customerRequest): View
    {
        return view('admin.requests.show', [
            'request' => $customerRequest->load(['user', 'category', 'offers.store']),
        ]);
    }
}
