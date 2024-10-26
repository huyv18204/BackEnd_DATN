<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;

class OrderDetailsController extends Controller
{
    public function show(Request $request, $id)
    {
        $size = $request->query('size');
        $order = Order::query()->find($id);
        if (!$order) {
            return response()->json([
                "message" => "Đơn hàng không tồn tại"
            ], 404);
        }

        $query = OrderDetail::query()->where('order_id', $id);

        $query->when($request->query('minPrice'), function ($query, $minPrice) {
            $query->where('total_amount', '>=', $minPrice);
        });

        $query->when($request->query('maxPrice'), function ($query, $maxPrice) {
            $query->where('total_amount', '<=', $maxPrice);
        });

        $query->when($request->query('size_name'), function ($query, $sizeName) {
            $query->where('size', $sizeName);
        });

        $query->when($request->query('color_name'), function ($query, $colorName) {
            $query->where('color', $colorName);
        });


        $query->orderBy('id', $request->query('sort', 'ASC'));

        $orderDetails = $size ? $query->paginate($size) : $query->get();
        if (!$orderDetails) {
            return response()->json([
                "message" => "Đơn hàng không có sản phẩm"
            ], 404);
        }
        return response()->json($orderDetails);
    }
}