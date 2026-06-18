<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateTransaction extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'affiliate_wallet_transactions'; 
    protected $fillable = ['user_id', 'amount', 'transaction_type', 'reference_type', 'message'];
    public $timestamps = true;
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}