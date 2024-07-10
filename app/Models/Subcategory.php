<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "is_active",
        "category_id"
    ];
    protected $casts = [
        'is_active' => "boolean",
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product_categories()
    {
        return $this->hasMany(ProductCategory::class);
    }
}
