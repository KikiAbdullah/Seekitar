<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserAddressRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    use ApiResponse;

    /** GET /addresses */
    public function index(Request $request): JsonResponse
    {
        $addresses = UserAddress::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->ok(UserAddressResource::collection($addresses));
    }

    /** POST /addresses */
    public function store(StoreUserAddressRequest $request): JsonResponse
    {
        $address = new UserAddress($request->safe()->all());
        $address->user_id = $request->user()->id;
        $address->save();

        return $this->created(new UserAddressResource($address->fresh()));
    }

    /** PATCH /addresses/{address} */
    public function update(StoreUserAddressRequest $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return $this->fail('Alamat tidak ditemukan.', 404);
        }

        $address->fill($request->safe()->all());
        $address->save();

        return $this->ok(new UserAddressResource($address->fresh()));
    }

    /** DELETE /addresses/{address} */
    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return $this->fail('Alamat tidak ditemukan.', 404);
        }

        $address->delete();

        return $this->noContent();
    }

    /** PATCH /addresses/{address}/default */
    public function setDefault(Request $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return $this->fail('Alamat tidak ditemukan.', 404);
        }

        $address->is_default = true;
        $address->save();

        return $this->ok(new UserAddressResource($address->fresh()));
    }
}
