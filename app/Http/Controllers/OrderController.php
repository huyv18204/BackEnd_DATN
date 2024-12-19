<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Order\OrderRequest;
use App\Jobs\SendOrderInfo;
use App\Models\Cart;
use App\Models\DeliveryPerson;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use App\Models\ProductAtt;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUser;
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
        //Sort
        $sortField = $request->input('sortField', 'id');
        $size = $request->query('size');
        $sortDirection = $request->query('sort', 'DESC');

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

        $query->orderBy($sortField, $sortDirection);

        $orders = $size ? $query->paginate($size) : $query->get();
        return response()->json($orders);
    }

    public function updateOrderStt(Request $request, $id): JsonResponse
    {

        if (!$request->order_status) {
            return response()->json([
                'message' => "Trạng thái là bắt buộc"
            ], 422);
        }

        if (!OrderStatus::isValidValue($request->order_status)) {
            return response()->json(['message' => "Trạng thái không hợp lệ"], 422);
        }

        try {
            $order = Order::query()->find($id);

            if (!$order) {
                return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
            }

            if ($order->order_status === OrderStatus::CANCELED->value) {
                return response()->json([
                    "message" => "Đơn hàng đã bị huỷ trước đó"
                ], 422);
            }

            if (
                $order->order_status === OrderStatus::ON_DELIVERY->value ||
                $order->order_status === OrderStatus::DELIVERED->value ||
                $order->order_status === OrderStatus::RECEIVED->value ||
                $order->order_status === OrderStatus::NOT_RECEIVE->value ||
                $order->order_status === OrderStatus::RETURN->value
            ) {
                if ($request->order_status === OrderStatus::CANCELED->value) {
                    return response()->json([
                        "message" => "Không thể huỷ đơn hàng"
                    ], 422);
                }
            }

            $order->update([
                'order_status' => $request['order_status']
            ]);

            $user = User::query()->find($order->user_id);

            if (
                $request->order_status === OrderStatus::CANCELED->value ||
                $request->order_status === OrderStatus::DELIVERED->value ||
                $request->order_status === OrderStatus::ON_DELIVERY->value ||
                $request->order_status === OrderStatus::CONFIRMED->value
            ) {
                SendOrderInfo::dispatch($user->email, $request->order_status, $order);
            }

            if ($request->order_status == OrderStatus::DELIVERED->value) {
                $order->update([
                    'payment_status' => PaymentStatus::PAID->value
                ]);
            }
            if ($request->order_status === OrderStatus::CANCELED->value) {
                OrderStatusHistory::query()->where('order_id', $id)->where('status', '!=', OrderStatus::PENDING->value)->delete();
                OrderStatusHistory::query()->create([
                    'order_id' => $id,
                    'status' => OrderStatus::CANCELED->value,
                    $request->note,
                ]);

                $orderDetails = OrderDetail::query()->where('order_id', $id)->get();
                foreach ($orderDetails as $item) {
                    $productAtt = ProductAtt::query()->find($item->product_att_id);
                    $productAtt->update([
                        'stock_quantity' => $productAtt->stock_quantity + $item->quantity
                    ]);
                }
            } elseif ($request->order_status === OrderStatus::DELIVERED->value) {
                OrderStatusHistory::query()->create([
                    'order_id' => $id,
                    'status' => $request->order_status,
                    'image' => $request->image,
                ]);
            } elseif ($request->order_status === OrderStatus::RETURN->value) {
                OrderStatusHistory::query()->create([
                    'order_id' => $id,
                    'status' => $request->order_status,
                    'note' => $request->note
                ]);
                $orderDetails = OrderDetail::query()->where('order_id', $id)->get();
                foreach ($orderDetails as $item) {
                    $productAtt = ProductAtt::query()->find($item->product_att_id);
                    $productAtt->update([
                        'stock_quantity' => $productAtt->stock_quantity + $item->quantity
                    ]);
                }
            } else {
                OrderStatusHistory::query()->create([
                    'order_id' => $id,
                    'status' => $request->order_status,
                ]);
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

    public function updatePaymentStt(Request $request, $id): JsonResponse
    {
        $validate = $request->validate([
            'payment_status' => 'required|in:Chưa thanh toán,Đã thanh toán,Thanh toán thất bại',
        ]);

        try {
            Order::query()->find($id)->update([
                'payment_status' => $validate['payment_status']
            ]);
            return response()->json([
                'message' => "Cập nhật trạng thái thành công"
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function store(OrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = JWTAuth::parseToken()->authenticate();
        DB::beginTransaction();
        try {
            if ($data['voucher_code']) {
                $voucher = Voucher::where('voucher_code', $data['voucher_code'])->first();
                if (!$voucher) {
                    return response()->json(['message' => 'Voucher không hợp lệ'], 422);
                }

                if ($voucher->status !== 'active') {
                    return response()->json(['message' => 'Voucher không còn hiệu lực'], 422);
                }

                if ($voucher->expiration_date && $voucher->expiration_date < now()) {
                    return response()->json(['message' => 'Voucher đã hết hạn'], 422);
                }
                VoucherUser::create([
                    'user_id' => $user->id,
                    'voucher_id' => $voucher->id,
                ]);
                $voucher->increment('used_count');
            }

            $errors = [];
            $outOfStockItems = [];
            $productAtts = ProductAtt::query()
                ->whereIn('id', array_column($data['order_details'], 'product_att_id'))
                ->get()
                ->keyBy('id');
            foreach ($data['order_details'] as $item) {
                $productAtt = $productAtts->get($item['product_att_id']);
                if ($productAtt->reduced_price && $productAtt->reduced_price != (int)$item['unit_price']) {
                    $errors[] = "{$item['product_name']}";
                } elseif (!$productAtt->reduced_price && $productAtt->regular_price != (int)$item['unit_price']) {
                    $errors[] = "{$item['product_name']}";
                }
                if ($productAtt->stock_quantity < $item['quantity']) {
                    $outOfStockItems[] = $productAtt->product->name;
                }
            }
            if (!empty($errors)) {
                $string = implode(', ', $errors);
                return response()->json(['message' => 'Giá sản phẩm ' . $string . " đã bị thay đổi"], 422);
            }

            if (!empty($outOfStockItems)) {
                $string = implode(', ', $outOfStockItems);
                return response()->json(['message' => 'Sản phẩm ' . $string . ' không đủ số lượng'], 422);
            }

            $address = OrderHepper::createOrderAddress($data['shipping_address_id']);
            $data['order_code'] = OrderHepper::createOrderCode();


            $order = Order::query()->create([
                "order_code" => $data['order_code'],
                "user_id" => $user->id,
                "order_status" => OrderStatus::PENDING->value,
                "payment_method" => $data['payment_method'] ?? PaymentMethod::CASH->value,
                "payment_status" => PaymentStatus::NOT_YET_PAID->value,
                "total_amount" => (int)$data['total_amount'],
                "order_address" => $address,
                "delivery_fee" => $data['delivery_fee'],
                "total_product_amount" => $data['total_product_amount'],
                "note" => $data['note'] ?? null,
            ]);

            foreach ($data['order_details'] as $item) {
                $item['order_id'] = $order->id;
                Cart::query()->where('product_att_id', $item['product_att_id'])->delete();
                OrderDetail::query()->create($item);

                $productAtt = $productAtts->get($item['product_att_id']);
                if (!$productAtt->update(['stock_quantity' => $productAtt->stock_quantity - $item['quantity']])) {
                    throw new Exception("Không thể cập nhật tồn kho");
                }
            }
            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => OrderStatus::PENDING->value,
            ]);

            SendOrderInfo::dispatch($user->email, "Chờ xác nhận", $order);

            DB::commit();
            return response()->json(['message' => 'Đặt hàng thành công', 'total_amount' => $data['total_amount'], 'order_id' => $order->id], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Đặt hàng thất bại', 'error' => $e->getMessage()], 400);
        }
    }

    public function show($id): JsonResponse
    {
        $order = Order::query()->with(['user', 'delivery_person.user', 'order_status_histories' => function ($query) {
            $query->select('status', 'created_at', 'order_id', 'note', 'image');
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
            $count = Order::query()->where('delivery_person_id', $validate['delivery_person_id'])
                ->whereIn('order_status', [OrderStatus::WAITING_DELIVERY, OrderStatus::ON_DELIVERY])->count();
            if ($count >= 10) {
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
        $count = Order::query()->where('delivery_person_id', $validate['delivery_person_id'])
            ->whereIn('order_status', [OrderStatus::WAITING_DELIVERY, OrderStatus::ON_DELIVERY])->count();

        if (count($validate['order_id']) + $count > 10) {
            return response()->json([
                "message" => "Người vận chuyển đã đạt số lượng tối đa đơn hàng"
            ], 422);
        }
        try {
            DB::beginTransaction();

            foreach ($validate['order_id'] as $id) {
                $order = Order::query()->find($id);
                $order->update([
                    'delivery_person_id' => $validate['delivery_person_id'],
                    'order_status' => OrderStatus::WAITING_DELIVERY
                ]);
                OrderStatusHistory::query()->create([
                    'order_id' => $id,
                    'status' => OrderStatus::WAITING_DELIVERY
                ]);
            }
            DB::commit();

            return response()->json([
                'message' => "Gán đơn hàng cho người vận chuyển thành công"
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => "Gán đơn hàng không thành công: " . $exception->getMessage()
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

    public function getById(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $sort = $request->input('sort', "ASC");
            $status = $request->input('status');
            $query = Order::query()
                ->with('user', 'order_details')
                ->where('user_id', $user->id)
                ->orderBy('id', $sort);

            if ($status === "confirm") {
                $query->whereIn('order_status', [OrderStatus::PENDING, OrderStatus::CONFIRMED]);
            } elseif ($status === "delivery") {
                $query->whereIn('order_status', [OrderStatus::WAITING_DELIVERY, OrderStatus::ON_DELIVERY]);
            }
            if ($status === "delivered") {
                $query->whereIn('order_status', [OrderStatus::DELIVERED]);
            }
            if ($status === "history") {
                $query->whereIn('order_status', [OrderStatus::RECEIVED, OrderStatus::RETURN, OrderStatus::CANCELED, OrderStatus::NOT_RECEIVE]);
            }
            $orders = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();

            return response()->json($orders);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function updateManyOrderStt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_status' => ['required', 'in:Đã huỷ,Đã xác nhận'],
            'order_id' => ['required', 'array'],
            'order_id.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        //        $orders = Order::whereIn('id', $validated['order_id'])->get();
        //
        //        $uniqueStatuses = $orders->pluck('status')->unique();
        //
        //        if ($uniqueStatuses->count() > 1) {
        //            return response()->json([
        //                'message' => 'Dữ liệu không hợp lệ.',
        //            ], 422);
        //        }

        try {
            foreach ($validated['order_id'] as $id) {
                $order = Order::query()->find($id);
                if (!$order) {
                    return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
                }

                if ($order->order_status === OrderStatus::CANCELED->value) {
                    return response()->json([
                        "message" => "Đơn hàng đã bị huỷ trước đó"
                    ], 422);
                }

                $order->update([
                    'order_status' => $request['order_status']
                ]);
                $user = User::query()->find($order->user_id);

                SendOrderInfo::dispatch($user->email, $request->order_status, $order);

                if ($request->order_status === OrderStatus::CANCELED->value) {
                    OrderStatusHistory::query()->where('order_id', $id)->where('status', '!=', OrderStatus::PENDING->value)->delete();
                    OrderStatusHistory::query()->create([
                        'order_id' => $id,
                        'status' => OrderStatus::CANCELED->value,
                        $request->note,
                    ]);

                    $orderDetails = OrderDetail::query()->where('order_id', $id)->get();
                    foreach ($orderDetails as $item) {
                        $productAtt = ProductAtt::query()->find($item->product_att_id);
                        $productAtt->update([
                            'stock_quantity' => $productAtt->stock_quantity + $item->quantity
                        ]);
                    }
                } else {
                    OrderStatusHistory::query()->create([
                        'order_id' => $id,
                        'status' => $request->order_status,
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
}
