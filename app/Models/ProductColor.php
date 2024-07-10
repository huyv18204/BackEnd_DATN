<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    use HasFactory;

    protected $fillable = [
        "color",
    ];

    public function product_variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
