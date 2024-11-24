<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "image",
        "slug",
        "parent_id",
        "category_code",
        "is_active"
    ];

    protected $casts = [
        'is_active' => "boolean",
        'created_at' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children');
    }

    public function getFullPathAttribute()
    {
        if ($this->parent) {
            return $this->parent->name . ' - ' . $this->name;
        }
        return $this->name;
    }
}
