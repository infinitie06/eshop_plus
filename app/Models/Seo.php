<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seo extends Model
{
    use HasFactory;

    protected $table = 'seo_settings';

    protected $fillable = [
        'seo_type',
        'reference_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
        'structured_data',
    ];

    protected $casts = [
        'structured_data' => 'array',
    ];

    /**
     * Get the product associated with this SEO setting
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'reference_id')->where('seo_type', 'product');
    }

    /**
     * Get the category associated with this SEO setting
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'reference_id')->where('seo_type', 'category');
    }

    /**
     * Get the blog associated with this SEO setting
     */
    public function blog()
    {
        return $this->belongsTo(Blog::class, 'reference_id')->where('seo_type', 'blog');
    }

    /**
     * Get SEO settings by type and reference ID
     */
    public static function getSeoData($type, $referenceId = null)
    {
        return self::where('seo_type', $type)
            ->where('reference_id', $referenceId)
            ->first();
    }

    /**
     * Create or update SEO settings
     */
    public static function updateOrCreateSeo($type, $referenceId, $data)
    {
        return self::updateOrCreate(
            [
                'seo_type' => $type,
                'reference_id' => $referenceId,
            ],
            $data
        );
    }
}
