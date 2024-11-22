<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'start_date',
        'end_date',
        'status',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'campaign_products');
    }

    protected $casts = [
        'start_date' => ConvertDatetime::class,
        'end_date' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
        'created_at' => ConvertDatetime::class,
    ];
}
