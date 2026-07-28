<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStoreRequest;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SettingService $settings) {}

    /**
     * GET /stores/nearby
     *
     * Radius dibatasi `max_search_radius_km` supaya satu request tidak
     * memindai seluruh kabupaten.
     */
    public function nearby(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'    => ['required', 'numeric', 'between:-90,90'],
            'lng'    => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'numeric', 'min:0.1'],
            'type'   => ['sometimes', 'in:goods,services,rental'],
        ]);

        $maxRadius = $this->settings->int('max_search_radius_km', 25);
        $radius    = min((float) ($data['radius'] ?? $maxRadius), $maxRadius);

        $query = Store::query()
            ->withCoordinates()
            ->where('is_active', true)
            ->nearby((float) $data['lat'], (float) $data['lng'], $radius)
            ->withDistance((float) $data['lat'], (float) $data['lng'])
            ->orderByDistance();

        if (isset($data['type'])) {
            // FIND_IN_SET, bukan LIKE: kolom SET harus dicocokkan per nilai,
            // kalau tidak 'goods' ikut cocok dengan 'goods_bekas'.
            $query->whereRaw('FIND_IN_SET(?, store_type)', [$data['type']]);
        }

        return $this->paginated($query->paginate($this->perPage()), StoreResource::class);
    }

    /** GET /stores/{store} */
    public function show(Store $store): JsonResponse
    {
        $store = Store::withCoordinates()->whereKey($store->getKey())->firstOrFail();

        return $this->ok(['store' => new StoreResource($store)]);
    }

    /** POST /stores — butuh verification_level >= 2. */
    public function store(StoreStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Store::class);

        $user = $request->user();

        $store = new Store($request->safe()->except(['latitude', 'longitude']));
        $store->user_id      = $user->id;
        $store->regency      = config('seekitar.regency');
        $store->regency_code = config('seekitar.regency_code');

        $store->setLocation(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        )->save();

        return $this->created(['store' => new StoreResource($store->fresh())]);
    }

    /** GET /stores/{store}/reviews */
    public function reviews(Store $store): JsonResponse
    {
        $reviews = $store->reviews()
            ->with('reviewer')
            ->latest()
            ->paginate($this->perPage());

        return $this->paginated($reviews, ReviewResource::class);
    }
}
