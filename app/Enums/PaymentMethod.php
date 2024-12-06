<?php

namespace App\Enums;

enum  PaymentMethod : string{
    case CASH = 'Thanh toán khi nhận hàng';
    case MOMO = 'MOMO';

    case VNPAY = 'VNPAY';

    public static function isValidValue(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }
}
