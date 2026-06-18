<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateTracking extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'product_id', 'affiliate_id', 'token', 'original_token', 
        'category_id', 'category_commission', 'affiliate_uuid', 
        'product_type', 'click_count', 'deep_link_url', 'web_url', 
        'last_clicked_at', 'usage_count', 'commission_earned', 'total_order_value'
    ];

    protected $casts = [
        'last_clicked_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function affiliate()
    {
        return $this->belongsTo(AffiliateUser::class, 'affiliate_id');
    }
}