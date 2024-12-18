<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Order\OrderRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use App\Models\ProductAtt;
use App\Services\OrderHepper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentController extends Controller
{
    public function createPayment(OrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = JWTAuth::parseToken()->authenticate()->id;
        foreach ($data['order_details'] as $item) {
            $productAtt = ProductAtt::query()->find($item['product_att_id']);
            if ($productAtt->stock_quantity < $item['quantity']) {
                $error[] = [
                    'message' => "Không đủ số lượng cho sản phẩm " . $item['product_name']
                ];
            }
        }
        if (!empty($error)) {
            return response()->json(['errors' => $error], 400);
        }

        $orderId = OrderHepper::createOrderCode();
        $accessKey = "F8BBA842ECF85";
        $secretKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";
        $url = env("FE_REDIRECT_URL", 'http://localhost:3000/');
        $ipnUrl = env('NGROK_URL') . "/api/v1/payment/callback";
        $endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
        $requestId = time() . '';
        $extraData = json_encode($data);
        $orderInfo = "Thanh Toán qua MOMO";
        $partnerCode = "MOMO";
        $rawHash = "accessKey={$accessKey}&amount={$data['total_amount']}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$url}&requestId={$requestId}&requestType=payWithMethod";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            'storeId' => 'MomoTestStore',
            'requestId' => $requestId,
            'amount' => $data['total_amount'],
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'requestType' => 'payWithMethod',
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'redirectUrl' => $url,
            'autoCapture' => true,
            'extraData' => $extraData,
            'orderGroupId' => '',
            'signature' => $signature,
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, $data);

        return response()->json($response->json());
    }

    public function handlePaymentCallback(Request $request): JsonResponse
    {
        $data = json_decode($request->extraData, true);
        DB::beginTransaction();
        try {
            if ($request->resultCode == 0) {
                $address = OrderHepper::createOrderAddress($data['shipping_address_id']);
                $order = Order::query()->create([
                    'order_code' => $request->orderId,
                    'total_product_amount' => $data['total_product_amount'],
                    'user_id' => $data['user_id'],
                    'total_amount' => $request->amount,
                    "order_status" => OrderStatus::PENDING->value,
                    'payment_method' => PaymentMethod::MOMO->value,
                    'payment_status' => PaymentStatus::PAID->value,
                    'order_address' => $address,
                    'note' => $data['note'] ?? null,
                    "delivery_fee" => $data['delivery_fee'] ?? 0,
                ]);

                if ($order) {
                    foreach ($data['order_details'] as $item) {
                        $item['order_id'] = $order->id;
                        Cart::query()->where('product_att_id', $item['product_att_id'])->delete();
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
                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'status' => OrderStatus::PENDING->value,
                ]);
                DB::commit();
                return response()->json(['message' => 'Thanh toán thành công']);
            } else {
                DB::rollBack();
                return response()->json([
                    'payUrl' => '/not-found',
                    'message' => "Thanh toán thất bại"
                ], 500);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }
}
