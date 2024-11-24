<?php

namespace App\Services;

use App\Models\District;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductAtt;
use App\Models\ShippingAddress;
use App\Models\Ward;
use Illuminate\Support\Str;

class OrderHepper
{
    public static function createOrderCode(): string
    {
        $currentDay = date('d');
        $currentMonth = date('m');
        return "OR" . $currentDay . $currentMonth . strtoupper(Str::random(2));

    }


    public static function createOrderAddress($shipping_address_id): string
    {
        $shippingAddress = ShippingAddress::query()->find($shipping_address_id);
        $district = District::query()->where("code", $shippingAddress->district_code)->first();
        $ward = Ward::query()->where("code", $shippingAddress->ward_code)->first();
        return $shippingAddress->recipient_address . ", " . $ward->name . ", Quận " . $district->name . ", Thành phố Hà Nội";
    }


}
