<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateUser extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'user_id',
        'uuid',
        'website_url',
        'application_url',
        'status',
        'commission_type',
        'default_commission_rate',
        'affiliate_wallet_balance'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trackings()
    {
        // Fix: Match affiliate_id in trackings to user_id in this table
        return $this->hasMany(AffiliateTracking::class, 'affiliate_id', 'user_id');
    }
}