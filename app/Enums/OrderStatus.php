<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'Chờ xác nhận';
    case ACCEPT = 'Đã xác nhận';
//    case COMPLETED = 'Giao hàng thành công';
//    case CANCELED = 'Huỷ đơn hàng';


    public static function isValidValue(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }
}

