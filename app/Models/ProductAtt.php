<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAtt extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color_id',
        'stock_quantity',
        'image',
        'is_active'
    ];
    protected $casts = [
        'is_active' => "boolean",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_att_size', 'product_att_id','size_id');
    }
}
