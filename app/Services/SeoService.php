<?php

namespace App\Services;

use App\Models\Seo;
use App\Models\Product;
use App\Models\Category;
use App\Models\Blog;

class SeoService
{
    /**
     * Get SEO data for a specific type and reference
     */
    public function getSeoData($type, $referenceId = null)
    {
        $seoData = Seo::getSeoData($type, $referenceId);
        
        if (!$seoData && $type !== 'global') {
            // Fallback to global settings if specific SEO not found
            $seoData = Seo::getSeoData('global', null);
        }
        
        return $seoData;
    }

    /**
     * Get default SEO data from system settings
     */
    public function getDefaultSeoData()
    {
        $settingService = app(SettingService::class);
        $systemSettings = $settingService->getSettings('system_settings', true);
        $systemSettings = $systemSettings ? json_decode($systemSettings, true) : [];
        
        return [
            'meta_title' => $systemSettings['app_name'] ?? 'E-Shop',
            'meta_description' => $systemSettings['app_name'] ?? 'E-Shop',
            'meta_keywords' => $systemSettings['app_name'] ?? 'E-Shop',
            'og_title' => $systemSettings['app_name'] ?? 'E-Shop',
            'og_description' => $systemSettings['app_name'] ?? 'E-Shop',
            'twitter_title' => $systemSettings['app_name'] ?? 'E-Shop',
            'twitter_description' => $systemSettings['app_name'] ?? 'E-Shop',
        ];
    }

    /**
     * Generate meta tags HTML
     */
    public function generateMetaTags($seoData, $defaultData = [])
    {
        if (!$seoData) {
            $seoData = (object) $this->getDefaultSeoData();
        }
        
        $metaTags = [];
        
        // Basic Meta Tags
        if (!empty($seoData->meta_title)) {
            $metaTags[] = '<meta name="title" content="' . e($seoData->meta_title) . '">';
        }
        
        if (!empty($seoData->meta_description)) {
            $metaTags[] = '<meta name="description" content="' . e($seoData->meta_description) . '">';
        }
        
        if (!empty($seoData->meta_keywords)) {
            $metaTags[] = '<meta name="keywords" content="' . e($seoData->meta_keywords) . '">';
        }
        
        if (!empty($seoData->robots)) {
            $metaTags[] = '<meta name="robots" content="' . e($seoData->robots) . '">';
        }
        
        if (!empty($seoData->canonical_url)) {
            $metaTags[] = '<link rel="canonical" href="' . e($seoData->canonical_url) . '">';
        }
        
        // Open Graph Tags
        if (!empty($seoData->og_title)) {
            $metaTags[] = '<meta property="og:title" content="' . e($seoData->og_title) . '">';
        }
        
        if (!empty($seoData->og_description)) {
            $metaTags[] = '<meta property="og:description" content="' . e($seoData->og_description) . '">';
        }
        
        if (!empty($seoData->og_image)) {
            $metaTags[] = '<meta property="og:image" content="' . e($seoData->og_image) . '">';
        }
        
        if (!empty($seoData->og_type)) {
            $metaTags[] = '<meta property="og:type" content="' . e($seoData->og_type) . '">';
        }
        
        $metaTags[] = '<meta property="og:url" content="' . e(url()->current()) . '">';
        
        // Twitter Card Tags
        if (!empty($seoData->twitter_card)) {
            $metaTags[] = '<meta name="twitter:card" content="' . e($seoData->twitter_card) . '">';
        }
        
        if (!empty($seoData->twitter_title)) {
            $metaTags[] = '<meta name="twitter:title" content="' . e($seoData->twitter_title) . '">';
        }
        
        if (!empty($seoData->twitter_description)) {
            $metaTags[] = '<meta name="twitter:description" content="' . e($seoData->twitter_description) . '">';
        }
        
        if (!empty($seoData->twitter_image)) {
            $metaTags[] = '<meta name="twitter:image" content="' . e($seoData->twitter_image) . '">';
        }
        
        return implode("\n    ", $metaTags);
    }

