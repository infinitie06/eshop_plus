<?php

namespace App\Livewire;

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategorySliders;
use App\Models\Offer;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use App\Services\TranslationService;
use App\Services\ProductService;
use App\Services\ComboProductService;
use App\Services\StoreService;
use App\Services\MediaService;
use App\Services\SettingService;
use App\Services\MailService;
use Illuminate\Support\Facades\Http;

class Home extends Component
{
    public $user_id;
    public function __construct()
    {
        $this->user_id = Auth::user() != '' ? Auth::user()->id : NUll;
    }
    public $store_id = "";
    public function render(Request $request)
    {
        $store_id = session('store_id');
        $this->store_id = $store_id;
        $sliders = getSliders("", "", "", $store_id);

        $categoryController = app(CategoryController::class);
        $categories = $categoryController->getCategories(sort: 'row_order', order: "ASC", store_id: $store_id);
        $categories = $categories->original;
        // dd($categories);
        $BrandController = app(BrandController::class);
        $brands = $BrandController->getBrands("", "", "", "", "ASC", $store_id);

        $categories_section = $this->getCategoriesSection();
        $sections = $this->sections();
        $offers = $this->getOffers($store_id);
        $settings = app(SettingService::class)->getSettings('web_settings', true);
        $settings = json_decode($settings);
        $rating = app(ProductService::class)->fetchRating('', '', 8, 0, '', 'desc', '', 1);
        $ratings = isset($rating) && !empty($rating) ? $rating['product_rating'] : [];
        $blogs = fetchDetails(Blog::class, ['store_id' => $store_id, 'status' => 1], '*');
        $blogs_count = count($blogs);
        $store_settings = app(StoreService::class)->getStoreSettings();
        $home_theme = getHomeTheme($store_settings);
        // dd($home_theme);
        // return view('livewire.' . config('constants.theme') . '.home.home', [
        $seoService = app(\App\Services\SeoService::class);
        $websiteData = $seoService->generateStructuredData('website', ['name' => $store_settings['app_name'] ?? config('app.name')]);
        
        $orgData = $seoService->generateStructuredData('organization', [
            'name' => $store_settings['app_name'] ?? config('app.name'),
            'logo' => $store_settings['logo'] ?? '',
            'phone' => $store_settings['support_number'] ?? '',
        ]);

        return view($home_theme, [
            'sliders' => $sliders,
            'categories' => $categories,
            'brands' => $brands,
            'sections' => $sections,
            'offers' => $offers,
            'categories_section' => $categories_section,
            'settings' => $settings,
            'ratings' => $ratings,
            'blogs' => $blogs,
            'blogs_count' => $blogs_count,
        ])->layoutData([
            'title' => "Home |",
            'structuredData' => $websiteData . "\n" . $orgData
        ]);
    }
    public function getOffers($store_id)
    {
        $offers = fetchDetails(Offer::class, ['store_id' => $store_id], '*');
        $translationService = app(TranslationService::class);
        $mediaService = app(MediaService::class);
        $language_code = $translationService->getLanguageCode();
        $mediaPath = config('constants.MEDIA_PATH');

        foreach ($offers as $key => $offer) {
            $image = !empty($offer->image) && file_exists(public_path($mediaPath . $offer->image))
                ? $mediaService->getImageUrl($offer->image)
                : $mediaService->getImageUrl('offerPlaceHolder.png', '', '', 'image', 'NO_USER_IMAGE');
            $banner_image = !empty($offer->banner_image) && file_exists(public_path($mediaPath . $offer->banner_image))
                ? $mediaService->getImageUrl($offer->banner_image)
                : $mediaService->getImageUrl('offerPlaceHolder.png', '', '', 'image', 'NO_USER_IMAGE');
            $offers[$key]->image = $image;
            $offers[$key]->title = $translationService->getDynamicTranslation(Offer::class, 'title', $offers[$key]->id, $language_code);
            $offers[$key]->banner_image = $banner_image;
            if ($offer->type == "categories") {
                $link = fetchDetails(Category::class, ['id' => $offer->type_id], 'slug');
                if ($link->isNotEmpty()) {
                    $offers[$key]->link = customUrl('categories/' . $link[0]->slug . '/products');
                }
            } elseif ($offer->type == "brand") {
                $link = fetchDetails(Brand::class, ['id' => $offer->type_id], 'slug');
                if ($link->isNotEmpty()) {
                    $offers[$key]->link = customUrl('products/?brand=' . $link[0]->slug);
                }
            }
        }
        return $offers;
    }
    public function getCategoriesSection()
    {
        $store_id = session('store_id');
        $sliders = fetchDetails(CategorySliders::class, ['store_id' => $store_id, 'status' => 1], '*');
        $translationService = app(TranslationService::class);
        $mediaService = app(MediaService::class);
        $language_code = $translationService->getLanguageCode();
        if (count($sliders) >= 1) {
            foreach ($sliders as $key => $slider) {
                $categories_detail = fetchDetails(Category::class, where_in_key: "id", where_in_value: explode(",", $slider->category_ids));
                $sliders[$key]->banner_image = $mediaService->dynamic_image($mediaService->getImageUrl($slider->banner_image), 620);
                $sliders[$key]->title = $translationService->getDynamicTranslation(CategorySliders::class, 'title', $sliders[$key]->id, $language_code);

                foreach ($categories_detail as $k => $details) {
                    $categories_detail[$k]->image = $mediaService->dynamic_image($mediaService->getImageUrl($details->image), 400);
                    $categories_detail[$k]->name = $translationService->getDynamicTranslation(Category::class, 'name', $categories_detail[$k]->id, $language_code);
                    $categories_detail[$k]->banner = $mediaService->dynamic_image($mediaService->getImageUrl($details->banner), 400);
                }
                $sliders[$key]->categories_detail = $categories_detail;
            }
        }
        return $sliders;
    }

