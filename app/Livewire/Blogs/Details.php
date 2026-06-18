<?php

namespace App\Livewire\Blogs;

use App\Models\Blog;
use Livewire\Component;
use App\Services\TranslationService;
use App\Services\MediaService;
use App\Services\SettingService;


class Details extends Component
{

    public $slug = "";

    public function mount($slug)
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $store_id = session("store_id");
        $blog = fetchDetails(Blog::class, ['store_id' => $store_id, 'status' => 1, 'slug' => $this->slug], '*');
        if (count($blog) == 0) {
            abort(404);
            return;
        }
        $language_code = app(TranslationService::class)->getLanguageCode();

        // Get the first blog item - ensure it's an object
        $blogItem = $blog->first();
        if (!$blogItem) {
            abort(404);
            return;
        }
         $blogImage = $blogArray['image'] ?? $blogItem->image ?? '';
                            $blogId = $blogArray['id'] ?? $blogItem->id;
                            $blog_img = !empty($blogImage) ? app(MediaService::class)->dynamic_image($blogImage, 1500) : '';
                            $language_code = app(TranslationService::class)->getLanguageCode();

        // Convert to array for safe access
        $blogArray = $blogItem->toArray();
        $blogId = $blogArray['id'] ?? $blogItem->id;
        $blogTitle = app(TranslationService::class)->getDynamicTranslation(Blog::class, 'title', $blogId, $language_code);

        // Get system settings for app name
        $system_settings = app(SettingService::class)->getSettings('system_settings', true);
        $system_settings = json_decode($system_settings, true);

        // Prepare SEO meta data
        $metaTitleValue = $blogArray['meta_title'] ?? $blogItem->meta_title ?? null;
        $metaTitle = !empty($metaTitleValue) ? $metaTitleValue : $blogTitle;

        $metaDescriptionValue = $blogArray['meta_description'] ?? $blogItem->meta_description ?? null;
        $shortDescriptionValue = $blogArray['short_description'] ?? $blogItem->short_description ?? null;
        $descriptionValue = $blogArray['description'] ?? $blogItem->description ?? '';

        $metaDescription = !empty($metaDescriptionValue) ? $metaDescriptionValue :
            (!empty($shortDescriptionValue) ? $shortDescriptionValue :
            \Illuminate\Support\Str::limit(strip_tags($descriptionValue), 160));

        $metaKeywords = $blogArray['meta_keywords'] ?? $blogItem->meta_keywords ?? '';
        $blogImage = $blogArray['image'] ?? $blogItem->image ?? '';
        $metaImage = !empty($blogImage) ? app(MediaService::class)->dynamic_image($blogImage, 1200) : '';
        $blogSlug = $blogArray['slug'] ?? $blogItem->slug ?? '';
        $blogUrl = customUrl('blogs/' . $blogSlug);

        // Deep linking URL for mobile app (adjust scheme and host based on your app configuration)
        $scheme = str_replace('://', '', $system_settings['deep_link_scheme'] ?? 'eshop');
        $host = $system_settings['deep_link_host'] ?? 'eshop-pro.eshopweb.store';
        $store_slug = session('store_slug') ?? '';
        $deepLinkUrl = $scheme . '://' . $host . '/blog/' . $blogSlug . ($store_slug ? '?store=' . $store_slug : '');

        $bread_crumb = [
            'page_main_bread_crumb' => '<a wire:navigate href="' . customUrl('blogs') . '">' . labels('front_messages.blogs', 'Blogs') . '</a>',
            'right_breadcrumb' => array(
                $blogTitle
            )
        ];

        $shareText = "Take a Look at this {$blogTitle} on " . ($system_settings['app_name'] ?? 'Our App');
        $webUrl = $blogUrl;

        $seoService = app(\App\Services\SeoService::class);
        $blogStructuredData = $seoService->generateStructuredData('blog', $blogItem);
        $breadcrumbStructuredData = $seoService->generateStructuredData('breadcrumb', [
            ['name' => labels('front_messages.blogs', 'Blogs'), 'url' => customUrl('blogs')],
            ['name' => $blogTitle, 'url' => $blogUrl]
        ]);

        return view('livewire.' . config('constants.theme') . '.blogs.details', [
            'blog' => $blog,
            'blogItem' => $blogItem,
            'blogArray' => $blogArray,
            'bread_crumb' => $bread_crumb,
            'system_settings' => $system_settings,
            'deepLinkUrl' => $deepLinkUrl,
            'blog_img' => $blog_img,
            'blogUrl' => $blogUrl,
            'blogTitle' => $blogTitle,
            'shareText' => $shareText,
            'webUrl' => $webUrl,
        ])->layoutData([
            'title' => $metaTitle . ' |',
            'metaKeys' => $metaKeywords,
            'metaDescription' => $metaDescription,
            'metaImage' => $metaImage,
            'structuredData' => $blogStructuredData . "\n" . $breadcrumbStructuredData
        ]);
    }
}
