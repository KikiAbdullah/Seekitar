<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ListingStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Wishlist pribadi (API §4.4). */
class FavoriteController extends Controller
{
    use ApiResponse;

    /** GET /favorites — terbaru lebih dulu. */
    public function index(Request $request): JsonResponse
    {
        $listings = Listing::query()
            ->with('store')
            ->join('favorites', 'favorites.listing_id', '=', 'listings.id')
            ->where('favorites.user_id', $request->user()->id)
            ->select('listings.*')
            ->orderByDesc('favorites.created_at')
            ->paginate($this->perPage());

        return $this->paginated($listings, ListingResource::class);
    }

    /**
     * POST /listings/{listing}/favorite
     *
     * IDEMPOTEN: memfavoritkan yang sudah difavoritkan tetap 200, bukan 409.
     * Tombol toggle di klien bisa mengirim ulang karena jaringan tidak
     * stabil, dan itu tidak boleh dianggap error (API §4.4).
     */
    public function store(Request $request, Listing $listing): JsonResponse
    {
        Favorite::firstOrCreate([
            'user_id'    => $request->user()->id,
            'listing_id' => $listing->id,
        ]);

        return $this->ok(null, 'Ditambahkan ke wishlist.');
    }

    /** DELETE /listings/{listing}/favorite — juga idempoten. */
    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        Favorite::where('user_id', $request->user()->id)
            ->where('listing_id', $listing->id)
            ->delete();

        return $this->ok(null, 'Dihapus dari wishlist.');
    }
}
