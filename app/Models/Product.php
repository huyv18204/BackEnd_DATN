<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
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
        'regular_price' => 'integer',
        'reduced_price' => 'integer',
        'created_at' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product_atts()
    {
        return $this->hasMany(ProductAtt::class);
    }


}
