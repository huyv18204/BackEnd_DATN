<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Size extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'size',
        'is_active'
    ];
    protected $casts = [
        'is_active' => "boolean",
    ];

    public function product_atts()
    {
        return $this->hasMany(ProductAtt::class);
    }
}
