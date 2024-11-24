<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'province_code',
        'district_code',
        'ward_code',
        'is_default',
    ];

}
