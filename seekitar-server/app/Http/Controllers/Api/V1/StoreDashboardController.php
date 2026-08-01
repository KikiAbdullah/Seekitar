<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

/**
 * Dashboard ringkasan toko untuk pemiliknya.
 */
class StoreDashboardController extends Controller
{
    use ApiResponse;

    /** GET /stores/{store}/dashboard */
    public function show(Store $store): JsonResponse
    {
        if ($store->user_id !== request()->user()->id) {
            abort(404);
        }

        $now = now();

        $totalListings = Listing::where('store_id', $store->id)
            ->where('status', ListingStatus::Active->value)
            ->count();

        $totalOrders = Order::where('store_id', $store->id)->count();

        $completedOrders = Order::where('store_id', $store->id)
            ->where('status', OrderStatus::Selesai->value)
            ->count();

        $pendingOrders = Order::where('store_id', $store->id)
            ->whereIn('status', [
                OrderStatus::MenungguKonfirmasi->value,
                OrderStatus::Diproses->value,
            ])
            ->count();

        $totalRevenue = (float) Order::where('store_id', $store->id)
            ->where('status', OrderStatus::Selesai->value)
            ->sum('total_amount');

        $avgRating = (float) Review::where('store_id', $store->id)
            ->whereNotNull('rating')
            ->avg('rating') ?: 0;

        $totalReviews = Review::where('store_id', $store->id)->count();

        // Pesanan bulan ini
        $ordersThisMonth = Order::where('store_id', $store->id)
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->count();

        $revenueThisMonth = (float) Order::where('store_id', $store->id)
            ->where('status', OrderStatus::Selesai->value)
            ->where('completed_at', '>=', $now->copy()->startOfMonth())
            ->sum('total_amount');

        return $this->ok([
            'total_listings'    => $totalListings,
            'total_orders'      => $totalOrders,
            'completed_orders'  => $completedOrders,
            'pending_orders'    => $pendingOrders,
            'total_revenue'     => $totalRevenue,
            'avg_rating'        => round($avgRating, 1),
            'total_reviews'     => $totalReviews,
            'orders_this_month' => $ordersThisMonth,
            'revenue_this_month' => $revenueThisMonth,
            'is_verified'       => $store->verified_at !== null,
            'is_active'         => (bool) $store->is_active,
        ]);
    }
}
