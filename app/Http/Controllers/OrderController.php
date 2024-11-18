<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Order\OrderRequest;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductAtt;
use App\Models\ShippingAddress;
use App\Models\Ward;
use App\Services\OrderHepper;
use DateTime;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

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
        if (!OrderStatus::isValidValue($request->order_status)) {
            return response()->json("Trạng thái không hợp lệ");
        }
        if (!$request->order_status) {
            return response()->json("Trạng thái là bắt buộc");
        }
        try {
            $order = Order::query()->find($id);
            if (!$order) {
                return response()->json('Đơn hàng không tồn tại');
            }
            $order->update([
                'order_status' => $request['order_status']
            ]);
            if ($request['order_status'] === OrderStatus::CANCELED->value) {
                $orderDetails = OrderDetail::query()->where('order_id', $id)->get();
                foreach ($orderDetails as $item) {
                    $productAtt = ProductAtt::query()->find($item->product_att_id);
                    $productAtt->update([
                        'stock_quantity' => $productAtt->stock_quantity + $item->quantity
                    ]);
                }
            }
            return response()->json([
                'message' => 'Cập nhật trạng thái đơn hàng thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
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
        try {
            $order = Order::query()->find($id);
            if (!$order) {
                return response()->json('Đơn hàng không tồn tại');
            }
            $order->update([
                'payment_status' => $validatedData['payment_status']
            ]);
            return response()->json([
                'message' => 'Cập nhật trạng thái thanh toán thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
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
            $address = OrderHepper::createOrderAddress($data['shipping_address_id']);
            $data['order_code'] = OrderHepper::createOrderCode();
            $user_id = JWTAuth::parseToken()->authenticate()->id;
            $order = Order::query()->create([
                "order_code" => $data['order_code'],
                "user_id" => $user_id,
                "order_date" => now(),
                "order_status" => OrderStatus::PENDING->value,
                "payment_method" => PaymentMethod::CASH->value,
                "payment_status" => PaymentStatus::NOT_YET_PAID->value,
                "total_amount" => $data['total_amount'],
                "order_address" => $address,
                "note" => $data['note'] ?? null,
            ]);

            if ($order) {
                foreach ($data['order_details'] as $item) {
                    $item['order_id'] = $order->id;
                    $orderDetails = OrderDetail::query()->create($item);
                    if ($orderDetails) {
                        $productAtt = ProductAtt::query()->find($orderDetails->product_att_id);
                        if ($productAtt->stock_quantity >= $item['quantity']) {
                            $productAtt?->update([
                                'stock_quantity' => $productAtt->stock_quantity - $item['quantity']
                            ]);
                        } else {
                            throw new Exception("Số lượng sản phẩm {$item['product_name']} không đủ");
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

    public function getByWaitingDeliveryStatus(Request $request): JsonResponse
    {
        $query = Order::query()
            ->where('order_status', OrderStatus::WAITING_DELIVERY->value)
            ->whereDoesntHave('shipment_detail');

        $query->when($request->query('order_code'), function ($query, $orderCode) {
            $query->where('order_code', $orderCode);
        });
        $orders = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();

        return response()->json($orders);

    }
}
