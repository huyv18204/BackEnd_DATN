<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Order\OrderRequest;
use App\Models\DeliveryPerson;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use App\Models\ProductAtt;
use App\Models\ShippingAddress;
use App\Models\Ward;
use App\Services\OrderHepper;
use DateTime;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $size = $request->query('size');

        $query = Order::query()
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'name', "address", "email", "phone");
                },
                'delivery_person.user'
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

    public function updateOrderStt(Request $request, $id): JsonResponse
    {
        if (!$request->order_status) {
            return response()->json([
                'message' => "Trạng thái là bắt buộc"
            ]);
        }

        if (!OrderStatus::isValidValue($request->order_status)) {
            return response()->json(['message' => "Trạng thái không hợp lệ"], 422);
        }
        try {
            $order = Order::query()->find($id);
            if (!$order) {
                return response()->json(['message' => 'Đơn hàng không tồn tại']);
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
            OrderStatusHistory::query()->create([
                'order_id' => $id,
                'status' => $request['order_status'],
            ]);
            return response()->json([
                'message' => 'Cập nhật trạng thái đơn hàng thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updatePaymentStt(Request $request, $id): JsonResponse
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

    public function store(OrderRequest $request): JsonResponse
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
                "order_status" => OrderStatus::PENDING->value,
                "payment_method" => PaymentMethod::CASH->value,
                "payment_status" => PaymentStatus::NOT_YET_PAID->value,
                "total_amount" => $data['total_amount'],
                "order_address" => $address,
                "delivery_fee" => 30,
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
                            return response()->json([
                                'message' => 'Số lượng sản phẩm không đủ'
                            ], 422);
                        }
                    }
                }
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => OrderStatus::PENDING->value,
            ]);
            DB::commit();
            $message = 'Đặt hàng thành công';
            return response()->json(['message' => $message], 201);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => 'Đặt hàng thất bại', 'error' => $exception->getMessage()], 400);
        }
    }

    public function show($id): JsonResponse
    {
        $order = Order::query()->with(['user', 'delivery_person.user', 'order_status_histories' => function ($query) {
            $query->select('status', 'created_at', 'order_id');
        }])->find($id);
        if (!$order) {
            return response()->json("Đơn hàng không tồn tại");
        }
        return response()->json($order);
    }

    public function assignToDeliveryPerson(Request $request, $id): JsonResponse
    {
        $validate = $request->validate([
            'delivery_person_id' => 'required|integer|exists:delivery_people,id'
        ], [
            'required' => "Dữ liệu không hợp lệ",
            'exists' => "Người giao hàng không tồn tại",
            'integer' => "Dữ liệu không hợp lệ"
        ]);

        try {
            $order = Order::query()->find($id);
            $count = Order::query()->where('delivery_person_id', $validate['delivery_person_id'])->count();
            if ($count > 10) {
                return response()->json(['message' => "Người vận chuyển đã đạt số lượng tối đa đơn hàng"]);
            }
            if (!$order) {
                return response()->json([
                    "message" => "Đơn hàng không tồn tại"
                ]);
            }
            $order->update([
                'delivery_person_id' => $validate['delivery_person_id'],
                'order_status' => OrderStatus::WAITING_DELIVERY
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $id,
                'status' => OrderStatus::WAITING_DELIVERY
            ]);

            return response()->json([
                'message' => "Gán đơn hàng cho người vận chuyển thành công"
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function assignManyToDeliveryPerson(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'delivery_person_id' => 'required|integer|exists:delivery_people,id',
            'order_id' => 'required|array',
            'order_id.*' => 'required|integer|exists:orders,id'
        ], [
            'required' => "Dữ liệu không hợp lệ",
            'delivery_person_id.exists' => "Người giao hàng không tồn tại",
            'integer' => "Dữ liệu không hợp lệ",
            'order_id.*.exists' => "Đơn hàng không tồi tại"
        ]);

        try {
            $errors = [];
            DB::beginTransaction();

            foreach ($validate['order_id'] as $id) {
                $order = Order::query()->find($id);
                $count = Order::query()->where('delivery_person_id', $validate['delivery_person_id'])->count();
                if ($count > 10) {
                    $errors[$id] = "Người vận chuyển đã đạt số lượng tối đa đơn hàng";
                    continue;
                }
                if (!$order) {
                    $errors[$id] = "Đơn hàng không tồn tại";
                    continue;
                }
                OrderStatusHistory::query()->create([
                    'order_id' => $id,
                    'status' => OrderStatus::WAITING_DELIVERY
                ]);
                try {
                    $order->update([
                        'delivery_person_id' => $validate['delivery_person_id'],
                        'order_status' => OrderStatus::WAITING_DELIVERY

                    ]);
                } catch (\Exception $e) {
                    $errors[$id] = "Không thể gán đơn hàng: " . $e->getMessage();
                }
            }
            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'message' => "Có lỗi xảy ra trong quá trình gán đơn hàng",
                    'errors' => $errors
                ], 422);
            }
            DB::commit();

            return response()->json([
                'message' => "Gán đơn hàng cho người vận chuyển thành công"
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => "Đã xảy ra lỗi hệ thống: " . $exception->getMessage()
            ], 500);
        }
    }


    // orders by delivery login (status : waiting delivery and on delivery)
    public function getByDeliveryPersonLogin(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $deliveryPerson = DeliveryPerson::query()->where('user_id', $user->id)->first();
            if (!$deliveryPerson) {
                return response()->json([
                    'message' => "Người giao hàng không tồn tại"
                ]);
            }
            //Viet them : lay danh sach theo status
            $sort = $request->input('sort', "ASC");
            $status = $request->input('status');

            $query = Order::query()
                ->with('user', 'order_details')
                ->where('delivery_person_id', $deliveryPerson->id)
                ->when($status, function ($query, $status) {
                    return $query->where('order_status', $status);
                })
                ->whereIn('order_status', [OrderStatus::WAITING_DELIVERY->value, OrderStatus::ON_DELIVERY->value])
                ->orderBy('id', $sort);

            $orders = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();

            return response()->json($orders);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    //orders history by delivery login (status return and delivered)
    public function historyDelivered(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $deliveryPerson = DeliveryPerson::query()->where('user_id', $user->id)->first();
            if (!$deliveryPerson) {
                return response()->json([
                    'message' => "Người giao hàng không tồn tại"
                ]);
            }
            $sort = $request->input('sort', "ASC");
            $query = Order::query()->with('user', 'order_details')
                ->where('delivery_person_id', $deliveryPerson->id)
                ->whereIn('order_status', [OrderStatus::DELIVERED->value, OrderStatus::RETURN->value])
                ->orderBy('id', $sort);
            $orders = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();
            return response()->json($orders);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    // orders by id delivery  (status : waiting delivery and on delivery)
    public function getByDeliveryPersonId(Request $request, $id): JsonResponse
    {
        try {
            $deliveryPerson = DeliveryPerson::query()->find($id);
            if (!$deliveryPerson) {
                return response()->json([
                    'message' => "Người giao hàng không tồn tại"
                ], 404);
            }
            $sort = $request->input('sort', "ASC");
            $query = Order::query()->where('delivery_person_id', $id)->orderBy('id', $sort);
            $orders = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();
            return response()->json($orders);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    //orders history by id delivery person
    public function historyDeliveredById(Request $request, $id): JsonResponse
    {
        try {
            $deliveryPerson = DeliveryPerson::query()->find($id);
            if (!$deliveryPerson) {
                return response()->json([
                    'message' => "Người giao hàng không tồn tại"
                ], 404);
            }
            $sort = $request->input('sort', "ASC");
            $query = Order::query()->with('user', 'order_details')->where('delivery_person_id', $id)
                ->whereIn('order_status', [OrderStatus::DELIVERED->value, OrderStatus::RETURN->value])
                ->orderBy('id', $sort);
            $orders = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();
            return response()->json($orders);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function updateManyOrderToOnDeliveryStatus(Request $request): JsonResponse
    {

        $validate = $request->validate([
            'id.*' => 'integer|exists:orders,id',
            'id' => ['required', 'array'],
        ]);
        DB::beginTransaction();
        try {
            foreach ($validate['id'] as $id) {
                Order::query()->find($id)->update([
                    'order_status' => OrderStatus::ON_DELIVERY->value
                ]);
            }
            DB::commit();
            return response()->json([
                'message' => "Cập nhật trạng thái đơn hàng thành công"
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }
}
