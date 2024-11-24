<?php

namespace App\Http\Controllers;

use App\Models\ShipmentDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentDetailController extends Controller
{
    public function show(Request $request,  $shipment_id) : JsonResponse {
        $query = ShipmentDetail::query()->with('order')->where('shipment_id', $shipment_id);


        if ($request->has('order_code')) {
            $query->whereHas('order', function ($subQuery) use ($request) {
                $subQuery->where('order_code',  $request->input('order_code'));
            });
        }

        $shipment_details = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();
        return response()->json($shipment_details);
    }
}
