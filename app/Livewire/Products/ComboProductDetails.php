<?php

namespace App\Livewire\Products;

use App\Models\ComboProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ProductService;
use App\Services\ComboProductService;
use App\Services\DeliveryService;

class ComboProductDetails extends Component
{
    use WithFileUploads;

    protected $listeners = ['local_cart_data'];

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
    public $slug = "";

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
        $this->slug = $slug;
        $reference_id = request()->get('ref') ?? "";
        $this->reference_id = $reference_id;
    }
    public function render()
    {
        $user_id =  $this->user_id;
        $filter['slug'] = $this->slug;
        $store_id = session('store_id');
        $details = app(ComboProductService::class)->fetchComboProduct(user_id: $user_id, filter: $filter, store_id: $store_id);
        if (count($details['combo_product']) < 1) {
            abort(404);
            return;
        }
        $reference_id = request()->get('ref') ?? "";
        $this->product_id = $details['combo_product'][0]->id ?? "";

        if ($this->product_id != "") {
            $combo_product = fetchDetails(ComboProduct::class, ['id' => $this->product_id], 'product_ids')[0];
            $product_ids = explode(",", $combo_product->product_ids);

            $primaryProducts = Product::select('category_id', 'id')
                ->whereIn('id', $product_ids)
                ->get();

            // Step 4: Extract category IDs
            $category_ids = $primaryProducts->pluck('category_id')->unique();

            // Step 5: Get related products in same categories but not the original ones
            $relatedProducts = Product::select('category_id', 'id')
                ->whereIn('category_id', $category_ids)
                ->whereNotIn('id', $product_ids)
                ->get();

            // Step 6: Merge both collections (if needed)
            $categories_and_relative_products = $primaryProducts->merge($relatedProducts);

            // Step 7: Extract related product IDs only
            $relative_product_ids = $relatedProducts->pluck('id')->toArray();

            $relative_product = app(ProductService::class)->fetchProduct(user_id: $user_id, id: $relative_product_ids, store_id: $store_id);
        }
        $this->product_details = $details['combo_product'][0];
        $this->pname = $details['combo_product'][0]->name;
        $this->pdescription = $details['combo_product'][0]->short_description;
        $this->image = $details['combo_product'][0]->image;
        if ($this->product_id != "") {
            $store_id = session('store_id');
            $siblingsProduct = getPreviousAndNextItemWithId(ComboProduct::class, $this->product_id, $store_id);
            $bread_crumb = [
                'page_main_bread_crumb' => '<a wire:navigate href="' . customUrl('combo-products') . '">'  . labels('front_messages.combo_products', 'Combo Products') . '</a>',
                'right_breadcrumb' => array(
                    '<a wire:navigate href="' . customUrl('combo-products/' . $this->product_details->slug) . '">' . $this->pname . '</a>'
                )
            ];
        }
        $deliverabilitySettings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);

        $product_faqs = app(ComboProductService::class)->getComboProductFaqs('', $this->product_id, '', '', 100, 0, 'id', 'desc');

        return view('livewire.' . config('constants.theme') . '.products.combo-details', [
            // return view('livewire.' . config('constants.theme') . '.products.combo-detailsStyleTwo', [
            'product_details' => $details['combo_product'][0],
            'relative_products' => $relative_product['product'],
            'siblingsProduct' => $siblingsProduct,
            'product_id' => $this->product_id,
            'bread_crumb' => $bread_crumb,
            'deliverabilitySettings' => $deliverabilitySettings,
            'reference_id' => $reference_id,
            'product_faqs' => $product_faqs,

        ])->layoutData([
            'title' => $this->pname . " |",
            'metaKeys' =>  $this->pname,
            'metaDescription' =>  $this->pdescription,
            'metaImage' => $this->image
        ]);
    }
}
