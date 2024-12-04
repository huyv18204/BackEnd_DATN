<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\OrderStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderStatusHistoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);
        if (!OrderStatus::isValidValue($request->input('status'))) {
            return response()->json([
                'message' => "Trạng thái không hợp lệ"
            ], 422);
        }
        try {
            OrderStatusHistory::query()->create([
                'order_id' => $request->input('order_id'),
                'status' => $request->input('status'),
            ]);

            return response()->json([
                'message' => "Tạo lịch sử trạng thái thành công"
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }


    }
}
