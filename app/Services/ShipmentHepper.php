<?php

namespace App\Services;

use App\Models\Shipment;

class ShipmentHepper
{
    public static function createOrderCode(): string
    {
        $currentDay = date('d');
        $currentMonth = date('m');
        $prevCode = "SM" . $currentDay . $currentMonth;
        $prevShipment = Shipment::query()->where("code", "LIKE", $prevCode . "%")
            ->orderByDesc('id')
            ->first();

        if ($prevShipment) {
            $parts = explode('-', $prevShipment->code);
            $lastPart = (int)end($parts) + 1;
            return $prevCode . '-' . str_pad($lastPart, 3, '0', STR_PAD_LEFT);
        } else {
            return $prevCode . '-' . "001";
        }
    }
}
