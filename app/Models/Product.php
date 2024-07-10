<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        "sku",
        "name",
        "thumbnail",
        "short_description",
        "long_description",
        "view",
        "regular_price",
        "reduced_price",
        "stock",
        "product_category_id",
        "is_active"

    ];

    protected $casts = [
        'is_active' => "boolean",
    ];

    public function product_category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function product_variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function product_galleries()
    {
        return $this->hasMany(ProductGallery::class);
    }

}
