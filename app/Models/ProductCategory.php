<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "is_active",
        "subcategory_id"
    ];
    protected $casts = [
        'is_active' => "boolean",
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subcategory(){
        return $this->belongsTo(Subcategory::class);
    }
}
