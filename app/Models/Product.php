<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'slug',
        'material',
        "sku",
        "name",
        "thumbnail",
        "short_description",
        "long_description",
        "view",
        "regular_price",
        "reduced_price",
        "category_id",
        "is_active"

    ];

    protected $casts = [
        'is_active' => "boolean",
    ];

    public function product_category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product_variants()
    {
        return $this->hasMany(ProductAtt::class);
    }


}
