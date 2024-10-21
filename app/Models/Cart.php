<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_att_id',
        'user_id',
        'quantity'
    ];

    protected $casts = [
        'created_at' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
    ];
}
