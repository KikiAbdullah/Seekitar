<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ListingStatus;
use App\Enums\StoreStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Autocomplete & saran pencarian untuk search bar mobile app.
 */
class SearchController extends Controller
{
    use ApiResponse;

    /** GET /search/suggestions */
    public function suggestions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q'    => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['sometimes', 'in:product,service,rental'],
        ]);

        $query = Listing::query()
            ->where('status', ListingStatus::Active->value)
            ->whereHas('store', fn ($q) => $q
                ->where('is_active', true)
                ->where('status', StoreStatus::Verified->value));

        // LIKE dengan prefix untuk indeks, bukan fulltext: pencarian singkat
        // 2-3 karakter tidak cocok untuk fulltext search.
        $keyword = $data['q'];
        $query->where('title', 'like', $keyword . '%');

        if (isset($data['type'])) {
            $query->where('listing_type', $data['type']);
        }

        $suggestions = $query->select('id', 'title', 'listing_type', 'price', 'images')
            ->distinct('title')
            ->orderBy('title')
            ->limit(8)
            ->get()
            ->map(fn (Listing $l) => [
                'id'       => $l->id,
                'title'    => $l->title,
                'type'     => $l->listing_type->value,
                'price'    => $l->price,
                'image'    => $l->images[0] ?? null,
            ]);

        return $this->ok($suggestions);
    }
}
