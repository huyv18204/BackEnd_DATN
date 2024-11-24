<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shipment\StoreRequest;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Services\ShipmentHepper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShipmentController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $query = Shipment::query()->with(['delivery_person.user']);

        if ($request->has('code')) {
            $query->where('code', $request->input('code'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('user_name')) {
            $query->whereHas('delivery_person.user', function ($subQuery) use ($request) {
                $subQuery->where('name', 'like', '%' . $request->input('user_name') . '%');
            });
        }
        $shipments = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();
        return response()->json($shipments);
    }


    public function show($id): JsonResponse
    {
        try {
            $shipment = Shipment::with(['delivery_person.user', 'delivery_person.vehicle'])->find($id);
            return response()->json($shipment);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function getByUserId(Request $request, $id): JsonResponse
    {

        $query = Shipment::query()->where('delivery_person_id', $id);

        if ($request->has('code')) {
            $query->where('code', $request->input('code'));
        }

        $shipment = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();
        return response()->json($shipment);
    }


    public function getByUserLogin(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
//        $query = Shipment::query()->with(['shipment_details' => function ($query) {
//            $query->select( 'shipment_id', 'order_id')->with(['order']);
//        }])->where('delivery_person_id', $user->id);

        $query = Shipment::query()->where('delivery_person_id', $user->id);
        if ($request->has('code')) {
            $query->where('code', $request->input('code'));
        }
        $shipment = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();
        return response()->json($shipment);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $shipment = Shipment::query()->create([
                'delivery_person_id' => $validated['delivery_person_id'],
                'code' => ShipmentHepper::createOrderCode()
            ]);
            foreach ($validated['orders'] as $order) {
                ShipmentDetail::query()->create([
                    'order_id' => $order['order_id'],
                    'shipment_id' => $shipment->id
                ]);
            }
            DB::commit();
            return response()->json([
                'message' => 'Tạo lô hàng cho shipper thành công'
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => "Lỗi " . $exception->getMessage()
            ], 500);
        }
    }

    public function update(StoreRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $shipment = Shipment::query()->find($id);

            if (!$shipment) {
                return response()->json([
                    'message' => 'Lô hàng không tồn tại'
                ], 422);
            }

            $shipment->update([
                'delivery_person_id' => $validated['delivery_person_id'],
            ]);

            $orderData = collect($validated['orders'])->pluck('order_id')->toArray();

            $shipmentDetails = [];
            foreach ($orderData as $orderId) {
                $shipmentDetails[] = [
                    'order_id' => $orderId
                ];
            }

            $shipment->shipment_details()->delete();
            foreach ($shipmentDetails as $detail) {
                $shipment->shipment_details()->create($detail);
            }

            DB::commit();
            return response()->json([
                'message' => 'Cập nhật lô hàng thành công'
            ], 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => "Lỗi: " . $exception->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Chưa hoàn thành,Hoàn thành giao hàng',
        ], [
            'status.required' => 'Trường trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái phải không hợp lệ',
        ]);

        try {
            $shipment = Shipment::query()->find($id);
            if (!$shipment) {
                return response()->json([
                    "message" => "Lô hàng không tồn tai"
                ], 422);
            }
            $shipmentDetailExist = ShipmentDetail::query()
                ->where('shipment_id', $id)
                ->whereHas('order', function ($query) {
                    $query->whereNotIn('order_status', ["Đã giao", "Trả hàng"]);
                })
                ->exists();

            if (!$shipmentDetailExist) {
                $shipment->update([
                    'status' => $validated['status']
                ]);

                return response()->json([
                    'message' => "Cập nhật trạng thái thành công"
                ]);
            } else {
                return response()->json([
                    'message' => "Lô hàng chưa hoàn thiện"
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

    }

}
