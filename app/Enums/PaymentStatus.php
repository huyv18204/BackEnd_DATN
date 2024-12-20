<?php

namespace App\Enums;

enum  PaymentStatus : string{
    case NOT_YET_PAID = 'Chưa thanh toán';
    case PAID = 'Đã thanh toán';
    case FAIL = 'Thanh toán thất bại';

    public static function isValidValue(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }
}
