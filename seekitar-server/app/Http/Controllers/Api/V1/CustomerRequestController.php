<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RequestStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomerRequestRequest;
use App\Http\Requests\Api\UpdateCustomerRequestRequest;
use App\Http\Resources\CustomerRequestResource;
use App\Http\Resources\OfferResource;
use App\Models\CustomerRequest;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerRequestController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SettingService $settings) {}

    /**
     * GET /requests — "Kebutuhan Sekitar" bagi penyedia.
     *
     * Hanya permintaan yang masih terbuka dan belum kedaluwarsa.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'radius'   => ['sometimes', 'numeric', 'min:0.1'],
            'category' => ['sometimes', 'integer', 'exists:categories,id'],
            'sort'     => ['sometimes', 'in:newest,nearest,expiring'],
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];
        $radius = min(
            (float) ($data['radius'] ?? 15),
            (float) $this->settings->int('max_search_radius_km', 25),
        );

        $query = CustomerRequest::query()
            ->withCoordinates()
            ->with('user')
            ->withCount('offers')
            ->where('status', RequestStatus::Open)
            ->where('expires_at', '>', now())
            // Permintaan sendiri tidak perlu muncul di daftar penyedia.
            ->where('user_id', '!=', $request->user()->id)
            ->nearby($lat, $lng, $radius)
            ->withDistance($lat, $lng);

        if (isset($data['category'])) {
            $query->where('category_id', $data['category']);
        }

        match ($data['sort'] ?? 'newest') {
            'nearest'  => $query->orderByDistance(),
            'expiring' => $query->orderBy('expires_at'),
            default    => $query->latest(),
        };

        return $this->paginated($query->paginate($this->perPage()), CustomerRequestResource::class);
    }

    /** GET /requests/mine — "Permintaan Saya" bagi pembeli. */
    public function mine(Request $request): JsonResponse
    {
        $query = CustomerRequest::query()
            ->withCoordinates()
            ->withCount('offers')
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->paginated($query->paginate($this->perPage()), CustomerRequestResource::class);
    }

    /** GET /requests/{customerRequest} */
    public function show(CustomerRequest $customerRequest): JsonResponse
    {
        $fresh = CustomerRequest::withCoordinates()
            ->with('user')
            ->withCount('offers')
            ->whereKey($customerRequest->getKey())
            ->firstOrFail();

        return $this->ok(['request' => new CustomerRequestResource($fresh)]);
    }

    /**
     * POST /requests
     *
     * `expires_at` dihitung SERVER dari pengaturan, bukan dikirim klien —
     * kalau tidak, klien bisa membuat permintaan yang tidak pernah kedaluwarsa.
     */
    public function store(StoreCustomerRequestRequest $request): JsonResponse
    {
        $customerRequest = new CustomerRequest($request->safe()->except(['latitude', 'longitude']));

        $customerRequest->user_id    = $request->user()->id;
        $customerRequest->radius_km  = $request->validated('radius_km')
            ?? $this->settings->int('default_request_radius_km', 15);
        $customerRequest->status     = RequestStatus::Open;
        $customerRequest->expires_at = now()->addHours(
            $this->settings->int('request_expiry_hours', 24)
        );

        $customerRequest->setLocation(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        )->save();

        return $this->created(['request' => new CustomerRequestResource($customerRequest->fresh())]);
    }

    /** PATCH /requests/{customerRequest} */
    public function update(UpdateCustomerRequestRequest $request, CustomerRequest $customerRequest): JsonResponse
    {
        $this->authorize('update', $customerRequest);

        $customerRequest->fill($request->safe()->except(['latitude', 'longitude']));

        if ($request->hasAny(['latitude', 'longitude'])) {
            $customerRequest->setLocation(
                (float) $request->validated('latitude'),
                (float) $request->validated('longitude'),
            );
        }

        $customerRequest->save();

        return $this->ok(['request' => new CustomerRequestResource($customerRequest->fresh())]);
    }

    /** DELETE /requests/{customerRequest} */
    public function destroy(CustomerRequest $customerRequest): JsonResponse
    {
        $this->authorize('delete', $customerRequest);

        if ($customerRequest->status === RequestStatus::Closed) {
            return $this->fail('Permintaan yang sudah selesai tidak dapat dihapus.', 422);
        }

        $customerRequest->delete();

        return $this->noContent();
    }

    /** POST /requests/{customerRequest}/extend */
    public function extend(CustomerRequest $customerRequest): JsonResponse
    {
        $this->authorize('extend', $customerRequest);

        $max = $this->settings->int('max_request_extensions', 2);

        if ($customerRequest->extension_count >= $max) {
            return $this->fail("Batas perpanjangan ($max kali) sudah tercapai.", 422);
        }

        // Diperpanjang dari SEKARANG, bukan dari expires_at lama: permintaan
        // yang sudah lewat tidak boleh "hidup lagi" ke masa lalu.
        $customerRequest->expires_at = now()->addHours(
            $this->settings->int('request_expiry_hours', 24)
        );
        $customerRequest->extended_at = now();
        $customerRequest->increment('extension_count');
        $customerRequest->save();

        return $this->ok(['request' => new CustomerRequestResource($customerRequest->fresh())]);
    }

    /** GET /requests/{customerRequest}/offers — hanya pemilik permintaan. */
    public function offers(Request $request, CustomerRequest $customerRequest): JsonResponse
    {
        $this->authorize('update', $customerRequest);

        $sort = $request->query('sort', 'cheapest');

        $query = $customerRequest->offers()->with('store');

        // Pengurutan WAJIB di sisi server: klien hanya menerima satu halaman,
        // jadi mengurutkan di klien hanya mengurutkan halaman itu (PRD §5.2.4).
        match ($sort) {
            'best_rating' => $query->join('stores', 'stores.id', '=', 'offers.store_id')
                ->select('offers.*')->orderByDesc('stores.rating_avg'),
            'fastest'     => $query->orderByRaw('estimated_hours IS NULL, estimated_hours ASC'),
            default       => $query->orderByRaw('(price + additional_cost) ASC'),
        };

        return $this->paginated($query->paginate($this->perPage()), OfferResource::class);
    }
}
