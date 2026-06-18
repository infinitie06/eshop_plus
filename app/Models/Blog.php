<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Blog extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'title',
        'category_id',
        'store_id',
        'image',
        'description',
        'short_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'slug'

    ];
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
