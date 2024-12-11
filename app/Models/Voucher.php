<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;
    protected $fillable = [
        'voucher_code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_value',
        'usage_limit',
        'used_count',
        'is_active',
        'start_date',
        'end_date',
        'status',
    ];
}
