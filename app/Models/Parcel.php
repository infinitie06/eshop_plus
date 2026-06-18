<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'delivery_boy_id',
        'name',
        'type',
        'status',
        'active_status',
        'otp',
        'delivery_charge',
        'pickup_location',
    ];
public function order()
{
    return $this->belongsTo(Order::class, 'order_id', 'id');
}
    public function items() {
        return $this->hasMany(Parcelitem::class);
    }
    public function deliveryBoy() {
        return $this->belongsTo(User::class, 'delivery_boy_id');
    }
    public function storeSeller()
    {
        return $this->belongsTo(Seller::class, 'store_id', 'id');
    }
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    public function pickupLocation()
    {
        return $this->belongsTo(PickupLocation::class, 'pickup_location', 'id');
    }
}
