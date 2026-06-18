<?php

namespace App\Livewire\Products;

use App\Models\Attribute_values;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductService;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Services\TranslationService;
use App\Services\SettingService;
class Listing extends Component
{
    #[Title('Product Listing |')]
    public $user_id;

    public $slug;
    public $routeType = "";
    public $section;
    public $category;
    public function __construct()
    {
        $this->user_id = Auth::user() != '' ? Auth::user()->id : NUll;
    }

    function mount(Request $request, $slug = null, $section = null)
    {
        $this->slug = $slug;
        $url = $request->url();
        $store_id = session('store_id');
        if (str_contains($url, '/section/')) {
            $this->routeType = 'section';
            if ($this->slug != "") {
                $this->section = fetchDetails(Section::class, ['id' => $this->slug, 'store_id' => $store_id]);
                if ($this->section == []) {
                    $this->redirect('products', true);
                }
            }
        } elseif (str_contains($url, '/categories/')) {
            $this->routeType = 'category';
            if ($this->slug != "") {
                $this->category = fetchDetails(Category::class, ['slug' => $this->slug, 'store_id' => $store_id]);
                if ($this->category == []) {
                    $this->redirect('products', true);
                }
            }
        }
    }

    public function render(Request $request)
    {
        // dd($request);
        $system_settings = app(SettingService::class)->getSettings('system_settings', true);
        $system_settings = json_decode($system_settings, true);
        $right_breadcrumb = [];
        $language_code = app(TranslationService::class)->getLanguageCode();
        $filter = [];
        $store_id = session('store_id');
        // category filter
        $category_id = null;
        $sub_categories = [];
        if ($this->category != []) {
            $category = $this->category;
            $breadcrumb = '<a href="' . customUrl('categories') . '"> Categories </a> <ion-icon class="align-text-top icon"
                name="chevron-forward-outline"></ion-icon>' . app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category[0]->id, $language_code);
            array_push($right_breadcrumb, $breadcrumb);
            $sub_categories = fetchDetails(Category::class, ['parent_id' => $category[0]->id]);
            $category_id = $category[0]->id;
        }
        // category filter

        // section filter
        $section = $this->section;
        if ($section != []) {
            if ($section[0]->product_type == 'custom_products') {
                $product_ids = explode(",", $section[0]->product_ids);
                $product_variant_ids = [];
                $product_variants = app(ProductService::class)->fetchProduct(null, null, $product_ids);
                if (count($product_variants) >= 1) {
                   foreach ($product_variants['product'] as $product) {
                        array_push($product_variant_ids, $product['variants'][0]['id']);
                    }
                }
                $filter['product_variant_ids'] = $product_variant_ids;
            } else {
                $category_id = explode(",", $section[0]->categories);
                $filter['product_type'] = $section[0]->product_type;
            }
            $breadcrumb = 'Section <ion-icon class="align-text-top icon"
                    name="chevron-forward-outline"></ion-icon>' . app(TranslationService::class)->getDynamicTranslation(Section::class, 'short_description', $section[0]->id, $language_code);
            array_push($right_breadcrumb, $breadcrumb);
        }
        // section filter

        $sortBy = $request->query('sort');
        $bySearch = $request->query('search');

        // by search filter
        if ($bySearch != null) {
            $filter['search'] = $bySearch;
            $breadcrumb = 'Search <ion-icon class="align-text-top icon"
            name="chevron-forward-outline"></ion-icon>' . $bySearch;
            array_push($right_breadcrumb, $breadcrumb);
        }
        // by search filter

        $sort = "";
        $order = "";
        $attribute_values = '';
        $attribute_names = '';
        foreach ($request->query() as $key => $value) {
            if (strpos($key, 'filter-') !== false) {
                if (!empty($attribute_values)) {
                    $attribute_values .= "|" . $request->query($key, true);
                } else {
                    $attribute_values = $request->query($key, true);
                }

                $key = str_replace('filter-', '', $key);
                if (!empty($attribute_names)) {
                    $attribute_names .= "|" . $key;
                } else {
                    $attribute_names = $key;
                }
            }
        }
        $attribute_values = explode('|', $attribute_values ?? '');
        $attribute_names = explode('|', $attribute_names ?? '');
        $filter['attribute_value_ids'] = app(ProductService::class)->getAttributeIdsByValue($attribute_values, $attribute_names);
        // dd($filter['attribute_value_ids']);
        $filter_attribute_value_ids = $filter['attribute_value_ids'];

        // brand filter
        if (isset($request->query()['brand']) && !empty($request->query()['brand'])) {
            $brand_slug = $request->query()['brand'];
            $brand = fetchDetails(Brand::class, ['slug' => $brand_slug, 'status' => '1']);
            $filter['brand'] = isset($brand[0]->id) ? $brand[0]->id : null;
            $breadcrumb = '<a href="' . customUrl('brands') . '"> Brands </a> <ion-icon class="align-text-top icon"
            name="chevron-forward-outline"></ion-icon>' . app(TranslationService::class)->getDynamicTranslation(Brand::class, 'name', isset($brand[0]->id) ? $brand[0]->id : null, $language_code);
            array_push($right_breadcrumb, $breadcrumb);
        }
        // brand filter

        // tags filter
        if (isset($request->query()['tag']) && !empty($request->query()['tag'])) {
            $tags = $request->query()['tag'];
            $filter['tags'] = $tags;
            $breadcrumb = 'Tags<ion-icon class="align-text-top icon"
            name="chevron-forward-outline"></ion-icon>' . $tags;
            array_push($right_breadcrumb, $breadcrumb);
        }
        // tags filter
        // product sort by
        if ($sortBy == "top-rated") {
            $filter['product_type'] = "top_rated_product_including_all_products";
        } elseif ($sortBy == "latest-products") {
            $filter['product_type'] = "new_added_products";
            $sort = 'products.id';
            $order = 'desc';
        } elseif ($sortBy == "oldest-first") {
            $filter['product_type'] = "old_products_first";
            $sort = 'products.id';
            $order = 'asc';
        } elseif ($sortBy == "price-asc") {
            $sort = 'product_variants.price';
            $order = 'asc';
        } elseif ($sortBy == "price-desc") {
            $sort = 'product_variants.price';
            $order = 'desc';
        }
        $sorted_by = "";
        $page = request()->get('page', 1);
        $limit = request()->get('perPage', 20);
        $offset = ($page - 1) * $limit;
        if (isset($request->query()['sort']) && !empty($request->query()['sort'])) {
            $sorted_by = $request->query()['sort'];
        }
        // product sort by

        // min max price filter
        if (isset($request->query()['min_price']) && ($request->query()['min_price'] != null) && isset($request->query()['max_price']) && ($request->query()['max_price'] != null)) {
            $filter['min_price'] = $request->query()['min_price'];
            $filter['max_price'] = $request->query()['max_price'];
        }
        // min max price filter
        $brands = fetchDetails(Brand::class, ['store_id' => $store_id, 'status' => '1']);
        foreach ($brands as $brand) {
            $brand->is_checked = false;
            if (isset($filter['brand']) && !empty($filter['brand'])) {
                $is_checked = ($brand->id == $filter['brand']) ? true : false;
                $brand->is_checked = $is_checked;
            }
        }
        $product_list = app(ProductService::class)->fetchProduct($this->user_id, $filter, null, $category_id, $limit, $offset, $sort, $order, null, null, null, null, $store_id);
        // dd($product_list);

        if (isset($request->query()['min_price']) && ($request->query()['min_price'] != null) && isset($request->query()['max_price']) && ($request->query()['max_price'] != null)) {
            $selected_min_price = $request->query()['min_price'];
            $selected_max_price = $request->query()['max_price'];
        }
        $min_max_price = [
            'min_price' => $product_list['min_price'],
            'max_price' => $product_list['max_price'],
            'selected_min_price' => $selected_min_price ?? $product_list['min_price'],
            'selected_max_price' => $selected_max_price ?? $product_list['max_price']
        ];

        // product filter by attributes
        // Collect the set of attribute_value_ids present across this store's
        // products to build the filter sidebar. Previously this hydrated every
        // Product model in the store plus its productAttributes relation just to
        // read one CSV column — heavy on large catalogs and run on every render.
        // A single join + pluck of only the CSV column is far lighter.
        $attr_value_csvs = \App\Models\Product_attributes::query()
            ->join('products', 'products.id', '=', 'product_attributes.product_id')
            ->where('products.store_id', $store_id)
            ->pluck('product_attributes.attribute_value_ids')
            ->all();
        $attr_val_ids = $attr_value_csvs
            ? array_unique(array_filter(array_merge(...array_map('str_getcsv', $attr_value_csvs))))
            : [];

        $attributeData = Attribute_values::whereIn('attribute_values.id', $attr_val_ids)
            ->join('attributes as a', 'attribute_values.attribute_id', '=', 'a.id')
            ->select('attribute_values.id as attribute_value_id', 'a.name as attribute_name', 'attribute_values.value as attribute_values', 'attribute_values.swatche_type', 'attribute_values.swatche_value')
            ->where('a.status', 1)
            ->get()->toArray();
        $groupedAttributes = [];
        foreach ($attributeData as $item) {
            $attributeName = $item['attribute_name'];
            if (!isset($groupedAttributes[$attributeName])) {
                $groupedAttributes[$attributeName] = [
                    'attribute_name' => $attributeName,
                    'attribute_values' => [],
                    'swatche_type' => [],
                    'swatche_value' => []
                ];
            }
            $groupedAttributes[$attributeName]['attribute_values'][] = $item['attribute_values'];
            $groupedAttributes[$attributeName]['swatche_type'][] = $item['swatche_type'];
            $groupedAttributes[$attributeName]['swatche_value'][] = $item['swatche_value'];
            $is_checked = in_array($item['attribute_value_id'], $filter_attribute_value_ids);
            $groupedAttributes[$attributeName]['is_checked'][] = $is_checked;
        }
        // product filter by attributes

        $bread_crumb['page_main_bread_crumb'] = '<a href="' . customUrl('products') . '">' . labels('front_messages.products', 'Products') . '</a>';

        if (count($right_breadcrumb) >= 1) {
            $bread_crumb['right_breadcrumb'] = $right_breadcrumb;
        }

        // per page
        $perPage = request()->get('perPage', 20);
        if (isset($request->query()['perPage']) && ($request->query()['perPage'] != null)) {
            $perPage = $request->query()['perPage'];
            $perPage = (int)($perPage);
            if ($perPage == 0) {
                $perPage = 20;
            }
        }
        if (!in_array($perPage, [12, 16, 20, 24])) {
            $perPage = 20;
        }
        // per page

        $products = collect($product_list['product']);
        // dd($products);
        $page = request()->get('page', 1);
        // dd($page);

        $total = $product_list['total'] ?? count($product_list['product']);
        if (isset($page)) {
            $paginator = new LengthAwarePaginator(
                $products,
                // $product_list['total'],
                $total,
                $perPage,
                $page,
                ['path' => url()->current()]
            );

        }
        $product_list['product'] = $paginator->items();
        // dd($product_list['product']);
        $product_list['links'] = $paginator->links();
        $language_code = app(TranslationService::class)->getLanguageCode();
        $view_mode = $request->query('mode') ?? "";
        return view(
            'livewire.' . config('constants.theme') . '.products.listing',
            [
                'products_listing' => $products,
                // 'total_products' => $product_list['total'],
                'total_products' => $total,
                'min_max_price' => $min_max_price,
                'links' => $product_list['links'],
                'Attributes' => $groupedAttributes,
                'filters' => $filter,
                'bySearch' => $bySearch,
                'sub_categories' => $sub_categories,
                'sorted_by' => $sorted_by,
                'brands' => $brands,
                'bread_crumb' => $bread_crumb,
                'view_mode' => $view_mode,
                'perPage' => $perPage,
                'products_type' => "regular",
                'language_code' => $language_code
            ]
        );
    }
}
