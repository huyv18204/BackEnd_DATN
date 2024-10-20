<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->query('size');

        $query = Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'name', "address", "email");
                }
                // ,
                // 'order_details.product' => function ($query) {
                //     $query->select('id', 'sku', 'category_id');
                // },
                // 'order_details.product.category' => function ($query) {
                //     $query->select('name', 'id');
                // }
            ]);


        $query->when($request->query('minPrice'), function ($query, $minPrice) {
            $query->where('total_amount', '>=', $minPrice);
        });

        $query->when($request->query('maxPrice'), function ($query, $maxPrice) {
            $query->where('total_amount', '<=', $maxPrice);
        });

        $query->when($request->query('paymentMethod'), function ($query, $paymentMethod) {
            if (PaymentMethod::isValidValue($paymentMethod)) {
                $query->where('payment_method', $paymentMethod);
            }
        });

        $query->when($request->query('orderStatus'), function ($query, $order_status) {
            if (OrderStatus::isValidValue($order_status)) {
                $query->where('order_status', $order_status);
            }
        });

        $query->when($request->query('paymentStatus'), function ($query, $payment_status) {
            if (PaymentStatus::isValidValue($payment_status)) {
                $query->where('payment_status', $payment_status);
            }
        });

        $query->when($request->query('userId'), function ($query, $userId) {
            $query->where('user_id', $userId);
        });

        $query->when($request->query('orderCode'), function ($query, $orderCode) {
            $query->where('order_code', $orderCode);
        });

        $query->orderBy('id', $request->query('sort', 'ASC'));

        $orders = $size ? $query->paginate($size) : $query->get();
        return response()->json($orders);
    }


    public function updateOrderStt(Request $request, $id)
    {

        $validatedData = $request->validate([
            'order_status' => [
                'required',
                new \Illuminate\Validation\Rules\Enum(OrderStatus::class)
            ],
        ]);


        $order = Order::query()->find($id);
        if (!$order) {
            return response()->json('Đơn hàng không tồn tại');
        }

        $response = $order->update([
            'order_status' => $validatedData['order_status']
        ]);

        if ($response) {
            return response()->json('Cập nhật trạng thái đơn hàng thành công');
        } else {
            return response()->json('Cập nhật trạng thái đơn hàng thất bại');
        }

    }


    public function updatePaymentStt(Request $request, $id)
    {

        $validatedData = $request->validate([
            'payment_status' => [
                'required',
                new \Illuminate\Validation\Rules\Enum(PaymentStatus::class)
            ],
        ]);

        $order = Order::query()->find($id);
        if (!$order) {
            return response()->json('Đơn hàng không tồn tại');
        }

        $response = $order->update([
            'payment_status' => $validatedData['payment_status']
        ]);

        if ($response) {
            return response()->json('Cập nhật trạng thái thanh toán thành công');
        } else {
            return response()->json('Cập nhật trạng thái thanh toán thất bại');
        }


    }

}