<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingAddress\ShippingAddressRequest;
use App\Models\District;
use App\Models\Province;
use App\Models\ShippingAddress;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShippingAddressController extends Controller
{
    protected function findOrCreateLocation($validated): array
    {
        $province = Province::query()->firstOrCreate(
            ['code' => $validated['province']['code']],
            $validated['province']
        );

        $district = District::query()->firstOrCreate(
            ['code' => $validated['district']['code']],
            [
                'name' => $validated['district']['name'],
                'code' => $validated['district']['code'],
                'province_code' => $province->code,
            ]
        );

        $ward = Ward::query()->firstOrCreate(
            ['code' => $validated['ward']['code']],
            [
                'name' => $validated['ward']['name'],
                'code' => $validated['ward']['code'],
                'district_code' => $district->code,
            ]
        );

        return [
            'province_code' => $province->code,
            'district_code' => $district->code,
            'ward_code' => $ward->code,
        ];
    }

    public function store(ShippingAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $locationCodes = $this->findOrCreateLocation($validated);

            $user = JWTAuth::parseToken()->authenticate();
            $is_default = $validated['is_default'] ?? false;

            if ($is_default) {
                $this->unsetDefaultAddress($user->id);
            }

            $shippingAddress = ShippingAddress::query()->create(array_merge($locationCodes, [
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_address' => $validated['recipient_address'],
                'user_id' => $user->id,
                'is_default' => $is_default,
            ]));

            if (!$shippingAddress) {
                throw new \Exception('Thêm địa chỉ giao hàng thất bại');
            }

            DB::commit();

            return response()->json([
                'message' => "Thêm địa chỉ giao hàng thành công"
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function update(ShippingAddressRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $locationCodes = $this->findOrCreateLocation($validated);

            $shippingAddress = ShippingAddress::query()->findOrFail($id);
            $is_default = $validated['is_default'] ?? false;

            if ($is_default) {
                $this->unsetDefaultAddress($shippingAddress->user_id);
            }
            $response = $shippingAddress->update(array_merge($locationCodes, [
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_address' => $validated['recipient_address'],
                'is_default' => $is_default,
            ]));

            if (!$response) {
                throw new \Exception('Cập nhật địa chỉ giao hàng thất bại');
            }

            DB::commit();

            return response()->json([
                'message' => "Cập nhật địa chỉ giao hàng thành công"
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    protected function unsetDefaultAddress($userId): void
    {
        $addressDefault = ShippingAddress::query()->where('user_id', $userId)->where('is_default', true)->first();
        if ($addressDefault) {
            $addressDefault->is_default = false;
            $addressDefault->save();
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $shippingAddress = ShippingAddress::query()->find($id);
            if (!$shippingAddress) {
                throw new \Exception('Địa chỉ đặt hàng không tồn tại');
            }
            $shippingAddress->delete();
            return response()->json(['message' => "Xoá địa chỉ đơn hàng thành công"]);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

    }

    public function getByUserId(Request $request): JsonResponse{
        $user = JWTAuth::parseToken()->authenticate();
        $size = $request->query('size');
        try {
            $shippingAddress = ShippingAddress::query()->where('user_id', $user->id);
            $shippingAddress = $size ? $shippingAddress->paginate($size) : $shippingAddress->get();
            return response()->json($shippingAddress);
        }catch (\Exception $exception){
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }


    public function show(Request $request, int $id): JsonResponse{
        try {
            $shippingAddress = ShippingAddress::query()->find($id);
            return response()->json($shippingAddress);
        }catch (\Exception $exception){
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
