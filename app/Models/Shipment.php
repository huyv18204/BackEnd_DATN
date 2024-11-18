<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'delivery_person_id',
        'code'
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shipment_details(): HasMany
    {
        return $this->hasMany(ShipmentDetail::class);
    }

    public function delivery_person(): BelongsTo {
        return $this->belongsTo(DeliveryPerson::class);
    }
}
