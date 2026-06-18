<?php

namespace App\Livewire\Products;

use App\Models\City;
use App\Models\Product;
use App\Models\Zipcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ProductService;
use App\Services\DeliveryService;
use App\Services\StoreService;
use App\Services\TranslationService;

class Details extends Component
{
    use WithFileUploads;

    protected $listeners = ['local_cart_data', 'city'];

    public $store_id;
    public $user_id;
    public function __construct()
    {
        $this->user_id = Auth::user() != '' ? Auth::user()->id : NUll;
    }

    public $product_details;

    public $product_id = "";

    public $pname = "";

    public $pdescription = "";
    public $image = "";

    public $pincode = "";
    public $city = "";

    public $relative_products = [];

    public function mount($slug)
    {
        if (request()->has('store')) {
            $storeSlug = request()->input('store');
            $store = \App\Models\Store::where('slug', $storeSlug)->first();
            
            if ($store) {
                session(['store_id' => $store->id]);
                session(['store_slug' => $store->slug]);
            }
        }

        $filter['slug'] = $slug;
        $user_id = $this->user_id;
        $this->store_id = session('store_id');
        $store_id = $this->store_id;

        $details = app(ProductService::class)->fetchProduct(user_id: $user_id, filter: $filter, is_detailed_data: 1, store_id: $store_id);

        // Check if product data is valid
        if (!isset($details['product']) || !is_array($details['product']) || empty($details['product'])) {
            $this->redirect('products', true);
            return;
        }

        $this->product_id = $details['product'][0]['id'] ?? null;
        if (!$this->product_id) {
            $this->redirect('products', true);
            return;
        }

        $product_ids = [$this->product_id];
        if (count($product_ids) >= 1) {
            $category_id = fetchDetails(Product::class, ['id' => $this->product_id], '*');
            $categories_id = $category_id[0]->category_id ?? "";
            $brand_id = $category_id[0]->brand ?? "";
            $tags = $category_id[0]->tags ?? "";

            $relative_products_id = Product::where('store_id', $store_id)
                ->where(function ($query) use ($categories_id, $brand_id) {
                    $query->where('category_id', $categories_id)
                        ->orWhere('brand', $brand_id);
                })
                ->whereNotIn('id', $product_ids)
                ->select('id')
                ->limit(10)
                ->get();

            $relative_id = $relative_products_id->pluck('id')->toArray();
            $relative_product = app(ProductService::class)->fetchProduct($user_id, "", $relative_id);
            $this->relative_products = $relative_product['product'] ?? [];
        }

        $this->product_details = (object) ($details['product'][0] ?? []);
        $this->pname = $details['product'][0]['name'] ?? '';
        $this->pdescription = $details['product'][0]['short_description'] ?? '';
        $this->image = $details['product'][0]['image'] ?? '';
    }
    public function render()
    {
        $product_id = $this->product_id;

        $store_id = $this->store_id;

        $deliverabilitySettings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);

        $product_faqs = app(ProductService::class)->getProductFaqs('', $this->product_id, '', '', 100, 0, 'id', 'desc');

        if ($product_id != "") {
            $siblingsProduct = getPreviousAndNextItemWithId(Product::class, $product_id, $store_id);
            $bread_crumb = [
                'page_main_bread_crumb' => '<a wire:navigate href="' . customUrl('products') . '">' . labels('front_messages.products', 'Products') . '</a>',
                'right_breadcrumb' => array(
                    '<a wire:navigate href="' . customUrl('products/' . $this->product_details->slug) . '">' . $this->pname . '</a>'
                )
            ];
        }
        $store_settings = app(StoreService::class)->getStoreSettings();
        $details_style = getProductDetailsStyle($store_settings);
        $language_code = app(TranslationService::class)->getLanguageCode();
        // dd($details_style);
        $seoService = app(\App\Services\SeoService::class);
        $productStructuredData = $seoService->generateStructuredData('product', $this->product_details);
        $breadcrumbStructuredData = $seoService->generateStructuredData('breadcrumb', [
            ['name' => labels('front_messages.products', 'Products'), 'url' => customUrl('products')],
            ['name' => $this->pname, 'url' => customUrl('products/' . $this->product_details->slug)]
        ]);

        return view($details_style, [
            'product_details' => $this->product_details,
            'relative_products' => $this->relative_products,
            'siblingsProduct' => $siblingsProduct,
            'product_id' => $product_id,
            'bread_crumb' => $bread_crumb,
            'deliverabilitySettings' => $deliverabilitySettings,
            'language_code' => $language_code,
            'product_faqs' => $product_faqs,
        ])->layoutData([
            'title' => $this->pname . " |",
            'metaKeys' =>  $this->pname,
            'metaDescription' =>  $this->pdescription,
            'metaImage' => $this->image,
            'structuredData' => $productStructuredData . "\n" . $breadcrumbStructuredData
        ]);
    }

    public function city($city)
    {
        $this->city = $city;
    }

    public function check_product_deliverability(Request $request)
    {
        $store_id = session('store_id');
        $deliverabilitySettings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);
        $validator = Validator::make(
            $request->all(),
            [
                'product_type' => 'required',
                'product_id' => 'required|exists:products,id'
            ]
        );

        if ($deliverabilitySettings[0]->product_deliverability_type == 'city_wise_deliverability') {
            $validator = Validator::make(
                $request->all(),
                [
                    'city' => 'required'
                ]
            );
            $request['pincode'] = null;
        } else {
            $validator = Validator::make(
                $request->all(),
                [
                    'pincode' => 'required'
                ]
            );
            $request['city'] = null;
        }
        if ($validator->fails()) {
            $errors = $validator->errors();
            $response['error'] = true;
            $response['message'] = $errors;
            return $response;
        }
        $pincode = $request['pincode'];
        $city = $request['city'];
        $city_id = "";
        $pincode_id = "";
        if ($deliverabilitySettings[0]->product_deliverability_type == 'city_wise_deliverability') {
            // City might be sent as JSON object with multiple languages
            if (is_string($city) && (str_starts_with($city, '{') || str_starts_with($city, '['))) {
                $city_decoded = json_decode($city, true);
                if (is_array($city_decoded) && isset($city_decoded['en'])) {
                    $city = $city_decoded['en'];
                }
            }
            
            $city_result = fetchDetails(City::class, ['name->en' => $city]);
            
            if (!empty($city_result) && count($city_result) > 0) {
                $city_id = $city_result[0]->id;
            } else {
                $city_id = "";
            }
        } else {
            $pincode_id = fetchDetails(Zipcode::class, ['zipcode' => $pincode]);
            if ($pincode_id != []) {
                $pincode_id = $pincode_id[0]->id;
            }
        }
        $product_id = $request['product_id'];
        $product_type = $request['product_type'];
        return app(DeliveryService::class)->checkProductDeliverable(product_id: $product_id, store_id: $store_id, city_id: $city_id, zipcode_id: $pincode_id, zipcode: $pincode, product_type: $product_type);
    }
}