    /**
     * Generate structured data (JSON-LD)
     */
    public function generateStructuredData($type, $data)
    {
        $structuredData = [];
        
        switch ($type) {
            case 'product':
                $structuredData = $this->generateProductStructuredData($data);
                break;
            case 'blog':
                $structuredData = $this->generateBlogPostingStructuredData($data);
                break;
            case 'organization':
                $structuredData = $this->generateOrganizationStructuredData($data);
                break;
            case 'website':
                $structuredData = $this->generateWebSiteStructuredData($data);
                break;
            case 'breadcrumb':
                $structuredData = $this->generateBreadcrumbStructuredData($data);
                break;
        }
        
        if (!empty($structuredData)) {
            return '<script type="application/ld+json">' . json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        }
        
        return '';
    }

    /**
     * Generate product structured data
     */
    private function generateProductStructuredData($product)
    {
        if (!$product) {
            return [];
        }

        $mediaService = app(MediaService::class);
        $imageUrl = !empty($product->image) ? $mediaService->getImageUrl($product->image) : '';
        if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $imageUrl = url($imageUrl);
        }

        $brandName = 'Unknown';
        if (isset($product->brandRelation)) {
            $brandName = $product->brandRelation->name ?? 'Unknown';
        } elseif (isset($product->brand_name)) {
            $brandName = $product->brand_name;
        }

        $availability = 'https://schema.org/InStock';
        if (isset($product->availability) && $product->availability == 0) {
            $availability = 'https://schema.org/OutOfStock';
        }

        $price = 0;
        if (isset($product->variants) && count($product->variants) > 0) {
            $price = $product->variants[0]['special_price'] > 0 ? $product->variants[0]['special_price'] : $product->variants[0]['price'];
        }

        $structuredData = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name ?? '',
            'description' => strip_tags($product->short_description ?? $product->description ?? ''),
            'image' => $imageUrl,
            'sku' => $product->sku ?? '',
            'brand' => [
                '@type' => 'Brand',
                'name' => $brandName
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => url()->current(),
                'priceCurrency' => session('currency_code') ?? 'USD',
                'price' => $price,
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
            ]
        ];

        if (!empty($product->rating) && !empty($product->no_of_ratings)) {
            $structuredData['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->rating,
                'reviewCount' => $product->no_of_ratings,
            ];
        }

        return $structuredData;
    }

    /**
     * Generate blog posting structured data
     */
    private function generateBlogPostingStructuredData($blog)
    {
        if (!$blog) {
            return [];
        }

        $mediaService = app(MediaService::class);
        $imageUrl = !empty($blog->image) ? $mediaService->getImageUrl($blog->image) : '';
        if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $imageUrl = url($imageUrl);
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $blog->title ?? '',
            'description' => $blog->short_description ?? strip_tags(\Illuminate\Support\Str::limit($blog->description ?? '', 160)),
            'image' => $imageUrl,
            'author' => [
                '@type' => 'Organization',
                'name' => config('app.name')
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('storage/' . app(SettingService::class)->getSettings('web_settings', true, 'logo'))
                ]
            ],
            'datePublished' => isset($blog->created_at) ? $blog->created_at->toIso8601String() : '',
            'dateModified' => isset($blog->updated_at) ? $blog->updated_at->toIso8601String() : ''
        ];
    }

    /**
     * Generate WebSite structured data
     */
    private function generateWebSiteStructuredData($data)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $data['name'] ?? config('app.name'),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/products?search={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }

    /**
     * Generate organization structured data
     */
    private function generateOrganizationStructuredData($data)
    {
        $mediaService = app(MediaService::class);
        $logoUrl = !empty($data['logo']) ? asset('storage/' . $data['logo']) : '';

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $data['name'] ?? config('app.name'),
            'url' => url('/'),
            'logo' => $logoUrl,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $data['phone'] ?? '',
                'contactType' => 'Customer Service'
            ],
            'sameAs' => [
                $data['facebook_link'] ?? '',
                $data['twitter_link'] ?? '',
                $data['instagram_link'] ?? '',
                $data['linkedin_link'] ?? ''
            ]
        ];
    }

    /**
     * Generate breadcrumb structured data
     */
    private function generateBreadcrumbStructuredData($items)
    {
        if (empty($items)) {
            return [];
        }

        $listItems = [];
        $position = 1;

        // Add Home
        $listItems[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Home',
            'item' => url('/')
        ];
        
        foreach ($items as $item) {
            if (empty($item['name'])) continue;
            
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => strip_tags($item['name']),
                'item' => !empty($item['url']) ? (filter_var($item['url'], FILTER_VALIDATE_URL) ? $item['url'] : url($item['url'])) : url()->current()
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems
        ];
    }
}
