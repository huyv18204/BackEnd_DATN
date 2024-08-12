<?php

namespace App\Enums;

enum  PaymentMethod : string{
    case CASH = 'Thanh toán khi nhận hàng';
    case VN_PAY = 'VN Pay';
    case MOMO = 'MOMO';

    public static function isValidValue(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }
}
