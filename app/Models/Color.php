<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "code",
        "is_active"
    ];
    protected $casts = [
        'is_active' => "boolean",
        'created_at' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
    ];

    public function product_atts()
    {
        return $this->hasMany(ProductAtt::class);
    }
}
