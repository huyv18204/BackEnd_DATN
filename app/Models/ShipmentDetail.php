<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ShipmentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipment_id',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function shipment() : BelongsTo {
        return $this->belongsTo(Shipment::class);
    }

    public function order() : BelongsTo {
        return $this->belongsTo(Order::class);
    }
}
