<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAtt extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'color_id',
        'size_id',
        'image',
        'regular_price',
        'reduced_price',
        'stock_quantity',
        'is_active'
    ];

    protected $casts = [
        'is_active' => "boolean",
        'regular_price' => 'integer',
        'reduced_price' => 'integer',
        'created_at' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::deleting(function ($variant) {
            $product = $variant->product;

            if ($product->productAtts()->count() <= 1) {
                $product->update(['is_active' => false]);
            }
        });
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
