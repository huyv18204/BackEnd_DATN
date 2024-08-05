<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Size extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $hidden = ['pivot'];
    protected $fillable = [
        'name',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => "boolean",
        'created_at' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
    ];

    public function product_atts()
    {
        return $this->belongsToMany(ProductAtt::class,'product_att_size');
    }
}
