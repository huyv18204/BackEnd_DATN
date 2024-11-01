<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Order\OrderRequest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductAtt;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->query('size');

        $query = Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'name', "address", "email", "phone");
                }
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


        if (!$request->order_status) {
            return response()->json("Trạng thái là bắt buộc");
        }

        if (!OrderStatus::isValidValue($request->order_status)) {
            return response()->json("Trạng thái không hợp lệ");
        }

        $order = Order::query()->find($id);
        if (!$order) {
            return response()->json('Đơn hàng không tồn tại');
        }
        if ($order->order_status === "Đã xác nhận" && $request['order_status'] === "Chờ xác nhận") {
            return response()->json("Trạng thái không hợp lệ");
        }

        $response = $order->update([
            'order_status' => $request['order_status']
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

    public function show($id)
    {
        $order = Order::query()->find($id);
        if (!$order) {
            return response()->json("Đơn hàng không tồn tại");
        }
        return response()->json($order);
    }

    public function store(OrderRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {

            $order = Order::query()->create($data);
            if ($order) {
                foreach ($data['order_details'] as $orderDetail) {
                    $orderDetail['order_id'] = $order->id;
                    $orderDetails = OrderDetail::query()->create($orderDetail);
                    if ($orderDetails) {
                        $product_att = ProductAtt::query()->find($orderDetails->product_att_id);
                        if ($product_att->stock_quantity >= $orderDetail['quantity']) {
                            $product_att?->update([
                                'stock_quantity' => $product_att->stock_quantity - $orderDetail['quantity']
                            ]);
                        } else {
                            throw new Exception("Số lượng sản phẩm {$orderDetail['product_name']} không đủ");
                        }
                    }
                }
            }

            DB::commit();
            $message = 'Đặt hàng thành công';
            return response()->json(['message' => $message], 201);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());
            return response()->json(['message' => 'Đặt hàng thất bại', 'error' => $exception->getMessage()], 400);
        }
    }


}
