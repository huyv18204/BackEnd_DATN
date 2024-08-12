<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'user_id',
        'total_amount',
        'payment_method',
        'order_status',
        'payment_status',
        'note',
        'order_address'
    ];


    public function order_details(){
        return $this->hasMany(OrderDetail::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'order_status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
    ];
}
