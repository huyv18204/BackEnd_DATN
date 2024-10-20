<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_att_id',
        'quantity',
        'subtotal',
        'size',
        'color',
        'product_name',
        'unit_price',
        'total_amount'
    ];

    public function product_att()
    {
        return $this->belongsTo(ProductAtt::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}