    public function sections()
    {
        $store_id = session('store_id');
        $limit =  12;
        $offset =  0;
        $sections = Section::where('store_id', $store_id)
            ->orderBy('row_order')
            ->offset($offset)
            ->limit($limit)
            ->get();
        $translationService = app(TranslationService::class);
        $mediaService = app(MediaService::class);
        $productService = app(ProductService::class);
        $comboProductService = app(ComboProductService::class);
        $language_code = $translationService->getLanguageCode();
        $filters['show_only_active_products'] = true;
        if (!empty($sections)) {
            for ($i = 0; $i < count($sections); $i++) {
                $product_ids = explode(',', (string)$sections[$i]->product_ids);
                $product_ids = array_filter($product_ids);
                $product_categories = (isset($sections[$i]->categories) && !empty($sections[$i]->categories) && $sections[$i]->categories != NULL) ? explode(',', $sections[$i]->categories ?? '') : null;
                if (isset($sections[$i]->product_type) && !empty($sections[$i]->product_type)) {
                    $filters['product_type'] = (isset($sections[$i]->product_type)) ? $sections[$i]->product_type : null;
                }
                if ($sections[$i]->style == "style_1") {
                    $limit = 12;
                } elseif ($sections[$i]->style == "style_2" || $sections[$i]->style == "style_3") {
                    $limit = 6;
                }
                if ($sections[$i]->product_type === "custom_combo_products") {
                    $combo_products = $comboProductService->fetchComboProduct(user_id: $this->user_id, id: (isset($product_ids)) ? $product_ids : null, limit: $limit, store_id: $store_id);
                } else {
                    $products = $productService->fetchProduct(user_id: $this->user_id, filter: (isset($filters)) ? $filters : null, id: (isset($product_ids)) ? $product_ids : null, category_id: $product_categories, limit: $limit, store_id: $this->store_id, is_detailed_data: 0);
                }
                $sections[$i]->title = $translationService->getDynamicTranslation(Section::class, 'title', $sections[$i]->id, $language_code);
                $sections[$i]->short_description = $translationService->getDynamicTranslation(Section::class, 'short_description', $sections[$i]->id, $language_code);
                $sections[$i]->banner_image = $mediaService->dynamic_image($mediaService->getMediaImageUrl($sections[$i]->banner_image), 800);
                $sections[$i]->slug = Str::slug($sections[$i]->title);
                $sections[$i]->filters = (isset($products['filters'])) ? $products['filters'] : [];
                if ($sections[$i]->product_type === "custom_combo_products") {
                    $sections[$i]->product_details = (object)$combo_products['combo_product'];
                } else {
                    $sections[$i]->product_details = (object)$products['product'];
                }
            }
        }
        return $sections;
    }

    public function sendMailTemplate($to, $template_key, $data = ['username' => 'jay', 'appname' => 'Ezeemart'], $givenLanguage = "")
    {
        $response = app(MailService::class)->sendMailTemplate(to: $to, template_key: $template_key, data: $data);
        return $response;
    }
    public function addToFavorite($productId)
    {

        $response = Http::post(route('add_to_favorites'), [
            'product_id' => $productId,
            'product_type' => 'product',
            'user_id' => Auth::id()
        ])->json();

        if (!$response['error']) {
            $this->dispatch('wishlistUpdated');
        }
    }


    public function removeFromFavorite($productId)
    {


        $response = Http::post(route('remove_from_favorite'), [
             'product_id' => $productId,
            'product_type' => 'product',
            'user_id' => Auth::id()
        ])->json();

        if (!$response['error']) {
            $this->dispatch('wishlistUpdated');
        }
    }
}
