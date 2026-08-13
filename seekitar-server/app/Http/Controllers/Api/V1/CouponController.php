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
use Illuminate\Database\QueryException;

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

        /** @var User $user */
        $user = $request->user();

        try {
            [$order, $discount] = DB::transaction(function () use ($data, $user): array {
                // Kunci kupon dan pesanan sebelum SEMUA pemeriksaan kuota.
                // Tanpa ini dua request paralel bisa sama-sama lolos lalu
                // memakai kupon melampaui limit.
                $coupon = Coupon::where('code', $data['code'])->lockForUpdate()->first();
                if (! $coupon) {
                    throw new \DomainException('Kupon tidak ditemukan.');
                }

                $order = Order::whereKey($data['order_id'])
                    ->where('buyer_id', $user->id)
                    ->lockForUpdate()
                    ->first();
                if (! $order) {
                    throw new \DomainException('Pesanan tidak ditemukan.');
                }
                if ($order->coupon_id !== null) {
                    throw new \LogicException('Pesanan ini sudah memakai kupon.');
                }
                if (! $coupon->isValid() || ! $coupon->canUse($user)) {
                    throw new \LogicException('Kupon tidak aktif, kedaluwarsa, atau batas pemakaiannya telah tercapai.');
                }
                if ($coupon->min_order_amount !== null && (float) $order->total_amount < (float) $coupon->min_order_amount) {
                    throw new \InvalidArgumentException('Total pesanan belum mencapai minimum kupon.');
                }

                $discount = $coupon->apply($order, $user);

                $order->update([
                    'discount_amount' => $discount,
                    'coupon_id'       => $coupon->id,
                ]);

                return [$order, $discount];
            });
        } catch (\DomainException) {
            return $this->fail('Kupon tidak ditemukan.', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\LogicException $e) {
            return $this->fail($e->getMessage(), 409);
        } catch (QueryException) {
            // Constraint unik adalah perlindungan kedua terhadap request
            // paralel/retry. Jangan pernah memantulkan pesan SQL ke klien.
            return $this->fail('Kupon sudah digunakan untuk pesanan ini.', 409);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Gagal menerapkan kupon. Silakan coba kembali.', 500);
        }

        $order->load('coupon');

        return $this->ok([
            'order'    => new OrderResource($order),
            'discount' => $discount,
        ]);
    }
}
