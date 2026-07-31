<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Http\Resources\OrderResource;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    use ApiResponse;

    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50'],
            'order_total' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::where('code', $data['code'])->first();

        if (!$coupon) {
            return $this->fail('Kupon tidak ditemukan', 404);
        }

        if (!$coupon->isValid()) {
            return $this->fail('Kupon tidak aktif atau sudah kedaluwarsa', 422);
        }

        /** @var User $user */
        $user = $request->user();

        if (!$coupon->canUse($user)) {
            return $this->fail('Batas pemakaian kupon telah tercapai', 422);
        }

        $discount = $coupon->calculateDiscount((float) $data['order_total']);

        return $this->ok([
            'coupon'   => new CouponResource($coupon),
            'discount' => $discount,
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'     => ['required', 'string', 'max:50'],
            'order_id' => ['required', 'string', 'exists:orders,id'],
        ]);

        $coupon = Coupon::where('code', $data['code'])->first();

        if (!$coupon) {
            return $this->fail('Kupon tidak ditemukan', 404);
        }

        if (!$coupon->isValid()) {
            return $this->fail('Kupon tidak aktif atau sudah kedaluwarsa', 422);
        }

        /** @var User $user */
        $user = $request->user();

        if (!$coupon->canUse($user)) {
            return $this->fail('Batas pemakaian kupon telah tercapai', 422);
        }

        $order = Order::where('id', $data['order_id'])
            ->where('buyer_id', $user->id)
            ->first();

        if (!$order) {
            return $this->fail('Pesanan tidak ditemukan', 404);
        }

        if ($coupon->min_order_amount !== null && (float) $order->total_amount < $coupon->min_order_amount) {
            return $this->fail('Total pesanan belum mencapai minimum kupon', 422);
        }

        try {
            $discount = DB::transaction(function () use ($coupon, $order, $user) {
                $discount = $coupon->apply($order, $user);

                $order->update([
                    'discount_amount' => $discount,
                    'coupon_id'       => $coupon->id,
                ]);

                return $discount;
            });
        } catch (\Throwable $e) {
            return $this->fail('Gagal menerapkan kupon: '.$e->getMessage(), 500);
        }

        $order->load('coupon');

        return $this->ok([
            'order'    => new OrderResource($order),
            'discount' => $discount,
        ]);
    }
}
