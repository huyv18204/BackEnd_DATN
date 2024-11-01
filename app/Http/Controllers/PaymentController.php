<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductAtt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $amount = 0;
        foreach ($request->all() as $key => $value) {
            $amount += $value["total_amount"];
        }

        $currentDay = date('d');
        $currentMonth = date('m');
        $hours = date('H');
        $minutes = date('i');
        $seconds = date('s');
//        $orderId = "ODMM" . $currentDay . $currentMonth . "-" . $hours . $minutes . $seconds;
        $orderId = "ODMM" . '-' . date('ds');


        $accessKey = "F8BBA842ECF85";
        $secretKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";
        $url = "http://localhost:3000/";
        $ipnUrl = env('NGROK_URL'). '/api/payment/callback';
        $endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
        $requestId = time() . '';
        $extraData = json_encode($request->toArray());
        $orderInfo = "Thanh Toán qua MOMO";
        $partnerCode = "MOMO";
        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$url}&requestId={$requestId}&requestType=payWithMethod";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            'storeId' => 'MomoTestStore',
            'requestId' => $requestId,
            'amount' => $amount,
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

    public function handlePaymentCallback(Request $request)
    {
        Log::info($request->all());
        $orderData = json_decode($request->extraData, true);
        DB::beginTransaction();
        try {
            if ($request->resultCode == 0) {
                $order = Order::query()->create([
                    'order_code' => $request->orderId,
                    'user_id' => 1,
                    'total_amount' => $request->amount,
                    'payment_method' => "MOMO",
                    'payment_status' => "Đã thanh toán",
                    'order_address' => 'Hà Nam',
                    'note' => "no no"
                ]);

                if ($order) {
                    foreach ($orderData as $item) {
                        $item['order_id'] = $order->id;
                        $order_details = OrderDetail::query()->create($item);

                        if ($order_details) {
                            $product_att = ProductAtt::query()->find($order_details->product_att_id);
                            if ($product_att->stock_quantity >= $item['quantity']) {
                                $product_att?->update([
                                    'stock_quantity' => $product_att->stock_quantity - $item['quantity']
                                ]);
                            } else {
                                throw new Exception("Số lượng sản phẩm {$item['product_name']} không đủ");
                            }
                        }
                    }
                }
                DB::commit();
                return response()->json(['message' => 'Thanh toán thành công']);
            } else {
                DB::rollBack();
                Log::error("Payment failed for order ID: {$request->orderId}, Result Code: {$request->resultCode}");
                return response()->json([
                    'message' => "Thanh toán thất bại"
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }
}
