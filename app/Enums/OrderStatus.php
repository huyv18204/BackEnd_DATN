<?php

namespace App\Enums;

enum OrderStatus: string
{

    case PENDING = 'Chờ xác nhận';
    case CONFIRMED = 'Đã xác nhận';
    case WAITING_DELIVERY = 'Chờ lấy hàng';
    case DELIVERED = 'Đã giao';
    case ON_DELIVERY = 'Đang giao';
    case CANCELED = 'Đã huỷ';
    case RETURN = 'Trả hàng';
    case RECEIVED = "Đã nhận hàng";
    case NOT_RECEIVE = "Chưa nhận hàng";

    public static function isValidValue(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }
}
