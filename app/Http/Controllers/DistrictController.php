<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index(): JsonResponse
    {
        $districts = District::all();
        return response()->json($districts);
    }

    public function show($code): JsonResponse
    {
        try {
            $district = District::query()->where('code', $code)->first();
            if (!$district) {
                return response()->json([
                    'message' => 'Quận không tồn tại'
                ]);
            }
            return response()->json($district);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

    }


    public function updateDeliveryFee(Request $request, $id): JsonResponse
    {
        $validate = $request->validate([
            'shipping_fee' => 'required|numeric'
        ]);

        try {
            $district = District::query()->find($id);
            $district->update([
                'shipping_fee' => $validate['shipping_fee']
            ]);
            return response()->json([
                'message' => "Cập nhật phí giao hàng thành công"
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }
}
