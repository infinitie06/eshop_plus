<?php

namespace App\Http\Controllers\App\v1;

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ComboProductRatingController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\admin\OrderController;
use App\Http\Controllers\Admin\ProductRatingController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Log;
use App\Libraries\Paypal;
use App\Libraries\Paystack;
use App\Libraries\Phonepe;
use App\Libraries\Razorpay;
use App\Libraries\Shiprocket;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CategorySliders;
use App\Models\City;
use App\Models\ComboProduct;
use App\Models\ComboProductFaq;
use App\Models\Currency;
use App\Models\Favorite;
use App\Models\Language;
use App\Models\Media;
use App\Models\Offer;
use App\Models\OfferSliders;
use App\Models\Order;
use App\Models\OrderBankTransfers;
use App\Models\OrderItems;
use App\Models\Otps;
use App\Models\PaymentRequest;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\Product_variants;
use App\Models\ProductFaq;
use App\Models\Role;
use App\Models\SearchHistory;
use App\Models\Section;
use App\Models\SellerStore;
use App\Models\Slider;
use App\Models\StorageType;
use App\Models\Store;
use App\Models\Brand;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Tax;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserFcm;
use App\Models\Zipcode;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\TranslationService;
use App\Traits\HandlesValidation;
use App\Services\ProductService;
use App\Services\ComboProductService;
use App\Services\CartService;
use App\Services\DeliveryService;
use App\Services\MediaService;
use App\Services\ShiprocketService;
use App\Services\CurrencyService;
use App\Services\SettingService;
use App\Services\OrderService;
use App\Services\WalletService;
use App\Services\FirebaseNotificationService;
use App\Services\PromoCodeService;
use Illuminate\Support\Arr;

class ApiController extends Controller
{
    use HandlesValidation;
    /*
---------------------------------------------------------------------------
Defined Methods:-
---------------------------------------------------------------------------

    1. user-registration
        - login
        - update_fcm
        - reset_password
        - get_login_identity
        - verify_user
        - register_user
    2. get_categories
    3. get_cities
    4. get_products
    5. get_slider_images
    6. get_settings
    7. update_user
    8. delete_user

    9. favorites
        -add_to_favorites
        -remove_from_favorites
        -get_favorites

    10. user_addresses
        -add_address
        -update_address
        -delete_address
        -get_address

    11. get_combo_products
    12. get_user_cart
    13. get_sections
    14. get_zipcode_by_city_id
    15. validate_promo_code
    16. place_order
    17. remove_from_cart
    18. manage_cart
    19. clear_cart
    20. get_orders
    21. update_order_item_status
    22. get_faqs
    23. get_offer_images
    24. get_ticket_types
    25. add_ticket
    26. edit_ticket
    27. get_tickets
    28. get_messages
    29. is_product_delivarable
    30. check_cart_products_delivarable
    31. get_sellers
    32. get_promo_codes
    33. get_stores
    34. get_brands
    35. sign_up
    36. delete_social_account
    37. add_product_faqs
    38. get_product_faqs
    39. send_message
    40. get_zipcodes
    41. update_order_status
    42. delete_order
    43. validate_refer_code
    44. get_notifications
    45. add_transaction
    46. transactions
    47. set_product_rating
    48. get_product_rating
    49. delete_product_rating
    50. check_shiprocket_serviceability
    51. send_withdrawal_request
    52. get_withdrawal_request
    53. send_bank_transfer_proof
    54. download_link_hash
    55. get_offers_sliders
    56. get_categories_sliders
    57. set_combo_product_rating
    58. get_combo_product_rating
    59. delete_combo_product_rating

---------------------------------------------------------------------------
---------------------------------------------------------------------------

*/
    public function login(Request $request)
    {
        /*
        Example:
        mobile : 9876543210
        country_code : +91
        password : 12345678
    */

        $rules = [
            'mobile' => 'required|numeric',
            'country_code' => 'required|string|max:10',
            'password' => 'required|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            // Find user by both mobile and country_code
            $user = User::where('mobile', $request->mobile)
                ->where('country_code', $request->country_code)
                ->first();

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'User not found for this mobile and country code',
                    'language_message_key' => 'user_not_found_for_country_code',
                ], 404);
            }

            // Validate password manually (Auth::attempt() doesn't support dual-field by default)
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid credentials',
                    'language_message_key' => 'invalid_credentials',
                ], 401);
            }

            // Generate token
            $token = $user->createToken('authToken')->plainTextToken;

            // Fetch FCM IDs
            $fcm_ids = UserFcm::where('user_id', $user->id)->pluck('fcm_id')->toArray();

            $user_data = [
                'id' => $user->id ?? '',
                'ip_address' => $user->ip_address ?? '',
                'username' => $user->username ?? '',
                'email' => $user->email ?? '',
                'mobile' => $user->mobile ?? '',
                'country_code' => $user->country_code ?? '',
                'image' => app(MediaService::class)->getMediaImageUrl($user->image, 'USER_IMG_PATH'),
                'balance' => $user->balance ?? '0',
                'activation_selector' => $user->activation_selector ?? '',
                'activation_code' => $user->activation_code ?? '',
                'forgotten_password_selector' => $user->forgotten_password_selector ?? '',
                'forgotten_password_code' => $user->forgotten_password_code ?? '',
                'forgotten_password_time' => $user->forgotten_password_time ?? '',
                'remember_selector' => $user->remember_selector ?? '',
                'remember_code' => $user->remember_code ?? '',
                'created_on' => $user->created_on ?? '',
                'last_login' => $user->last_login ?? '',
                'active' => $user->active ?? '',
                'company' => $user->company ?? '',
                'address' => $user->address ?? '',
                'bonus' => $user->bonus ?? '',
                'cash_received' => $user->cash_received ?? '0.00',
                'dob' => $user->dob ?? '',
                'country_code' => $user->country_code ?? '',
                'city' => $user->city ?? '',
                'area' => $user->area ?? '',
                'street' => $user->street ?? '',
                'pincode' => $user->pincode ?? '',
                'apikey' => $user->apikey ?? '',
                'referral_code' => $user->referral_code ?? '',
                'friends_code' => $user->friends_code ?? '',
                'fcm_id' => array_values($fcm_ids) ?? '',
                'latitude' => $user->latitude ?? '',
                'longitude' => $user->longitude ?? '',
                'created_at' => $user->created_at ?? '',
                'type' => $user->type ?? '',
                'is_notification_on' => $user->is_notification_on ?? '',
            ];

            return response()->json([
                'error' => false,
                'message' => 'User Logged in successfully',
                'language_message_key' => 'user_logged_in_successfully',
                'token' => $token,
                'user' => $user_data,
            ]);
        }
    }

    public function get_categories(CategoryController $categoryController, Request $request)
    {
        /*
            store_id:3
            id:15               // optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort:               id / name // { default -row_id } optional
            order:DESC/ASC      // { default - ASC } optional
            has_child_or_item:false { default - true}  optional
                                */

        $rules = [
            'id' => 'numeric|exists:categories,id',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'store_id' => 'required|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';

            $id = $request->filled('id') ? (int) $request->input('id') : '';
            $ids = $request->filled('ids') ? $request->input('ids') : '';
            $search = $request->filled('search') ? trim($request->input('search')) : '';
            $limit = $request->filled('limit') ? (int) $request->input('limit') : 25;
            $offset = $request->filled('offset') ? (int) $request->input('offset') : 0;
            $sort = $request->filled('sort') ? $request->input('sort') : 'row_order';
            $order = $request->filled('order') ? $request->input('order') : 'ASC';
            $has_child_or_item = $request->filled('has_child_or_item') ? $request->input('has_child_or_item') : 'true';

            $response = ['message' => 'Category(s) retrieved successfully'];
            $language_code = $request->attributes->get('language_code');
            $cat_res = $categoryController->get_categories($id, $limit, $offset, $sort, $order, $has_child_or_item, '', '', '', $store_id, $search, $ids, $language_code);
            // dd($cat_res);
            $popular_categories = $categoryController->get_categories(NULL, "", "", 'clicks', 'DESC', 'false', "", "", "", $store_id, "", "", $language_code);

            return response()->json([
                'error' => $cat_res->original['categories']->isEmpty() ? true : false,
                'total' => $cat_res->original['total'],
                'message' => $cat_res->original['categories']->isEmpty() ? 'Category does not exist' : 'Category retrieved successfully',
                'language_message_key' => $cat_res->original['categories']->isEmpty() ? 'categories_does_not_exist' : 'categories_retrived_successfully',
                'data' => $cat_res->original['categories'],
                'popular_categories' => $popular_categories->original['categories'],
            ]);
        }
    }

    public function get_cities(AreaController $areaController, Request $request)
    {
        /*
           sort:               // { c.name / c.id } optional
           order:DESC/ASC      // { default - ASC } optional
           limit:25            // { default - 25 } optional
           offset:0            // { default - 0 } optional
           search:value        // {optional}
       */
        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $limit = $request->filled('limit') ? (int) $request->input('limit') : 25;
            $offset = $request->filled('offset') ? (int) $request->input('offset') : 0;
            $sort = $request->filled('sort') ? $request->input('sort') : 'name';
            $order = $request->filled('order') ? $request->input('order') : 'ASC';
            $search = $request->filled('search') ? trim($request->input('search')) : '';
            $language_code = $request->attributes->get('language_code');
            $city_res = $areaController->getCitiesList($sort, $order, $search, $limit, $offset, $language_code);
            return response()->json($city_res->original);
        }
    }
    public function get_products(Request $request)
    {
        $rules = [
            'store_id' => 'required_without_all:slug,store_slug|exists:stores,id',
            'store_slug' => 'sometimes|string|exists:stores,slug',
            'id' => 'sometimes|numeric|exists:products,id',
            'product_ids' => 'sometimes|string',
            'product_variant_ids' => 'sometimes|string',
            'search' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'attribute_value_ids' => 'sometimes',
            'sort' => 'sometimes|string',
            'limit' => 'sometimes|numeric',
            'offset' => 'sometimes|numeric',
            'order' => 'sometimes|string|alpha',
            'is_similar_products' => 'sometimes|numeric',
            'top_rated_product' => 'sometimes|numeric',
            'min_price' => 'sometimes|numeric|lte:max_price',
            'max_price' => 'sometimes|numeric|gte:min_price',
            'discount' => 'sometimes|numeric',
            'zipcode' => 'sometimes|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            // Resolve store_id from store_slug if provided
            if ($request->filled('store_slug') && !$request->filled('store_id')) {
                $store = fetchDetails(Store::class, ['slug' => $request->input('store_slug')], 'id');
                if ($store->isEmpty()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Store not found',
                        'language_message_key' => 'store_not_found',
                        'data' => []
                    ]);
                }
                $store_id = (int) $store[0]->id;
            } else {
                $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';
            }

            $tags = [];
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order = $request->filled('order') ? $request->input('order') : 'DESC';
            // dd($order);
            $sort = $request->filled('sort') ? $request->input('sort') : 'products.id';
            switch ($sort) {
                case 'p.id':
                    $sort = 'products.id';
                    break;
                case 'p.created_at':
                    $sort = 'products.created_at';
                    break;
                case 'p.total_sales':
                    $sort = 'products.total_sales';
                    break;
                case 'p.average_rating':
                    $sort = 'products.average_rating';
                    break;
                case 'pv.price':
                    $sort = 'product_variants.price';
                    break;
            }
            $language_code = $request->attributes->get('language_code');
            $is_detailed_data = $request->input('is_detailed_data') ? $request->input('is_detailed_data') : 0;
            $seller_id = $request->filled('seller_id') ? $request->input('seller_id') : null;
            $filters['search'] = $request->filled('search') ? trim($request->input('search')) : '';
            $filters['slug'] = $request->input('slug', '');
            $filters['tags'] = $request->input('tags', '');
            $filters['rating'] = $request->input('rating', '');
            $attributeValueIdsInput = $request->filled('attribute_value_ids')
                ? $request->input('attribute_value_ids')
                : ($request->filled('attribute_values_ids') ? $request->input('attribute_values_ids') : null);
            $filters['attribute_value_ids'] = !is_null($attributeValueIdsInput)
                ? explode(',', $attributeValueIdsInput)
                : null;
            $filters['is_similar_products'] = $request->filled('is_similar_products') ? $request->input('is_similar_products') : null;
            $filters['most_popular_products'] = $request->filled('most_popular_products') ? $request->input('most_popular_products') : '';
            $filters['discount'] = $request->filled('discount') ? $request->input('discount', 0) : 0;
            //dd($filters['discount']);
            $filters['product_type'] = $request->input('top_rated_product', 0) == 1 ? 'top_rated_product_including_all_products' : $request->input('product_type');
            $filters['minimum_price'] = $request->filled('minimum_price') ? $request->input('minimum_price') : '';
            $filters['maximum_price'] = $request->filled('maximum_price') ? $request->input('maximum_price') : '';
            // $filters['show_only_active_products'] = 1;
            $zipcode = $request->filled('zipcode') ? $request->input('zipcode') : 0;
            $type = $request->has('type') ? $request->input('type') : '';
            //find product according to zipcode
            if ($request->filled('zipcode')) {
                $is_pincode = Zipcode::where('zipcode', $zipcode)->exists();
                if ($is_pincode) {
                    $zipcode_id = Zipcode::where('zipcode', $zipcode)->firstOrFail()->id;

                    $zipcode = $zipcode_id;
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Products Not Found !',
                        'language_message_key' => 'products_not_found'
                    ], 200);
                }
            }
            $category_id = $request->input('category_id', null);
            $brand_id = $request->input('brand_id', null);
            $product_id = $request->input('id', null);
            $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : null;
            $product_ids = $request->input('product_ids', null);
            $product_variant_ids = $request->filled('product_variant_ids') ? $request->input('product_variant_ids') : null;

            if (!is_null($product_ids)) {
                $product_id = explode(",", $product_ids);
            }
            if (!is_null($category_id)) {
                $category_id = explode(",", $category_id);
            }
            if (!is_null($brand_id)) {
                $brand_id = explode(",", $brand_id);
            }
            if (!is_null($product_variant_ids)) {
                $filters['product_variant_ids'] = explode(",", $product_variant_ids);
            }

            //fetch product using filters
            $products = app(ProductService::class)->fetchProduct($user_id, (isset($filters)) ? $filters : null, $product_id, $category_id, $limit, $offset, $sort, $order, null, $zipcode, $seller_id, $brand_id, $store_id, $is_detailed_data, $type, 0, $language_code);
            //    dd($products);
            foreach ($products['product'] as $product) {
                if (!empty($product->tags)) {
                    $tags = array_values(array_unique(array_merge($tags, $product->tags)));
                }
            }
            if (!empty($products['product'])) {

                $filtered_brand_ids = array_filter($products['brand_ids'], function ($value) {
                    return !empty($value);
                });
                $brand_ids = implode(',', $filtered_brand_ids);
                $response = [
                    'error' => false,
                    'message' => 'Products retrieved successfully!',
                    'language_message_key' => 'products_retrived_successfully',
                    'min_price' => isset($products['min_price']) && !empty($products['min_price']) ? strval($products['min_price']) : '0',
                    'max_price' => isset($products['max_price']) && !empty($products['max_price']) ? strval($products['max_price']) : '0',
                    'category_ids' => isset($products['category_ids']) && !empty($products['category_ids']) ? implode(',', $products['category_ids']) : '',
                    'brand_ids' => isset($products['brand_ids']) && !empty($products['brand_ids']) ? $brand_ids : '',
                    'search' => $filters['search'],
                    'filters' => isset($products['filters']) && !empty($products['filters']) ? $products['filters'] : [],
                    'tags' => !empty($tags) ? $tags : [],
                    'total' => isset($products['total']) ? strval($products['total']) : '',
                    'offset' => $offset,
                    'data' => $products['product'],
                ];
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Products Not Found !',
                    'language_message_key' => 'products_not_found',
                    'data' => [],
                ], 200);
            }

            return response()->json($response);
        }
    }



    public function get_combo_products(Request $request)
    {
        $rules = [
            'store_id' => 'required_without:store_slug|exists:stores,id',
            'store_slug' => 'sometimes|exists:stores,slug',
            'id' => 'sometimes|numeric|exists:combo_products,id',
            'product_ids' => 'sometimes|string',
            'search' => 'sometimes|string',
            'attribute_value_ids' => 'sometimes|string',
            'sort' => 'sometimes|string',
            'limit' => 'sometimes|numeric',
            'offset' => 'sometimes|numeric',
            'order' => 'sometimes|string|alpha',
            'top_rated_product' => 'sometimes|numeric',
            'discount' => 'sometimes|numeric',
            'zipcode' => 'sometimes|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Resolve store_id from store_slug if provided
        if ($request->filled('store_slug') && !$request->filled('store_id')) {
            $store = fetchDetails(Store::class, ['slug' => $request->input('store_slug')], 'id');
            if ($store->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Store not found',
                    'language_message_key' => 'store_not_found',
                    'data' => []
                ]);
            }
            $store_id = (int) $store[0]->id;
        } else {
            $store_id = (int) $request->input('store_id', 0);
        }

        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'combo_products.id');
        $product_id = $request->input('id', '');
        $product_ids = $request->input('product_ids', null);
        $type = $request->input('type', '');
        if (!is_null($product_ids)) {
            $product_id = explode(",", $product_ids);
        }

        $language_code = $request->attributes->get('language_code');
        $seller_id = (int) $request->input('seller_id', 0);
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : '';
        $category_id = $request->input('category_id', '');
        $brand_id = $request->input('brand_id', '');

        $filters = [
            'search' => $request->input('search', null),
            'tags' => $request->input('tags', []),
            'flag' => $request->input('flag', ''),
            'attribute_value_ids' => $request->filled('attribute_value_ids')
                ? explode(',', $request->input('attribute_value_ids'))
                : [],
            'is_similar_products' => $request->input('is_similar_products', null),
            'product_type' => $request->input('top_rated_product') == 1
                ? 'top_rated_product_including_all_products'
                : $request->input('product_type', ''),
            'show_only_active_products' => $request->input('show_only_active_products', true),
            'show_only_stock_product' => $request->input('show_only_stock_product', false),
            'minimum_price' => $request->input('minimum_price', ''),
            'maximum_price' => $request->input('maximum_price', ''),
            'discount' => $request->input('discount', 0),
        ];

        // Handle zipcode
        $zipcode = $request->input('zipcode', 0);
        if ($zipcode) {
            $zipcode_record = Zipcode::where('zipcode', $zipcode)->first();
            if ($zipcode_record) {
                $zipcode = $zipcode_record->id;
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Products Not Found !',
                    'language_message_key' => 'products_not_found',
                    'data' => [],
                ], 200);
            }
        }

        // Fetch products
        $products = app(ComboProductService::class)->fetchComboProduct(
            $user_id,
            $filters,
            $product_id,
            $limit,
            $offset,
            $sort,
            $order,
            '',
            $zipcode,
            $seller_id,
            $store_id,
            $category_id,
            $brand_id,
            $type,
            '',
            $language_code
        );

        // Prepare response
        $response = [
            'error' => $products['combo_product']->isEmpty(),
            'message' => $products['combo_product']->isEmpty()
                ? 'No products found'
                : 'Products retrieved successfully!',
            'language_message_key' => $products['combo_product']->isEmpty()
                ? 'no_products_found'
                : 'products_retrieved_successfully',
            'total' => isset($products['total']) ? (int)$products['total'] : 0,
            'min_price' => $products['min_price'] ?? '0',
            'max_price' => $products['max_price'] ?? '0',
            'category_ids' => $products['category_ids'] ?? [],
            'brand_ids' => $products['brand_ids'] ?? [],
            'data' => $products['combo_product'],
        ];

        return response()->json($response);
    }

    public function get_settings(AddressController $addressController, Request $request)
    {
        /*
            type : payment_method // { default : all  } optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
        */
        $rules = [
            'type' => 'sometimes|in:payment_method,store_setting',
            'store_id' => 'sometimes|numeric|exists:stores,id',
            'limit' => 'sometimes|numeric',
            'offset' => 'sometimes|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {


            $type = $request->input('type', 'all');
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : '';
            $store_id = $request->input('store_id', '');
            $tags = $general_settings = array();

            $language_code = $request->attributes->get('language_code');


            if ($type == 'all' || $type == 'payment_method') {

                $filter['tags'] = $request->input('tags', '');

                $products = app(ProductService::class)->fetchProduct(null, $filter, null, null, $limit, $offset, 'products.id', 'DESC', null, '', '', '', $store_id, '', '', '', $language_code);

                for ($i = 0; $i < count($products); $i++) {
                    if (!empty($products['product'][$i]->tags)) {
                        $tags = array_merge($tags, $products['product'][$i]->tags);
                    }
                }
                $settings = [
                    'logo' => 0,
                    'privacy_policy' => 1,
                    'terms_and_conditions' => 1,
                    'fcm_server_key' => 1,
                    'contact_us' => 1,
                    'payment_method' => 1,
                    'about_us' => 1,
                    'currency' => 0,
                    'user_data' => 0,
                    'system_settings' => 1,
                    'shipping_policy' => 1,
                    'return_policy' => 1,
                    'shipping_method' => 1,
                    'pusher_settings' => 1,
                    'admin_preference' => 1,

                ];
                if ($type == 'payment_method') {

                    if (!$request->bearerToken()) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Please provide a valid token',
                            'language_message_key' => 'please_provide_valid_token',
                            'code' => 401,
                        ], 401);
                    }

                    $settings_res['payment_method'] = app(SettingService::class)->getSettings($type, $settings[$type]);
                    $settings_res['payment_method'] = json_decode($settings_res['payment_method'], true);
                    if (isset($user_id) && !empty($user_id)) {
                        $cart_total_response = app(CartService::class)->getCartTotal($user_id, false, 0, '', $store_id);

                        $cod_allowed = (!empty($cart_total_response) && isset($cart_total_response[0]->is_cod_allowed)) ? $cart_total_response[0]->is_cod_allowed : 1;
                        $settings_res['is_cod_allowed'] = $cod_allowed;
                    } else {
                        $settings_res['is_cod_allowed'] = 1;
                    }

                    $general_settings = $settings_res;
                } else {
                    foreach ($settings as $type => $isjson) {
                        if ($type == 'payment_method') {
                            continue;
                        }
                        $general_settings[$type] = [];
                        $settings_res = app(SettingService::class)->getSettings($type, $isjson);
                        $settings_res = json_decode($settings_res, true);

                        if ($type == 'logo') {
                            $logo_setting = app(SettingService::class)->getSettings('system_settings', true);
                            $logo_setting = json_decode($logo_setting, true);
                            $settings_res = app(MediaService::class)->getMediaImageUrl($logo_setting['logo']);
                        }
                        if ($type == 'user_data' && isset($user_id) && !empty($user_id)) {
                            $cart_total_response = app(CartService::class)->getCartTotal($user_id, false, 0, '', $store_id);
                            $res = $addressController->getAddress($user_id, null, false, true);

                            if (!empty($res)) {
                                $zipcode_details = fetchDetails(Zipcode::class, ['zipcode' => $res[0]->pincode], 'id');
                                if (!$zipcode_details->isEmpty()) {
                                    $zipcode_id = $zipcode_details[0]->id;
                                    $zipcode_data = fetchDetails(Zipcode::class, ['id' => $zipcode_id], 'zipcode');
                                    if (!$zipcode_data->isEmpty()) {
                                        $zipcode = $zipcode_data[0]->zipcode;
                                    }
                                }
                            }
                            $settings_res = fetchUsers($user_id);
                            $settings_res = [
                                'cities' => $settings_res->cities ?? '',
                                'street' => $settings_res->street ?? '',
                                'area' => $settings_res->area ?? '',
                                'cart_total_items' => 0, // Initialize to 0, you can update it later
                                'pincode' => isset($zipcode) ? $zipcode : '',
                            ];
                        } elseif ($type == 'user_data' && !isset($user_id)) {
                            $settings_res = '';
                        }
                        //Strip tags in case of terms_and_conditions and privacy_policy

                        if ($isjson && isset($settings_res[$type])) {
                            array_push($general_settings[$type], $settings_res[$type]);
                        } else {
                            array_push($general_settings[$type], $settings_res);
                        }
                    }
                    $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
                    $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';
                    $general_settings['currency'] = $currency;
                }

                // Only process these if type is not 'payment_method' (when type is 'payment_method', general_settings only has payment data)
                if ($type != 'payment_method') {
                    // Only unset ai_setting if user is not authenticated, otherwise include it in response
                    if (!auth('sanctum')->check() || empty($user_id)) {
                        if (isset($general_settings['system_settings'][0]['ai_setting'])) {
                            unset($general_settings['system_settings'][0]['ai_setting']);
                        }
                    }
                    if (isset($general_settings['shipping_method'][0])) {
                        unset($general_settings['shipping_method'][0]['password']);
                        unset($general_settings['shipping_method'][0]['email']);
                        unset($general_settings['shipping_method'][0]['webhook_token']);
                        $general_settings['shipping_method'][0]['minimum_free_delivery_order_amount'] = isset($general_settings['shipping_method'][0]['minimum_free_delivery_order_amount']) && $general_settings['shipping_method'][0]['minimum_free_delivery_order_amount'] !== null ? $general_settings['shipping_method'][0]['minimum_free_delivery_order_amount'] : '';
                    }
                    if (isset($general_settings['terms_and_conditions'][0])) {
                        $general_settings['terms_and_conditions'][0] = isset($general_settings['terms_and_conditions'][0]) && $general_settings['terms_and_conditions'][0] !== null ? $general_settings['terms_and_conditions'][0] : '';
                    }
                    // Loop through the array and replace null values with an empty string
                    if (isset($general_settings['system_settings']) && !empty($general_settings['system_settings'])) {
                        $base_url = url('/'); // or config('app.url')
                        foreach ($general_settings['system_settings'][0] as $key => $value) {
                            if ($value === null) {
                                $general_settings['system_settings'][0][$key] = "";
                            } elseif (in_array($key, ['logo', 'favicon']) && !empty($value)) {
                                $general_settings['system_settings'][0][$key] = app(MediaService::class)->getImageUrl($value);
                            }
                        }
                    }

                    if (isset($general_settings['system_settings'][0]['on_boarding_image']) && !empty($general_settings['system_settings'][0]['on_boarding_image'])) {
                        $onboarding_images = $general_settings['system_settings'][0]['on_boarding_image'];
                        if (isset($onboarding_images) && !empty($onboarding_images)) {
                            foreach ($onboarding_images as &$image) {
                                $image = app(MediaService::class)->getImageUrl($image, "", "", 'image', 'MEDIA_PATH');
                            }
                        }
                    } else {
                        $onboarding_images = [];
                    }
                    if (isset($general_settings['system_settings'][0])) {
                        $general_settings['system_settings'][0]['on_boarding_image'] = $onboarding_images;
                    }

                    // Add asset paths to onboarding videos
                    $onboarding_videos = [];
                    if (isset($general_settings['system_settings'][0]['on_boarding_video']) && !empty($general_settings['system_settings'][0]['on_boarding_video'])) {
                        $onboarding_videos = $general_settings['system_settings'][0]['on_boarding_video'];

                        if (isset($onboarding_videos) && !empty($onboarding_videos)) {
                            foreach ($onboarding_videos as &$video) {
                                $video = app(MediaService::class)->getImageUrl($video, "", "", 'image', 'MEDIA_PATH');
                            }
                        }
                    }
                    if (isset($general_settings['system_settings'][0])) {
                        $general_settings['system_settings'][0]['on_boarding_video'] = $onboarding_videos;

                        // Add deep link scheme for mobile app (from database settings)
                        // Store only the scheme name (e.g., "eshop") in DB
                        // Mobile app will append "://" on their side
                        $deepLinkScheme = $general_settings['system_settings'][0]['deep_link_scheme'] ?? 'eshop';
                        // Remove "://" if it exists (for backward compatibility)
                        $deepLinkScheme = str_replace('://', '', $deepLinkScheme);
                        // Return only the scheme name
                        $general_settings['system_settings'][0]['deep_link_scheme'] = $deepLinkScheme;
                    }

                    if (isset($general_settings['user_data'][0])) {
                        $general_settings['user_data'] = (isset($general_settings['user_data'][0]) && !empty($general_settings['user_data'][0])) ? $general_settings['user_data'][0] : [];
                    }
                }

                if (isset($general_settings['payment_method']) && !empty($general_settings['payment_method'])) {
                    $general_settings['payment_method'] = array_map(function ($value) {
                        return $value === null ? "" : $value;
                    }, $general_settings['payment_method']);
                }

                // Fetch languages to include in settings response (for all types)
                $languages = Language::select('id', 'language', 'code', 'native_language', 'is_rtl')->get();

                // Auto-detect file type based on controller, allow override via parameter
                $file_type = $request->input('file_type', $this->detectFileType());

                // Convert is_rtl to integer and add file metadata
                $languages = $languages->map(function ($language) use ($file_type) {
                    $language->is_rtl = intval($language->is_rtl);

                    // Get file metadata using helper method
                    $metadata = $this->getLanguageFileMetadata($language->code, $file_type);

                    // Convert to array to avoid Laravel's timestamp casting
                    $languageArray = $language->toArray();

                    // Add file metadata (without labels)
                    $languageArray['updated_at'] = $metadata['updated_at'];
                    $languageArray['missing_labels_count'] = $metadata['missing_labels_count'];
                    $languageArray['total_labels'] = $metadata['total_labels'];
                    $languageArray['file_exists'] = $metadata['file_exists'];

                    return (object) $languageArray;
                });

                $general_settings['languages'] = $languages;
                if ($type != 'payment_method') {
                    $general_settings['tags'] = $tags;
                }

                $response = [
                    'error' => false,
                    'message' => 'Settings retrieved successfully',
                    'language_message_key' => 'settings_retrieved_successfully',
                    'data' => $general_settings,
                ];
            } else {

                $response = [
                    'error' => true,
                    'message' => 'Settings Not Found',
                    'language_message_key' => 'settings_not_found',
                    'data' => [],
                ];
            }
            return response()->json($response);
        }
    }

    public function get_slider_images(CategoryController $categoryController, Request $request)
    {
        $offset = request()->query('offset', 0);
        $limit = request()->query('limit', 1);
        $rules = [
            'store_id' => 'required|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';
            $res = fetchDetails(Slider::class, ['store_id' => $store_id], '*');
            $language_code = $request->attributes->get('language_code');
            for ($i = 0; $i < count($res); $i++) {
                if ($res[$i]->link == null || empty($res[$i]->link)) {
                    $res[$i]->link = "";
                }

                // Use default image for app, but fallback to web_image if image is not available
                $imageToUse = '';
                if (!empty($res[$i]->image)) {
                    $imageToUse = $res[$i]->image;
                } elseif (!empty($res[$i]->web_image)) {
                    $imageToUse = $res[$i]->web_image;
                }

                if (!empty($imageToUse)) {
                    $res[$i]->image = app(MediaService::class)->getMediaImageUrl($imageToUse);
                } else {
                    $res[$i]->image = '';
                }

                if (strtolower($res[$i]->type) == 'categories') {
                    $id = (!empty($res[$i]->type_id) && isset($res[$i]->type_id)) ? $res[$i]->type_id : '';
                    $cat_res = $categoryController->getCategories($id);
                    $res[$i]->data = $cat_res->original['categories'];
                } elseif (strtolower($res[$i]->type) == 'products') {
                    $id = (!empty($res[$i]->type_id) && isset($res[$i]->type_id)) ? $res[$i]->type_id : '';
                    $pro_res = app(ProductService::class)->fetchProduct(NULL, NULL, $id, '', $limit, $offset, '', '', '', '', '', '', $store_id, '', '', '', $language_code);
                    $res[$i]->data = $pro_res['product'];
                } elseif (strtolower($res[$i]->type) == 'combo_products') {
                    $id = (!empty($res[$i]->type_id) && isset($res[$i]->type_id)) ? $res[$i]->type_id : '';
                    $combo_pro_res = app(ComboProductService::class)->fetchComboProduct('', '', $id, '20', '0', '', '', '', '', '', $store_id, '', '', '', '', $language_code);
                    $res[$i]->data = $combo_pro_res['combo_product'];
                } else {
                    $res[$i]->data = [];
                }
            }

            if ($res->isNotEmpty()) {
                $response = [
                    'error' => false,
                    'message' => 'Sliders Retrieved Successfully',
                    'language_message_key' => 'sliders_retrieved_successfully',
                    'data' => $res,
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => 'No Sliders Found',
                    'language_message_key' => 'no_sliders_found',
                    'data' => $res,
                ];
            }

            return response()->json($response);


            return response()->json($response);
        }
    }

    public function update_fcm(Request $request)
    {
        // Validation rules

        $rules = [
            'fcm_id' => 'required',
            'is_delete' => 'sometimes|boolean',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Check if the user is authenticated
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : null;

        // Get fcm_id from request
        $fcm_id = $request->input('fcm_id') ? $request->input('fcm_id') : '';
        $is_delete = $request->input('is_delete'); // New delete parameter

        // If the delete parameter is set to 1, handle deletion
        if ($is_delete == 1) {
            if (isset($user_id) && !empty($user_id) && !empty($fcm_id)) {
                // Delete the entry from user_fcm table
                $deleted = UserFcm::where('user_id', $user_id)
                    ->where('fcm_id', $fcm_id)
                    ->delete();

                if ($deleted) {
                    $response = [
                        'error' => false,
                        'message' => 'FCM ID deleted successfully',
                        'language_message_key' => 'deleted_successfully',
                        'data' => [],
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'No entry found to delete!',
                        'language_message_key' => 'no_entry_found',
                        'data' => [],
                    ];
                }
            } else {
                // Handle case where user_id or fcm_id is not set
                $response = [
                    'error' => true,
                    'message' => 'User ID and FCM ID are required for deletion!',
                    'language_message_key' => 'user_id_fcm_id_required',
                    'data' => [],
                ];
            }
        } else {
            // Handle insertion logic
            if (!empty($fcm_id)) {
                if (isset($user_id) && !empty($user_id)) {
                    // Prepare the data for insertion
                    $fcm_data = [
                        'fcm_id' => $fcm_id,
                        'user_id' => $user_id,
                    ];

                    // Check if the FCM ID already exists for the user
                    $existing_fcm = UserFcm::where('user_id', $user_id)
                        ->where('fcm_id', $fcm_id)
                        ->first();

                    if (!$existing_fcm) {
                        // If it doesn't exist, create a new entry
                        $user_res = UserFcm::insert($fcm_data);

                        // Prepare the response
                        if ($user_res) {
                            $response = [
                                'error' => false,
                                'message' => 'FCM ID stored successfully',
                                'language_message_key' => 'stored_successfully',
                                'data' => [],
                            ];
                        } else {
                            $response = [
                                'error' => true,
                                'message' => 'Insertion Failed!',
                                'language_message_key' => 'insertion_failed',
                                'data' => [],
                            ];
                        }
                    } else {
                        // If the FCM ID already exists, prepare a response indicating this
                        $response = [
                            'error' => true,
                            'message' => 'FCM ID already exists for this user.',
                            'language_message_key' => 'fcm_id_exists',
                            'data' => [],
                        ];
                    }
                } else {
                    // Handle case where user_id is not set
                    $response = [
                        'error' => true,
                        'message' => 'User ID is required!',
                        'language_message_key' => 'user_id_required',
                        'data' => [],
                    ];
                }
            }
        }

        return response()->json($response);
    }


    public function reset_password_old(Request $request)
    {
        /* Parameters to be passed
            mobile_no:7894561235
            new: pass@123
        */
        $rules = [
            'mobile_no' => 'required|numeric|digits_between:1,16',
        ];

        $messages = [
            'mobile_no.required' => 'Mobile Number is required.',
            'mobile_no.numeric' => 'Mobile Number must be numeric.',
            'mobile_no.digits_between' => 'Mobile Number must be between 1 and 16 digits.',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages, null, true)) {
            return $response;
        } else {
            $mobile_no = $request->input('mobile_no');
            $new_pass = $request->input('new');
            $identityColumn = config('auth.defaults.passwords') === 'users.email' ? 'email' : 'mobile';

            $user = User::where($identityColumn, $mobile_no)->first();

            if (!$user) {
                $response = [
                    'error' => true,
                    'message' => 'User does not exist!',
                    'language_message_key' => 'user_does_not_exist',
                    'data' => [],
                ];
                return response()->json($response);
            }

            $status = Password::broker()->sendResetLink(
                ['email' => $user->email]
            );

            if ($status === Password::RESET_LINK_SENT) {
                $response = [
                    'error' => false,
                    'message' => 'Password reset link sent successfully!',
                    'language_message_key' => 'password_reset_link_sent_successfully!',
                    'data' => [],
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Unable to send password reset link.',
                    'language_message_key' => 'unable_to_send_password_reset_link',
                    'data' => [],
                ];
            }
            return response()->json($response);
        }
    }
    public function reset_password(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];

        $messages = [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages, null, true)) {
            return $response;
        }

        $email = $request->input('email');

        // Find user based on email
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User does not exist!',
                'language_message_key' => 'user_does_not_exist',
                'data' => [],
            ]);
        }

        // Send reset link to user's email
        $status = Password::broker()->sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'error' => false,
                'message' => 'Password reset link sent successfully!',
                'language_message_key' => 'password_reset_link_sent_successfully',
                'data' => [],
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Unable to send password reset link.',
            'language_message_key' => 'unable_to_send_password_reset_link',
            'data' => [],
        ]);
    }
    public function get_login_identity()
    {
        $response = [
            'error' => false,
            'message' => 'Data Retrieved Successfully',
            'language_message_key' => 'data_retrieved_successfully',
            'data' => array('identity' => (config('auth.defaults.passwords') === 'users.email' ? 'email' : 'mobile')),
        ];
        return response()->json($response);
    }
    // public function verify_user(Request $request)
    // {

    //     $rules = [
    //         'mobile' => 'numeric',
    //         'email' => 'sometimes|nullable|email',
    //         'country_code' => 'sometimes|nullable|string',
    //     ];
    //     if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
    //         return $response;
    //     }



    //     $mobile = $request->input('mobile');
    //     $email = $request->input('email');

    //     // Check if mobile or email exists in users table
    //     $user = null;
    //     if (isset($mobile) && isExist(['mobile' => $mobile], User::class)) {
    //         $user = User::where('mobile', $mobile)->first();
    //     } elseif (isset($email) && isExist(['email' => $email], User::class)) {
    //         $user = User::where('email', $email)->first();
    //     }

    //     $authentication_settings = app(SettingService::class)->getSettings('system_settings', true);
    //     $authentication_settings = json_decode($authentication_settings, true);

    //     if ($authentication_settings['authentication_method'] == "firebase") {
    //         if ($user) {
    //             Auth::login($user);
    //             $token = $user->createToken('authToken')->plainTextToken;
    //             $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

    //             $fcm_ids_array = array_map(function ($item) {
    //                 return $item->fcm_id;
    //             }, $fcm_ids->all());
    //             $user_data = $this->getUserDataArray($user);
    //             $user_data['fcm_id'] = $fcm_ids_array;

    //             // Get store_status from seller_store table
    //             $seller_store = SellerStore::where('user_id', $user->id)->first();
    //             $store_Status = $seller_store ? $seller_store->status : null;

    //             return response()->json([
    //                 'error' => false,
    //                 'message' => 'User Logged in successfully',
    //                 'language_message_key' => 'user_logged_in_successfully',
    //                 'token' => $token,
    //                 'user' => $user_data,
    //                 'store_Status' => $store_Status,
    //             ]);
    //         }
    //     } else {
    //         if ($user) {

    //             $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

    //             $fcm_ids_array = array_map(function ($item) {
    //                 return $item->fcm_id;
    //             }, $fcm_ids->all());
    //             Auth::login($user);
    //             $token = $user->createToken('authToken')->plainTextToken;

    //             $user_data = $this->getUserDataArray($user);
    //             $user_data['fcm_id'] = $fcm_ids_array;

    //             // Get store_status from seller_store table
    //             $seller_store = SellerStore::where('user_id', $user->id)->first();
    //             $store_Status = $seller_store ? $seller_store->status : null;

    //             return response()->json([
    //                 'error' => false,
    //                 'message' => 'User Logged in successfully',
    //                 'language_message_key' => 'user_logged_in_successfully',
    //                 'token' => $token,
    //                 'user' => $user_data,
    //                 'store_Status' => $store_Status,
    //             ]);
    //         } else {
    //             $mobile_data = array(
    //                 'mobile' => $mobile
    //             );

    //             if (request()->has('mobile') && !Otps::where('mobile', request('mobile'))->exists()) {
    //                 Otps::insert($mobile_data);
    //             }

    //             $otps = Otps::where('mobile', $mobile)->get()->toArray();

    //             $otp = random_int(100000, 999999);
    //             $data = set_user_otp($mobile, $otp);

    //             // Assume send_otp is a function that sends the OTP to the user's mobile
    //             set_user_otp($mobile, $otp);

    //             return response()->json([
    //                 'error' => false,
    //                 'message' => 'OTP sent successfully',
    //                 'language_message_key' => 'otp_sent_successfully',
    //             ]);
    //         }
    //     }

    //     return response()->json([
    //         'error' => true,
    //         'message' => 'User Not Registered',
    //         'language_message_key' => 'user_not_registered',
    //         'code' => 102,
    //         'data' => [],
    //     ]);
    // }

    public function verify_user(Request $request)
    {

        $rules = [
            'mobile' => 'nullable|digits_between:8,15',
            'email'  => 'sometimes|nullable|email',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $mobile = $request->input('mobile');
        $email  = $request->input('email');

        // 🔎 Find user either by mobile or email
        $user = null;
        if ($mobile && isExist(['mobile' => $mobile], User::class)) {
            $user = User::where('mobile', $mobile)->first();
        } elseif ($email && isExist(['email' => $email], User::class)) {
            $user = User::where('email', $email)->first();
        }

        // 🔧 Load system authentication settings
        $authentication_settings = app(SettingService::class)->getSettings('system_settings', true);
        $authentication_settings = json_decode($authentication_settings, true);

        if ($authentication_settings['authentication_method'] === "firebase") {
            if ($user) {
                // Login user and return token
                Auth::login($user);
                $token = $user->createToken('authToken')->plainTextToken;

                $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');
                $fcm_ids_array = array_column($fcm_ids->toArray(), 'fcm_id');

                $user_data = $this->getUserDataArray($user);
                $user_data['fcm_id'] = $fcm_ids_array;

                return response()->json([
                    'error'   => false,
                    'message' => 'User Logged in successfully',
                    'language_message_key' => 'user_logged_in_successfully',
                    'token'   => $token,
                    'user'    => $user_data,
                ]);
            }
        } else {
            if ($user) {
                // Non-firebase login
                $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');
                $fcm_ids_array = array_column($fcm_ids->toArray(), 'fcm_id');

                Auth::login($user);
                $token = $user->createToken('authToken')->plainTextToken;

                $user_data = $this->getUserDataArray($user);
                $user_data['fcm_id'] = $fcm_ids_array;

                return response()->json([
                    'error'   => false,
                    'message' => 'User Logged in successfully',
                    'language_message_key' => 'user_logged_in_successfully',
                    'token'   => $token,
                    'user'    => $user_data,
                ]);
            } else {
                // User not found → send OTP instead
                $otp = rand(100000, 999999);
                $code = ltrim($request->input('code', '20'), '+'); // default 91
                $fullMobile = $code . $mobile;

                \Log::info("send_otp request", [
                    'mobile'          => $mobile,
                    'code'            => $code,
                    'formatted_mobile' => $fullMobile,
                    'otp'             => $otp,
                ]);

                // SMS text
                $message = "رز التحقق من موقع لبدي : {$otp}";

                // Send SMS
                $res = send_sms($mobile, $message, "+" . $code);

                $http_code     = $res['http_code'] ?? 0;
                $error_message = $res['error_message'] ?? $res['message'] ?? 'Unknown error';

                if ($http_code == 200) {
                    // Store OTP
                    Otps::updateOrCreate(
                        ['mobile' => $mobile],
                        [
                            'otp'        => $otp,
                            'varified'   => 0,
                            'created_at' => now(),
                        ]
                    );

                    return response()->json([
                        "error"   => false,
                        "message" => "OTP sent successfully.",
                        "language_message_key" => "otp_sent_successfully",
                        "data"    => [
                            'mobile'   => $mobile,
                            'varified' => 0,
                            // ⚠️ remove otp from response in production
                            'otp'      => $otp
                        ]
                    ]);
                }

                \Log::error("SMS sending failed", [
                    'mobile'   => $mobile,
                    'response' => $res
                ]);

                return response()->json([
                    "error"   => true,
                    "message" => "SMS sending failed: " . $error_message
                ]);
            }
        }

        // Fallback
        return response()->json([
            'error'   => true,
            'message' => 'User Not Registered',
            'language_message_key' => 'user_not_registered',
            'code'    => 102,
            'data'    => [],
        ]);
    }

    public function verify_otp(Request $request)
    {
        // Validate the input

        $rules = [
            'mobile' => 'required|numeric',
            'otp' => 'required|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }


        $mobile = $request->input('mobile');
        $otp = $request->input('otp');
        $auth_settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);

        if ($auth_settings['authentication_method'] == "sms") {
            $otps = Otps::where('mobile', $mobile)->first();

            if (!$otps) {
                return response()->json([
                    'error' => true,
                    'message' => 'OTP not found for this mobile number',
                    'language_message_key' => 'data_not_found',
                ]);
            }
            $time_expire = checkOTPExpiration($otps->created_at);

            if ($time_expire['error'] == 1) {
                return response()->json([
                    'error' => true,
                    'message' => $time_expire['message'],
                ]);
            }

            if ($otps->otp != $otp) {
                return response()->json([
                    'error' => true,
                    'message' => 'OTP not valid, check again',
                    'language_message_key' => 'invalid_otp_supplied',
                ]);
            } else {
                Otps::where('mobile', $mobile)->update(['varified' => 1]);
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'OTP Verified Successfully',
            'language_message_key' => 'otp_verified_successfully',
            'data' => [],
        ]);
    }

    public function resend_otp(Request $request)
    {
        // Validate the input

        $rules = [
            'mobile' => 'required|numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }



        $mobile = $request->input('mobile');
        $auth_settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);

        if ($auth_settings['authentication_method'] == "sms") {
            $otps = Otps::where('mobile', $mobile)->first();

            if (!$otps) {
                return response()->json([
                    'error' => true,
                    'message' => 'No OTP found for this mobile number',
                    'language_message_key' => 'data_not_found',
                    'data' => [],
                ]);
            }

            $otp = random_int(100000, 999999);
            $data = set_user_otp($mobile, $otp);

            // Optionally, you can send the OTP here using a hypothetical function send_otp
            set_user_otp($mobile, $otp);

            return response()->json([
                'error' => false,
                'message' => 'Ready to send OTP request via SMS!',
                'language_message_key' => 'ready_to_send_otp',
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Invalid authentication method',
            'language_message_key' => 'invalid_authentication_method',
            'data' => [],
        ]);
    }

    private function getUserDataArray($user)
    {
        $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

        $fcm_ids_array = array_map(function ($item) {
            return $item->fcm_id;
        }, $fcm_ids->all());
        return [
            'id' => $user->id ?? '',
            'ip_address' => $user->ip_address ?? '',
            'username' => $user->username ?? '',
            'email' => $user->email ?? '',
            'mobile' => $user->mobile ?? '',
            'country_code' => $user->country_code ?? '',
            'image' => app(MediaService::class)->getMediaImageUrl($user->image, 'USER_IMG_PATH'),
            'balance' => $user->balance ?? '0',
            'activation_selector' => $user->activation_selector ?? '',
            'activation_code' => $user->activation_code ?? '',
            'forgotten_password_selector' => $user->forgotten_password_selector ?? '',
            'forgotten_password_code' => $user->forgotten_password_code ?? '',
            'forgotten_password_time' => $user->forgotten_password_time ?? '',
            'remember_selector' => $user->remember_selector ?? '',
            'remember_code' => $user->remember_code ?? '',
            'created_on' => $user->created_on ?? '',
            'last_login' => $user->last_login ?? '',
            'active' => $user->active ?? '',
            'company' => $user->company ?? '',
            'address' => $user->address ?? '',
            'bonus' => $user->bonus ?? '',
            'cash_received' => $user->cash_received ?? '0.00',
            'dob' => $user->dob ?? '',
            'country_code' => $user->country_code ?? '',
            'city' => $user->city ?? '',
            'area' => $user->area ?? '',
            'street' => $user->street ?? '',
            'pincode' => $user->pincode ?? '',
            'apikey' => $user->apikey ?? '',
            'referral_code' => $user->referral_code ?? '',
            'friends_code' => $user->friends_code ?? '',
            'fcm_id' => array_values($fcm_ids_array) ?? '',
            'latitude' => $user->latitude ?? '',
            'longitude' => $user->longitude ?? '',
            'created_at' => $user->created_at ?? '',
            'type' => $user->type ?? '',
            'is_notification_on' => $user->is_notification_on ?? '',
        ];
    }


    public function register_user(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'mobile' => [
                'required',
                'numeric',
                Rule::unique('users', 'mobile')->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code);
                }),
            ],
            'country_code' => 'required|string|max:255',
            'fcm_id' => 'nullable|string|max:255',
            'referral_code' => 'nullable|string|unique:users,referral_code|max:255',
            'friends_code' => 'nullable|string|max:255',
            'password' => 'string|max:255',
        ];

        $messages = [
            'mobile.unique' => 'The mobile number is already registered for this country. Please log in.',
            'email.unique' => 'The email is already registered. Please log in.',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages, null, true)) {
            return $response;
        } else {
            if ($request->filled('friends_code')) {
                $friends_code = $request->input('friends_code');
                $friend = User::where('referral_code', $friends_code)->first();

                if (!$friend) {
                    $response = [
                        'error' => true,
                        'message' => 'Invalid friends code! Please pass the valid referral code of the inviter',
                        'language_message_key' => 'invalid_friends_code_pass_valid_referral_code',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
            }

            $settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);
            $wallet_balance = isset($settings['wallet_balance_amount']) && !empty($settings['wallet_balance_amount'])
                ? $settings['wallet_balance_amount']
                : '';

            $additional_data = [
                'username' => $request->name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'country_code' => $request->country_code,
                'referral_code' => $request->referral_code,
                'friends_code' => $request->friends_code,
                'type' => 'phone',
                'role_id' => 2,
            ];

            $lastInsertId = User::insertGetId($additional_data);

            if ($lastInsertId) {

                // add fcm id in user fcm table
                if ($request->filled('fcm_id')) {
                    $existing_fcm = UserFcm::where('user_id', $lastInsertId)
                        ->where('fcm_id', $request->fcm_id)
                        ->first();

                    if (!$existing_fcm) {
                        UserFcm::insert([
                            'fcm_id' => $request->fcm_id,
                            'user_id' => $lastInsertId,
                        ]);
                    }
                }

                // activate user and update wallet if enabled
                User::where('id', $lastInsertId)->update(['active' => 1]);

                if (isset($settings['wallet_balance_status']) && $settings['wallet_balance_status'] == 1) {
                    app(WalletService::class)->updateWalletBalance(
                        'credit',
                        $lastInsertId,
                        $wallet_balance,
                        'Welcome Wallet Amount Credited for User ID: ' . $lastInsertId
                    );
                }

                // fetch only the newly created user
                $data = User::select(
                    'users.id',
                    'users.username',
                    'users.email',
                    'users.mobile',
                    'users.country_code',
                    'c.name as city_name',
                    'users.is_notification_on'
                )
                    ->where('users.id', $lastInsertId)
                    ->leftJoin('cities as c', 'c.id', '=', 'users.city')
                    ->first();

                $tempRow = [
                    'id' => $data->id ?? '',
                    'username' => $data->username ?? '',
                    'email' => $data->email ?? '',
                    'mobile' => $data->mobile ?? '',
                    'country_code' => $data->country_code ?? '',
                    'city_name' => $data->city_name ?? '',
                    'area_name' => $data->area_name ?? '',
                    'is_notification_on' => intval($data->is_notification_on ?? 0),
                ];

                $response = [
                    'error' => false,
                    'message' => 'Registered Successfully',
                    'language_message_key' => 'registered_successfully',
                    'data' => [$tempRow],
                ];

                return response()->json($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Registration Failed',
                    'language_message_key' => 'registration_fail',
                    'data' => [],
                ];
                return response()->json($response);
            }
        }
    }

    public function update_user(Request $request)
    {
        /*
        username:hiten{optional}
        dob:12/5/1982{optional}
        mobile:7852347890 {optional}
        country_code:91 {optional}
        email:amangoswami@gmail.com {optional}
        address:Time Square {optional}
        city:23 {optional}
        pincode:56 {optional}
        latitude:45.453 {optional}
        longitude:45.453 {optional}
        image:[]
        referral_code:Userscode
        old:12345
        new:345234
        is_notification_on:1/0
    */

        if (auth()->check()) {
            $user_id = auth()->user()->id;
        }

        $rules = [
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user_id),
            ],
            'dob' => 'nullable|date',
            'city' => 'nullable|numeric',
            'address' => 'nullable|string',
            'pincode' => 'nullable|numeric',
            'username' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'referral_code' => 'nullable|string',
        ];

        // if mobile provided, country_code is required, and mobile+country_code combination must be unique
        if ($request->filled('mobile')) {
            $rules['mobile'] = [
                'numeric',
                Rule::unique('users', 'mobile')->ignore($user_id)->where(function ($query) use ($request) {
                    if ($request->filled('country_code')) {
                        $query->where('country_code', $request->country_code);
                    }
                }),
            ];
            $rules['country_code'] = 'required_with:mobile|numeric';
        }

        // override validation if password update requested
        if (!empty($request->input('old')) || !empty($request->input('new'))) {
            $rules = [
                'old' => 'required',
                'new' => 'required|min:6',
            ];
        }

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_details = fetchDetails(User::class, ['id' => $user_id], '*');

        // password update block
        if (!empty($request->input('old')) || !empty($request->input('new'))) {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'User is not authenticated',
                    'language_message_key' => 'user_not_authenticated'
                ], 401);
            }

            if (!Hash::check($request->input('old'), $user->password)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Old password is incorrect',
                    'language_message_key' => 'old_password_incorrect'
                ], 400);
            }

            $user->password = bcrypt($request->input('new'));
            $user->save();

            if (!$user_details->isEmpty()) {
                $file_path = str_replace('\\', '/', public_path(config('constants.USER_IMG_PATH') . $user_details[0]->image));
                if (empty($user_details[0]->image) || File::exists($file_path) == false) {
                    $user_details[0]->image = str_replace('\\', '/', public_path(config('constants.NO_USER_IMAGE')));
                } else {
                    $user_details[0]->image = $file_path;
                }

                $user_details[0]->image_sm = app(MediaService::class)->getImageUrl($user_details[0]->image, 'thumb', 'sm', 'image');
            }

            return response()->json([
                'error' => false,
                'message' => 'Password Update Successfully',
                'language_message_key' => 'password_update_successful',
                'data' => $user_details,
            ]);
        }

        $is_updated = false;

        // referral_code logic
        $referral_code = $request->input('referral_code');
        if (isset($referral_code) && !empty($referral_code)) {
            if (!$user_details->isEmpty() && empty($user_details[0]->referral_code)) {
                updateDetails(['referral_code' => $referral_code], ['id' => $user_id], User::class);
                $is_updated = true;
            }
        }

        // handle image upload
        $userImgPath = public_path(config('constants.USER_IMG_PATH'));
        if (!File::exists($userImgPath)) {
            File::makeDirectory($userImgPath, 0755, true);
        }

        $rules = ['image' => 'image|mimes:jpeg,gif,jpg,png'];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageNewName = $image->getClientOriginalName();
            $image_path = $userImgPath . '/' . $imageNewName;

            if (!$image->move($userImgPath, $imageNewName)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Error uploading image',
                    'language_message_key' => 'error_uploading_image',
                    'data' => [],
                ]);
            }
        }

        // collect update fields
        $set = [];
        foreach (['username', 'email', 'dob', 'mobile', 'address', 'city', 'area', 'pincode', 'latitude', 'longitude', 'is_notification_on', 'country_code'] as $field) {
            if ($request->filled($field)) {
                $set[$field] = $request->$field;
            }
        }

        if ($request->hasFile('image')) {
            $set['image'] = '/' . $imageNewName;
        }

        if (!empty($set)) {
            updateDetails($set, ['id' => $user_id], User::class);
            $user_details = fetchDetails(User::class, ['id' => $user_id], '*');

            foreach ($user_details as $row) {
                $row = (object) outputEscaping($row);
                $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $row->id ?? null], 'fcm_id');
                $fcm_ids_array = array_map(fn($item) => $item->fcm_id, $fcm_ids->all());

                $defaultImage = app(MediaService::class)->getImageUrl('no-user-img.jpeg', "", "", "image", 'NO_USER_IMAGE');
                $imageUrl = $row->image !== "" ? app(MediaService::class)->getImageUrl($row->image ?? null, 'thumb', 'sm', 'image', 'USER_IMG_PATH') : "";
                $image = $imageUrl ?: $defaultImage;

                $rows[] = [
                    'id' => intval($row->id ?? ''),
                    'username' => $row->username ?? '',
                    'email' => $row->email ?? '',
                    'mobile' => $row->mobile ?? '',
                    'country_code' => intval($row->country_code ?? ''),
                    'image' => $image,
                    'is_notification_on' => $row->is_notification_on ?? '',
                    'address' => $row->address ?? '',
                    'city' => $row->city ?? '',
                    'area' => $row->area ?? '',
                    'pincode' => $row->pincode ?? '',
                    'latitude' => $row->latitude ?? '',
                    'longitude' => $row->longitude ?? '',
                    'referral_code' => $row->referral_code ?? '',
                    'friends_code' => $row->friends_code ?? '',
                    'fcm_id' => array_values($fcm_ids_array) ?? '',
                    'created_at' => $row->created_at ?? '',
                ];
            }

            return response()->json([
                'error' => false,
                'message' => 'Profile Update Successfully',
                'language_message_key' => 'profile_updated_successfully',
                'data' => $rows,
            ]);
        } elseif ($is_updated == true) {
            $user_details = fetchDetails(User::class, ['id' => $user_id], '*');
            foreach ($user_details as $row) {
                $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $row->id], 'fcm_id');
                $fcm_ids_array = array_map(fn($item) => $item->fcm_id, $fcm_ids->all());
                $row = outputEscaping($row);

                $defaultImage = app(MediaService::class)->getImageUrl('no-user-img.jpeg', "", "", "image", 'NO_USER_IMAGE');
                $imageUrl = $row->image !== "" ? app(MediaService::class)->getImageUrl($row->image, 'thumb', 'sm', 'image', 'USER_IMG_PATH') : "";
                $image = $imageUrl ?: $defaultImage;

                $rows[] = [
                    'id' => intval($row->id ?? ''),
                    'username' => $row->username ?? '',
                    'email' => $row->email ?? '',
                    'mobile' => $row->mobile ?? '',
                    'country_code' => intval($row->country_code ?? ''),
                    'image' => $image,
                    'address' => $row->address ?? '',
                    'city' => $row->city ?? '',
                    'pincode' => $row->pincode ?? '',
                    'latitude' => $row->latitude ?? '',
                    'longitude' => $row->longitude ?? '',
                    'referral_code' => $row->referral_code ?? '',
                    'friends_code' => $row->friends_code ?? '',
                    'fcm_id' => array_values($fcm_ids_array) ?? '',
                    'created_at' => $row->created_at ?? '',
                ];
            }

            return response()->json([
                'error' => false,
                'message' => 'Profile Update Successfully',
                'language_message_key' => 'profile_updated_successfully',
                'data' => $rows,
            ]);
        }
    }

    public function delete_user(Request $request)
    {
        /*
            mobile:9874563214
            password:12345695
        */

        $rules = [
            'mobile' => 'nullable|numeric',
            'user_id' => 'numeric|exists:users,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            if (auth()->check()) {

                $user_id = auth()->user()->id;
            }

            $mobile = $request->input('mobile');
            $password = $request->input('password');
            $user_data = fetchDetails(User::class, ['id' => $user_id, 'mobile' => $mobile], ['id', 'username', 'password', 'active', 'mobile']);

            if ($user_data) {
                $credentials = [
                    'mobile' => $request->input('mobile'),
                    'password' => $request->input('password'),
                ];

                if (Auth::guard('api')->attempt($credentials)) {
                    $user = Auth::user();

                    if ($user) {
                        $role_id = $user->role_id;
                        $user_roles = fetchDetails(Role::class, ['id' => $role_id]);

                        if (!$user_roles->isEmpty() && $user_roles[0]->id == 2) {
                            $status = 'awaiting,received,processed,shipped';
                            $multiple_status = explode(',', $status);
                            $orders = app(OrderService::class)->fetchOrders('', $request->input('user_id'), $multiple_status);

                            foreach ($orders['order_data'] as $order) {

                                updateDetails(['status' => 'cancelled'], ['id' => $order->id], Order::class);
                                updateDetails(['active_status' => 'cancelled'], ['id' => $order->id], Order::class);

                                updateDetails(['active_status' => 'cancelled'], ['order_id' => $order->id], OrderItems::class);

                                app(OrderService::class)->process_refund($order->id, 'cancelled', 'orders');

                                $data = fetchDetails(OrderItems::class, ['order_id' => $order->id], ['product_variant_id', 'quantity']);
                                $product_variant_ids = [];
                                $qtns = [];

                                foreach ($data as $d) {
                                    $product_variant_ids[] = $d->product_variant_id;
                                    $qtns[] = $d->quantity;
                                }
                                app(ProductService::class)->updateStock($product_variant_ids, $qtns, 'plus');
                            }
                            deleteDetails(['id' => $user_id], User::class);
                            return response()->json(['error' => false, 'message' => 'User Deleted Successfully', 'language_message_key' => 'user_deleted_successfully']);
                        } else {
                            return response()->json(['error' => true, 'message' => 'Details do not match', 'language_message_key' => 'details_do_not_match']);
                        }
                    } else {
                        $response = [
                            'error' => true,
                            'message' => 'Details Does not Match',
                            'language_message_key' => 'details_do_not_match',
                            'data' => [],
                        ];
                        return response()->json($response);
                    }
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'User Not Found',
                        'language_message_key' => 'user_does_not_exist',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
            }
        }
    }
    public function add_to_favorites(Request $request)
    {
        /*
            product_id:60
            product_type:regular // {regular / combo}
            is_seller:1          // optional
            seller_id:18         // optional if is_seller is 0
        */

        $rules = [
            'product_id' => 'required_if:is_seller,0|numeric',
            'product_type' => 'required_if:is_seller,0|in:regular,combo',
            'is_seller' => 'required|in:0,1',
            'seller_id' => 'required_if:is_seller,1|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (auth()->check()) {
            $user_id = auth()->user()->id;
        } else {
            $response = [
                'error' => true,
                'message' => 'Please Login first.',
                'language_message_key' => 'please_login_first',
                'code' => 102,
            ];
            return response()->json($response);
        }

        $product_id = $request->input('product_id');
        $product_type = $request->input('product_type');
        $is_seller = $request->input('is_seller', 0);
        $seller_id = $request->input('seller_id', null);
        if ($is_seller == 0) {
            if (isExist(['user_id' => $user_id, 'product_id' => $product_id], Favorite::class)) {
                $response = [
                    'error' => true,
                    'message' => 'Already added to favorite !',
                    'language_message_key' => 'already_added_to_favorite',
                    'data' => [],
                ];
                return response()->json($response);
            }
        } elseif ($is_seller == 1) {
            if (isExist(['user_id' => $user_id, 'seller_id' => $seller_id], Favorite::class)) {
                $response = [
                    'error' => true,
                    'message' => 'Already added to favorite !',
                    'language_message_key' => 'already_added_to_favorite',
                    'data' => [],
                ];
                return response()->json($response);
            }
        }

        $data = [
            'user_id' => $user_id,
            'product_id' => $product_id,
            'product_type' => $product_type,
            'is_seller' => $is_seller,
            'seller_id' => $seller_id,
        ];

        $fav_res = Favorite::create($data);
        if ($fav_res) {
            $response = [
                'error' => false,
                'message' => 'Added to favorite !',
                'language_message_key' => 'added_to_favorite',
            ];
        } else {
            $response = [
                'error' => true,
                'message' => 'Not Added to favorite !',
                'language_message_key' => 'not_added_to_favorite',
            ];
        }

        return response()->json($response);
    }


    public function remove_from_favorites(Request $request)
    {
        /*
            product_id:60
            product_type:regular // {regular / combo}
            is_seller:1          // optional
            seller_id:18         // optional if is_seller is 0
        */


        $rules = [
            'product_id' => 'required_if:is_seller,0|numeric',
            'product_type' => 'required_if:is_seller,0|in:regular,combo',
            'is_seller' => 'required|in:0,1',
            'seller_id' => 'required_if:is_seller,1|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (!auth()->check()) {
            return response()->json([
                'error' => true,
                'message' => 'Please Login first.',
                'language_message_key' => 'please_login_first',
                'code' => 102,
            ]);
        }

        $user_id = auth()->user()->id;
        $is_seller = $request->input('is_seller');
        $product_id = $request->input('product_id');
        $product_type = $request->input('product_type');
        $seller_id = $request->input('seller_id');

        if ($is_seller == 0) {
            if (!isExist(['user_id' => $user_id, 'product_id' => $product_id, 'product_type' => $product_type], Favorite::class)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Item not added as favorite !',
                    'language_message_key' => 'item_not_added_as_favorite',
                    'data' => [],
                ]);
            }

            $data = [
                'user_id' => $user_id,
                'product_id' => $product_id,
                'product_type' => $product_type,
            ];
        } else {
            if (!isExist(['user_id' => $user_id, 'seller_id' => $seller_id], Favorite::class)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Seller not added as favorite !',
                    'language_message_key' => 'seller_not_added_as_favorite',
                    'data' => [],
                ]);
            }

            $data = [
                'user_id' => $user_id,
                'seller_id' => $seller_id,
            ];
        }

        deleteDetails($data, Favorite::class);

        return response()->json([
            'error' => false,
            'message' => 'Removed from favorite',
            'language_message_key' => 'removed_from_favorite',
            'data' => [],
        ]);
    }

    public function get_favorites(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
            'product_limit' => 'numeric',
            'product_offset' => 'numeric',
            'seller_limit' => 'numeric',
            'seller_offset' => 'numeric',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (!auth()->check()) {
            return response()->json([
                'error' => true,
                'message' => 'Please Login first.',
                'language_message_key' => 'please_login_first',
                'code' => 102,
            ]);
        }

        $user_id = auth()->id();
        $store_id = $request->input('store_id');
        $product_limit = $request->input('product_limit', 25);
        $product_offset = $request->input('product_offset', 0);
        $seller_limit = $request->input('seller_limit', 25);
        $seller_offset = $request->input('seller_offset', 0);
        $language_code = $request->attributes->get('language_code');

        // ✅ Favorite Products (regular)
        $favoriteProducts = Favorite::with(['product.store'])
            ->where('user_id', $user_id)
            ->where('product_type', 'regular')
            ->whereHas('product', function ($q) use ($store_id) {
                $q->where('store_id', $store_id)->where('status', 1);
            })
            ->skip($product_offset)
            ->take($product_limit)
            ->get();

        // ✅ Favorite Combo Products
        $favoriteComboProducts = Favorite::with(['comboProduct.store'])
            ->where('user_id', $user_id)
            ->where('product_type', 'combo')
            ->whereHas('comboProduct', function ($q) use ($store_id) {
                $q->where('store_id', $store_id)->where('status', 1);
            })
            ->skip($product_offset)
            ->take($product_limit)
            ->get();

        $result_products = [];

        foreach ($favoriteProducts as $fav) {
            $details = app(ProductService::class)->fetchProduct($user_id, null, $fav->product_id, '', $product_limit, $product_offset, '', '', '', '', '', '', $store_id, '', '', '', $language_code);
            if (!empty($details)) {
                $result_products[] = $details['product'][0] ?? null;
            }
        }

        foreach ($favoriteComboProducts as $fav) {
            $details = app(ComboProductService::class)->fetchComboProduct($user_id, null, $fav->product_id, '20', '0', '', '', '', '', '', $store_id, '', '', '', '', $language_code);
            if (!empty($details)) {
                $result_products[] = $details['combo_product'][0] ?? null;
            }
        }

        $total_products = Favorite::where('user_id', $user_id)
            ->where('product_type', 'regular')
            ->whereHas('product', fn($q) => $q->where('store_id', $store_id)->where('status', 1))
            ->count();

        $total_combo_products = Favorite::where('user_id', $user_id)
            ->where('product_type', 'combo')
            ->whereHas('comboProduct', fn($q) => $q->where('store_id', $store_id)->where('status', 1))
            ->count();
        $total_products += $total_combo_products;
        // ✅ Favorite Sellers
        $favoriteSellers = Favorite::with([
            'seller.stores' => function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            },
            'seller.user'
        ])
            ->where('user_id', $user_id)
            ->whereNotNull('seller_id')
            ->get()
            ->filter(fn($fav) => $fav->seller && $fav->seller->stores->isNotEmpty());

        $result_sellers = [];

        $paginatedSellers = $favoriteSellers->slice($seller_offset)->take($seller_limit);

        foreach ($paginatedSellers as $fav) {
            $store = $fav->seller->stores->first();
            $user = $fav->seller->user;

            $seller_total_products = Product::where('store_id', $store_id)->where('seller_id', $fav->seller->id)->count();

            $result_sellers[] = [
                'seller_id' => $fav->seller->id,
                'user_id' => $user_id,
                'store_name' => $store->pivot->store_name ?? '',
                'store_description' => $store->pivot->store_description ?? '',
                'rating' => $store->pivot->rating ?? 0,
                'no_of_ratings' => $store->pivot->no_of_ratings ?? 0,
                'store_logo' => app(MediaService::class)->getMediaImageUrl($store->pivot->logo ?? '', 'SELLER_IMG_PATH'),
                'total_products' => $seller_total_products,
                'is_favorite' => 1,
                'seller_address' => trim(str_replace(["\n", "\r"], '', $user->address ?? '')),
            ];
        }

        $response = [
            'error' => false,
            'message' => 'Data Retrieved Successfully',
            'language_message_key' => 'data_retrieved_successfully',
            'products' => [
                'total' => count(array_filter($result_products)),
                'data' => array_values(array_filter($result_products)),
            ],
            'sellers' => [
                'total' => $favoriteSellers->count(),
                'data' => array_values($result_sellers),
            ],
        ];

        if (empty($result_products) && empty($result_sellers)) {
            $response['error'] = true;
            $response['message'] = 'No Favorite Product(s) or Seller(s) Are Added';
            $response['language_message_key'] = 'no_favorite_products_or_sellers_added';
        }

        return response()->json($response);
    }


    public function add_address(AddressController $addressController, Request $request)
    {
        /*
        type:Home/Office/Others
        country_code:+91
        mobile:1234567890
        name:test user
        alternate_mobile:9876543210
        address:Time Square Empire
        landmark:Bhuj-Mirzapar Highway
        area_id:1
        city_id:2
        city_name:bhuj
        area_name:jay nagar
        general_area_name:jay nagar
        pincode_name:370001
        pincode:0123456
        state:Gujarat
        country:India
        latitude:45.453
        longitude:45.453
        is_default:1
        */


        $rules = [
            'mobile' => 'numeric',
            'alternate_mobile' => 'numeric',
            'pincode_name' => 'numeric',
            'pincode' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            $request['user_id'] = $user_id;

            $addressController->store($request);

            $res = $addressController->getAddress($user_id, null, true);

            $response = [
                'error' => false,
                'message' => 'Address Added Successfully',
                'language_message_key' => 'address_added_successfully',
                'data' => $res,
            ];
            return response()->json($response);
        }
    }
    public function update_address(AddressController $addressController, Request $request)
    {
        /*
        id:2
        type:Home/Office/Others
        country_code:+91
        mobile:1234567890
        name:test user
        alternate_mobile:9876543210
        address:Time Square Empire
        landmark:Bhuj-Mirzapar Highway
        area_id:1
        city_id:2
        city_name:bhuj
        area_name:jay nagar
        general_area_name:jay nagar
        pincode_name:370001
        pincode:0123456
        state:Gujarat
        country:India
        latitude:45.453
        longitude:45.453
        is_default:1
        */
        $rules = [
            'id' => 'numeric|required|exists:addresses,id',
            'mobile' => 'numeric',
            'alternate_mobile' => 'numeric',
            'pincode_name' => 'numeric',
            'pincode' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            $request['user_id'] = $user_id;
            $addressController->store($request);

            $res = $addressController->getAddress(null, $request->input('id'), true);
            $response = [
                'error' => false,
                'message' => 'Address updated Successfully',
                'language_message_key' => 'address_updated_successfully',
                'data' => $res,
            ];
            return response()->json($response);
        }
    }

    public function delete_address(AddressController $addressController, Request $request)
    {
        $rules = [
            'id' => 'numeric|required|exists:addresses,id',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $id = $request->input('id');
            $addressController->destroy($id);
            $response = [
                'error' => false,
                'message' => 'Address Deleted Successfully',
                'language_message_key' => 'address_deleted_successfully',
                'data' => [],
            ];
            return response()->json($response);
        }
    }
    public function get_address(AddressController $addressController, Request $request)
    {
        $rules = [
            'mobile' => 'numeric',
            'alternate_mobile' => 'numeric',
            'pincode_name' => 'numeric',
            'pincode' => 'numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }

            $res = $addressController->getAddress($user_id);


            if (!$res->isEmpty()) {

                $is_default_counter = collect($res)->pluck('is_default')->countBy();


                if (!isset($is_default_counter['1']) && !empty($res) && is_array($res)) {
                    updateDetails(['is_default' => '1'], ['id' => $res[0]->id], Address::class);
                    $res = $addressController->getAddress($user_id);
                }
                $response = [
                    'error' => false,
                    'message' => 'Address Retrieved Successfully',
                    'language_message_key' => 'address_retrieved_successfully',
                    'data' => $res,
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => 'No Address Found !',
                    'language_message_key' => 'no_address_found',
                    'data' => [],
                ];
            }
            return response()->json($response);
        }
    }

    public function get_user_cart(Request $request, CartController $cartController, PromoCodeController $promoCodeController)
    {
        /*
          delivery_pincode:370001 //optional when standard shipping is on
          only_delivery_charge:0 (default:0)// if 1 it's only returen shiprocket delivery charge OR return all cart information
          address_id:2 // only when only_delivery_charge is 1
          is_saved_for_later: 1 { default:0 }
        */

        $rules = [
            'only_delivery_charge' => 'required|numeric',
            'address_id' => $request->input('only_delivery_charge') == 1 ? 'required|numeric' : '',
            'delivery_pincode' => $request->input('only_delivery_charge') != 1 ? 'numeric' : '',
            'is_saved_for_later' => 'numeric',
            'store_id' => 'required|numeric|exists:stores,id',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => labels('please_login_first', 'Please Login first.'),
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            // dd($user_id);
            $settings = [];
            $settings = app(SettingService::class)->getSettings('shipping_method', true);
            $settings = json_decode($settings, true);
            $language_code = $request->attributes->get('language_code');
            $only_delivery_charge = request('only_delivery_charge', 0);
            $store_id = request('store_id') != null ? request('store_id') : '';
            $is_saved_for_later = request('is_saved_for_later', 0);
            $address_id = request('address_id', 0);
            $deliveryPincode = request('delivery_pincode', '');
            $area_id = fetchDetails(Address::class, ['id' => $address_id], ['area_id', 'area', 'pincode', 'city']);
            $zipcode = !$area_id->isEmpty() ? $area_id[0]->pincode : '';
            $city = !$area_id->isEmpty() ? $area_id[0]->city : '';
            $zipcode_id = '';
            if (isset($zipcode) && !empty($zipcode)) {
                $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
                $zipcode_id = !$zipcode_id->isEmpty() ? $zipcode_id[0]->id : '';
            }
            $city_id = '';
            if (isset($city) && !empty($city)) {
                $city_id = fetchDetails(City::class, ['name->en' => $city], 'id');
                $city_id = !$city_id->isEmpty() ? $city_id[0]->id : '';
            }

            $settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);
            $product_availability = "";
            $product_deliverability_type = fetchDetails(Store::class, ['id' => $store_id], 'product_deliverability_type');
            $product_deliverability_type = !$product_deliverability_type->isEmpty() ? $product_deliverability_type[0]->product_deliverability_type : '';
            if (!empty($address_id)) {
                if ($product_deliverability_type == 'city_wise_deliverability') {
                    $product_availability = app(DeliveryService::class)->checkCartProductsDeliverable($user_id, '', '', $store_id, $city, $city_id, $is_saved_for_later);
                } else {
                    $product_availability = app(DeliveryService::class)->checkCartProductsDeliverable($user_id, $zipcode, $zipcode_id, $store_id, '', '', $is_saved_for_later);
                }
            } else {
                $product_availability = [];
            }
            if (
                $only_delivery_charge == 1 &&
                !empty($product_availability) &&
                isset($product_availability[0]['is_valid_wight']) &&
                $product_availability[0]['is_valid_wight'] == 0
            ) {
                $response = [
                    'error' => true,
                    'message' => $product_availability[0]['message'] ?? labels('invalid_weight', 'Invalid weight'),
                    'data' => [],
                ];
                return response()->json($response);
            }

            $product_availability = is_array($product_availability) ? $product_availability : [];
            // dd($product_availability);
            $productDeliverableCollection = new Collection($product_availability);


            $productNotDeliverable = $productDeliverableCollection->filter(function ($var) {
                return $var['is_deliverable'] === false && $var['product_id'] !== null;
            })->values();
            $cart_user_data = $cartController->get_user_cart($user_id, $is_saved_for_later, '', $store_id);
            $other_saved_for_later = ($is_saved_for_later == 1) ? 0 : 1;
            $other_cart_user_data = $cartController->get_user_cart($user_id, $other_saved_for_later, '', $store_id);
            $cart_total = 0.0;

            for ($i = 0; $i < count($cart_user_data); $i++) {

                $cart_total += $cart_user_data[$i]->sub_total;
                if (!isset($product_availability[$i])) {
                    continue;
                }
                $cart[$i]['delivery_by'] = $product_availability[$i]['delivery_by'];
                $cart[$i]['is_deliverable'] = $product_availability[$i]['is_deliverable'];
                $cart[$i]['product_id'] = $product_availability[$i]['product_id'];
                $cart[$i]['product_qty'] = $product_availability[$i]['product_qty'];
                $cart[$i]['minimum_free_delivery_order_qty'] = $product_availability[$i]['minimum_free_delivery_order_qty'];
                $cart[$i]['product_delivery_charge'] = $product_availability[$i]['product_delivery_charge'];
                $cart[$i]['product_type'] = $cart_user_data[$i]->product_type;
                $cart[$i]['type'] = $cart_user_data[$i]->type;

                if ($cart[$i]['delivery_by'] == "standard_shipping") {
                    $standard_shipping_cart[] = $cart[$i];
                } else {
                    $local_shipping_cart[] = $cart[$i];
                }
            }
            $cart_total_response = app(CartService::class)->getCartTotal($user_id, false, $is_saved_for_later, $address_id, $store_id);
            if ($only_delivery_charge == 1) {
                $address_detail = fetchDetails(Address::class, ['id' => $address_id], 'pincode');
                $delivery_pincode = !$address_detail->isEmpty() ? $address_detail[0]->pincode : "";
            } else {
                $delivery_pincode = (isset($deliveryPincode)) ? $deliveryPincode : 0;
            }

            $tmp_cart_user_data = $cart_user_data;
            $weight = 0;

            if (!empty($tmp_cart_user_data)) {
                for ($i = 0; $i < count($tmp_cart_user_data); $i++) {

                    $cart_user_data[$i]->product_delivery_charge = "0";

                    if ($tmp_cart_user_data[$i]->cart_product_type == 'regular') {
                        $product_data = Product_variants::select('product_id', 'availability')
                            ->where('id', $tmp_cart_user_data[$i]->product_variant_id)
                            ->first();
                    }

                    if ($tmp_cart_user_data[$i]->cart_product_type == 'combo') {
                        $product_data = ComboProduct::select('id as product_id', 'availability')
                            ->where('id', $tmp_cart_user_data[$i]->product_id)
                            ->first();
                    }
                    $user_id = auth('sanctum')->id();
                    if (!empty($product_data->product_id)) {
                        // dd($product_data->product_id);
                        if ($tmp_cart_user_data[$i]->cart_product_type == 'regular') {
                            $pro_details = app(ProductService::class)->fetchProduct($user_id, NULL, $product_data->product_id, '', '20', '0', '', '', '', '', '', '', $store_id, '', '', '', $language_code);
                        } else {
                            $pro_details = app(ComboProductService::class)->fetchComboProduct($user_id, NULL, $product_data->product_id, '20', '0', '', '', '', '', '', $store_id, '', '', '', '', $language_code);
                        }

                        if (!empty($pro_details['product']) || !empty($pro_details['combo_product'])) {
                            if ($tmp_cart_user_data[$i]->cart_product_type == 'regular') {

                                $pro_details['product'][0]['net_amount'] = $cart_user_data[$i]->net_amount;

                                if ($pro_details['product'][0]['availability'] == 0 && $pro_details['product'][0]['availability'] != null) {
                                    updateDetails(['is_saved_for_later' => '1'], $cart_user_data[$i]->id, Cart::class);
                                    unset($cart_user_data[$i]);
                                }

                                if (!empty($pro_details['product'])) {
                                    $cart_user_data[$i]->product_details = $pro_details['product'];
                                } else {
                                    deleteDetails(['id' => $cart_user_data[$i]->id], Cart::class);
                                    unset($cart_user_data[$i]);
                                    continue;
                                }
                            }

                            if ($tmp_cart_user_data[$i]->cart_product_type == 'combo') {

                                $pro_details['combo_product'][0]->net_amount = $cart_user_data[$i]->net_amount;

                                if ($pro_details['combo_product'][0]->availability == 0 && $pro_details['combo_product'][0]->availability != null) {
                                    updateDetails(['is_saved_for_later' => '1'], $cart_user_data[$i]->id, Cart::class);
                                    unset($cart_user_data[$i]);
                                }

                                if (!empty($pro_details['combo_product'])) {
                                    $cart_user_data[$i]->product_details = $pro_details['combo_product'];
                                } else {
                                    deleteDetails(['id' => $cart_user_data[$i]->id], Cart::class);
                                    unset($cart_user_data[$i]);
                                    continue;
                                }
                            }
                        } else {
                            deleteDetails(['id' => $cart_user_data[$i]->id], Cart::class);
                            unset($cart_user_data[$i]);
                            continue;
                        }
                    } else {
                        deleteDetails(['id' => $cart_user_data[$i]->id], Cart::class);
                        unset($cart_user_data[$i]);
                        continue;
                    }
                }

                if (isset($settings['shiprocket_shipping_method']) && $settings['shiprocket_shipping_method'] == 1) {

                    $parcels = app(ShiprocketService::class)->makeShippingParcels($tmp_cart_user_data);
                    $parcels_details = app(ShiprocketService::class)->checkParcelsDeliverability($parcels, $delivery_pincode, $cart_total_response['sub_total']);
                }
            }

            if ($cart_user_data->isEmpty()) {
                $response = [
                    'error' => true,
                    'message' => labels('app_labels.your_cart_is_empty_discover_amazing_products_and_start_adding_them', 'Your cart is empty. Discover amazing products and start adding them'),
                    // 'message' => app_label('your_cart_is_empty_discover_amazing_products_and_start_adding_them', 'Your cart is empty. Discover amazing products and start adding them!'),
                    'language_message_key' => 'your_cart_is_empty_discover_amazing_products_and_start_adding_them',
                    'data' => array(),
                ];
                return response()->json($response);
            }
            if ($only_delivery_charge == 0) {
                $search = request()->input('search', '');
                $limit = request()->input('limit', 25);
                $offset = request()->input('offset', 0);
                $order = request()->input('order', 'DESC');
                $sort = request()->input('sort', 'id');

                $product_variant_ids = [];
                $qtys = [];
                $product_types = [];

                foreach ($tmp_cart_user_data as $item) {
                    $product_variant_ids[] = $item->product_variant_id;
                    $qtys[] = $item->qty;
                    $product_types[] = $item->product_type;
                }
                // dd($product_variant_ids);
                $check_current_stock_status = validateStock($product_variant_ids, $qtys, $product_types);
                // dd($check_current_stock_status);
                $out_of_stock_data = [];

                $response = [
                    'error' => false,
                    'message' => labels('data_retrieved_from_cart', 'Data Retrieved From Cart !'),
                    'language_message_key' => 'data_retrieved_from_cart',
                    'total_quantity' => $cart_total_response['quantity'],
                    'sub_total' => $cart_total_response['sub_total'],
                    'item_total' => $cart_total_response['item_total'],
                    'discount' => $cart_total_response['discount'] ?? strval($cart_total_response['discount']),
                    'currency_sub_total_data' => app(CurrencyService::class)->getPriceCurrency($cart_total_response['sub_total']),
                ];
                // dd($local_shipping_cart);
                $deliveryCharge = 0;
                if (!empty($local_shipping_cart)) {

                    $settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);

                    $deliveryCharge = app(DeliveryService::class)->getDeliveryCharge(request()->input('address_id'), $cart_total_response['sub_total'], $local_shipping_cart, $store_id);
                    // dd($deliveryCharge);
                    if ((isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'zipcode_wise_delivery_charge') || (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'city_wise_delivery_charge') || (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'global_delivery_charge')) {
                        $response['delivery_charge'] = str_replace(",", "", $deliveryCharge);
                        $response['currency_delivery_charge_data'] = app(CurrencyService::class)->getPriceCurrency($response['delivery_charge']);
                    } else {
                        $response['delivery_charge'] = 0;
                        for ($i = 0; $i < count($tmp_cart_user_data); $i++) {
                            $product_delivery_charge = isset($deliveryCharge[$i]['delivery_charge']) && !empty($deliveryCharge[$i]['delivery_charge']) ? str_replace(',', '', $deliveryCharge[$i]['delivery_charge']) : 0;
                            $cart_user_data[$i]->product_delivery_charge = $product_delivery_charge;
                            $cart_user_data[$i]->currency_product_delivery_charge_data = app(CurrencyService::class)->getPriceCurrency($cart_user_data[$i]->product_delivery_charge);
                            $response['delivery_charge'] += (float) $product_delivery_charge;
                            $response['currency_delivery_charge_data'] = app(CurrencyService::class)->getPriceCurrency($response['delivery_charge']);
                        }
                    }
                }
                $response['delivery_charge'] = isset($response['delivery_charge']) ? strval($response['delivery_charge']) : '0';
                $deliveryCharge = (is_array($deliveryCharge) && isset($deliveryCharge[0]['delivery_charge']))
                    ? (float) str_replace(',', '', $deliveryCharge[0]['delivery_charge'])
                    : (float) str_replace(',', '', $deliveryCharge);
                $response['tax_percentage'] = $cart_total_response['tax_percentage'] ?? "0";
                $response['tax_amount'] = $cart_total_response['tax_amount'] ?? "0";
                $response['currency_tax_amount_data'] = app(CurrencyService::class)->getPriceCurrency($response['tax_amount']);

                $response['overall_amount'] = $cart_total_response['overall_amount'];
                $response['currency_overall_amount_data'] = app(CurrencyService::class)->getPriceCurrency($response['overall_amount']);
                $response['total_arr'] = $cart_total_response['total_arr'];
                $response['currency_total_arr_data'] = app(CurrencyService::class)->getPriceCurrency($response['total_arr']);
                $response['variant_id'] = $cart_total_response['variant_id'];

                if (isset($settings['shiprocket_shipping_method']) && $settings['shiprocket_shipping_method'] == 1) {
                    $response['parcels_details'] = $parcels_details;
                }

                $response['cart'] = array_values($cart_user_data->toArray());
                $other_key = ($is_saved_for_later == 1) ? 'active_cart_data' : 'save_for_later_data';
                $response[$other_key] = array_values($other_cart_user_data->toArray());
                $response['out_of_stock_data'] = !empty($out_of_stock_data) ? $out_of_stock_data : [];
                $result = $promoCodeController->getPromoCodes($limit, $offset, $sort, $order, $search, $store_id);

                $response['promo_codes'] = $result['data'];

                return response()->json($response);
            } else {
                // if only_delivery_charge is 1
                $data = [];

                if (!empty($standard_shipping_cart)) {

                    $delivery_pincode = fetchDetails(Address::class, ['id' => request()->input('address_id')], 'pincode');
                    if ($delivery_pincode->isEmpty()) {
                        return response()->json([
                            'error' => true,
                            'message' => labels('address_not_found', 'Address not found'),
                            'language_message_key' => 'address_not_found',
                            'data' => []
                        ]);
                    }
                    $parcels = app(ShiprocketService::class)->makeShippingParcels($tmp_cart_user_data);
                    $parcels_details = app(ShiprocketService::class)->checkParcelsDeliverability($parcels, $delivery_pincode[0]->pincode, $cart_total);

                    $data['delivery_charge_with_cod'] = $parcels_details['delivery_charge_with_cod'];
                    $data['currency_delivery_charge_with_cod_data'] = app(CurrencyService::class)->getPriceCurrency($data['delivery_charge_with_cod']);
                    $data['delivery_charge_without_cod'] = $parcels_details['delivery_charge_without_cod'];
                    $data['currency_delivery_charge_without_cod_data'] = app(CurrencyService::class)->getPriceCurrency($data['delivery_charge_without_cod']);
                    $data['estimated_delivery_days'] = $parcels_details['estimated_delivery_days'];
                    $data['estimate_date'] = $parcels_details['estimate_date'];
                }

                $response['error'] = false;
                $response['message'] = labels('data_retrieved_successfully', 'Data Retrieved Successfully !');
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['data'] = $data;

                return response()->json($response);
            }
        }
    }

    public function get_sections(Request $request)
    {
        /*
            store_id : 1
            limit:10            // { default - 25 } {optional}
            offset:0            // { default - 0 } {optional}
            user_id:12              {optional}
            section_id:4            {optional}
            attribute_value_ids : 34,23,12 //
            top_rated_product: 1 // { default - 0 } optional
            p_limit:10          // { default - 10 } {optional}
            p_offset:10         // { default - 0 } {optional}
            p_sort:pv.price      // { default - pid } {optional}
            p_order:asc         // { default - desc } {optional}
            discount: 5 // { default - 5 } optional
            min_price:10000          // optional
            max_price:50000          // optional
            zipcode:1          // optional
        */

        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'section_id' => 'numeric',
            'p_limit' => 'numeric',
            'p_offset' => 'numeric',
            'p_sort' => 'numeric',
            'p_order' => 'string',
            'discount' => 'numeric',
            'zipcode' => 'nullable|string',
            'min_price' => 'nullable|numeric|lte:max_price',
            'max_price' => 'nullable|numeric|gte:min_price',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $limit = $request->filled('limit') ? $request->input('limit', 25) : 25;
            $offset = $request->filled('offset') ? $request->input('offset', 0) : 0;
            $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : 0;
            $section_id = $request->filled('section_id') ? $request->input('section_id') : 0;
            $store_id = $request->filled('store_id') ? $request->input('store_id') : 0;
            $filters['attribute_value_ids'] = $request->input('attribute_value_ids', null);
            $filters['product_type'] = $request->input('top_rated_product') == 1 ? 'top_rated_product_including_all_products' : null;
            $p_limit = $request->filled('p_limit') ? $request->input('p_limit', 10) : 10;
            $p_offset = $request->filled('p_offset') ? $request->input('p_offset', 0) : 0;
            $p_order = $request->filled('p_order') ? $request->input('p_order', 'DESC') : 'DESC';
            $p_sort = $request->filled('p_sort') ? $request->input('p_sort', 'p.id') : 'products.id';
            $filters['discount'] = $request->input('discount', 0);
            $filters['min_price'] = $request->filled('min_price') ? $request->input('min_price', 0) : 0;
            $filters['max_price'] = $request->filled('max_price') ? $request->input('max_price', 0) : 0;
            $zipcode = $request->filled('zipcode') ? $request->input('zipcode', 0) : 0;

            if ($request->filled('zipcode')) {
                $zipcode = $request->input('zipcode');
                $isPincode = Zipcode::where('zipcode', $zipcode)->exists();

                if ($isPincode) {
                    $zipcode_id = Zipcode::where('zipcode', $zipcode)->value('id');
                    $zipcode = $zipcode_id;
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Products Not Found!',
                        'language_message_key' => 'products_not_found',
                        'data' => [],
                    ], 200);
                }
            }
            $sections = Section::where('store_id', $store_id)
                ->when($request->filled('section_id'), function ($query) use ($request) {
                    return $query->where('id', $request->input('section_id'));
                })
                ->orderBy('row_order')->skip($offset)->take($limit)->get();
            $language_code = $request->attributes->get('language_code');
            if (!$sections->isEmpty()) {
                foreach ($sections as &$section) {
                    $section->title = app(TranslationService::class)->getDynamicTranslation(Section::class, 'title', $section->id, $language_code);
                    $section->short_description = app(TranslationService::class)->getDynamicTranslation(Section::class, 'short_description', $section->id, $language_code);
                    // dd($section->updated_at);
                    if ($section->product_type == 'custom_combo_products') {
                        $section->categories = $section->categories ?: '';
                        $comboproductIds = explode(',', $section->product_ids);
                        $comboproductIds = array_filter($comboproductIds);
                        $products = app(ComboProductService::class)->fetchComboProduct($user_id, '', $comboproductIds, $limit, $offset, $p_sort, $p_order, '', '', '', $store_id, '', '', '', '', $language_code);
                        $response = [
                            'error' => false,
                            'message' => 'Sections retrieved successfully.',
                            'language_message_key' => 'sections_retrieved_successfully',
                        ];
                        $response['min_price'] = isset($products['min_price']) && !empty($products['min_price']) ? strval($products['min_price']) : '0';
                        $response['max_price'] = isset($products['max_price']) && !empty($products['max_price']) ? strval($products['max_price']) : '0';
                        $section->title = app(TranslationService::class)->getDynamicTranslation(Section::class, 'title', $section->id, $language_code);
                        $section->short_description = app(TranslationService::class)->getDynamicTranslation(Section::class, 'short_description', $section->id, $language_code);
                        $section->banner_image = app(MediaService::class)->getMediaImageUrl($section->banner_image);
                        $section->total = strval($products['total']);
                        $section->filters = $products['filters'] ?? [];
                        $section->product_details = $products['combo_product'];

                        $section->product_ids = $section->product_ids ?: '';
                        $category_ids = implode(',', array_filter(collect($products['category_ids'])->unique()->values()->all()));
                        $brand_ids = implode(',', array_filter(collect($products['brand_ids'])->unique()->values()->all()));
                        $section->category_ids = $category_ids;
                        $section->brand_ids = $brand_ids;
                    } else {
                        $productIds = explode(',', $section->product_ids);
                        $productIds = array_filter($productIds);

                        $filters = [
                            'show_only_active_products' => 1,
                            'product_type' => $request->input('top_rated_product') ? 'top_rated_product_including_all_products' : null,
                        ];

                        if (empty($filters['product_type']) && !empty($section->product_type)) {
                            $filters['product_type'] = $section->product_type;
                        }

                        $categories = $section->categories ? explode(',', $section->categories) : '';
                        $products = app(ProductService::class)->fetchProduct($user_id, $filters, $productIds, $categories, $p_limit, $p_offset, $p_sort, $p_order, null, $zipcode, null, '', '', '', '', '', $language_code);
                        if (!empty($products['product'])) {
                            $response = [
                                'error' => false,
                                'message' => 'Sections retrieved successfully.',
                                'language_message_key' => 'sections_retrieved_successfully',
                            ];
                            $response['min_price'] = isset($products['min_price']) && !empty($products['min_price']) ? strval($products['min_price']) : '0';
                            $response['max_price'] = isset($products['max_price']) && !empty($products['max_price']) ? strval($products['max_price']) : '0';
                            $section->title = app(TranslationService::class)->getDynamicTranslation(Section::class, 'title', $section->id, $language_code);
                            $section->short_description = app(TranslationService::class)->getDynamicTranslation(Section::class, 'short_description', $section->id, $language_code);
                            $section->banner_image = app(MediaService::class)->getMediaImageUrl($section->banner_image);
                            $section->total = strval($products['total']);
                            $section->filters = $products['filters'] ?? [];

                            $section->product_details = $products['product'];

                            $section->categories = $section->categories ?: '';



                            $product_id = fetchDetails(Product::class, fields: 'id', where_in_key: 'category_id', where_in_value: explode(',', $section->categories));
                            $product_ids = [];
                            foreach ($product_id as $ids) {

                                $product_ids[] = $ids->id;
                            }


                            // Unset 'total' property from all elements of 'product_details' array
                            foreach ($section->product_details as $product_detail) {
                                unset($product_detail->total);
                            }
                            $category_ids = implode(',', array_filter(collect($section->product_details)->pluck('category_id')->unique()->values()->all()));
                            $brand_ids = implode(',', array_filter(collect($section->product_details)->pluck('brand_id')->unique()->values()->all()));
                            $section->category_ids = $category_ids;
                            $section->product_ids = $section->product_ids ? $section->product_ids : ($section->category_ids ? implode(',', $product_ids) : '');
                            $section->brand_ids = $brand_ids;
                        } else {
                            $response = [
                                'error' => false,
                                'message' => 'Sections retrieved successfully.',
                                'language_message_key' => 'sections_retrieved_successfully',
                            ];
                            $section->total = '0';
                            $section->filters = [];
                        }
                    }
                }
                foreach ($sections as &$section) {
                    foreach ($section as $key => &$value) {
                        $value = $value ?? "";
                        $section->banner_image = app(MediaService::class)->getMediaImageUrl($section->banner_image);
                        $section->created_at = $section->created_at ? Carbon::parse($section->created_at)->format('Y-m-d H:i:s') : '';
                        $section->updated_at = $section->updated_at ? Carbon::parse($section->updated_at)->format('Y-m-d H:i:s') : '';
                    }
                }
                // $response['data'] = $sections;
                $response['data'] = $sections->map(function ($section) {
                    $sectionArray = $section->toArray();
                    $sectionArray['created_at'] = Carbon::parse($section->created_at)->format('Y-m-d H:i:s');
                    $sectionArray['updated_at'] = Carbon::parse($section->updated_at)->format('Y-m-d H:i:s');
                    $sectionArray['banner_image'] = app(MediaService::class)->getMediaImageUrl($section->banner_image);
                    return $sectionArray;
                });
            } else {
                $response = [
                    'error' => true,
                    'message' => 'No sections are available.',
                    'language_message_key' => 'no_sections_available',
                    'data' => [],
                ];
            }

            return response()->json($response);
        }
    }

    public function get_zipcode_by_city_id(AreaController $areaController, Request $request)
    {
        /*
            id:'57'
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort:               // { a.name / a.id } optional
            order:DESC/ASC      // { default - ASC } optional
            search:value        // {optional}
        */

        $rules = [
            'city_id' => 'numeric|required|exists:cities,id',
            'limit' => 'numeric',
            'offset' => 'numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $limit = request('limit', 25);
            $offset = request('offset', 0);
            $sort = request('sort', 'zipcode');
            $order = request('order', 'ASC');
            $search = request('search', '');
            $city_id = request('city_id');

            $result = $areaController->getAreaByCity($city_id, $sort, $order, $search, $limit, $offset);
            return response()->json($result);
        }
    }

    public function validate_promo_code(Request $request)
    {
        /*
            promo_code:'NEWOFF10'
            user_id:28
            final_total:'300'
        */
        $rules = [
            'promo_code' => 'required',
            'final_total' => 'required|numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            $promo_code = request('promo_code');
            $final_total = request('final_total');
            $language_code = $request->attributes->get('language_code');
            // dd($language_code);
            $res = app(abstract: PromoCodeService::class)->validatePromoCode($promo_code, $user_id, $final_total, '0', $language_code);
            // dd($res);
            return response()->json($res->original);
        }
    }

    public function place_order(Request $request, TransactionController $transactionController)
    {
        /*
            store_id:1
            email:testmail123@gmail.com // only enter when ordered product is digital product and one of them is not downloadable(download_allowed = 0)
            delivery_charge:20.0
            latitude:40.1451
            longitude:-45.4545
            promo_code_id:1 {optional}
            payment_method: Paypal / Payumoney / COD / PAYTM
            address_id:17
            delivery_date:10/12/2012
            delivery_time:Today - Evening (4:00pm to 7:00pm)
            is_wallet_used:1 {By default 0}
            wallet_balance_used:1
            order_note:text      //{optional}
            order_payment_currency_code:INR
            shipping_option_id:1 {optional} // ID of selected shipping option from delivery check
            shipping_option_name:Standard Shipping {optional} // Name of selected shipping option
            shipping_carrier:Shiprocket {optional} // Carrier name for selected shipping option
            shipping_estimated_days:3-5 {optional} // Estimated delivery days

        */

        $rules = [
            'promo_code_id' => 'nullable',
            'order_note' => 'nullable',
            'is_wallet_used' => 'required|numeric',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'delivery_date' => 'nullable',
            'delivery_time' => 'nullable',
            'store_id' => 'required|numeric|exists:stores,id',
            'order_payment_currency_code' => 'required',
            'status' => 'required',
            // New single shipping_option parameter (e.g., "cheapest", "fastest", or specific shipping name)
            'shipping_option' => 'nullable|string|max:255',
            // Keep old individual fields for backward compatibility
            'shipping_option_id' => 'nullable|numeric',
            'shipping_option_name' => 'nullable|string|max:255',
            'shipping_carrier' => 'nullable|string|max:255',
            'shipping_estimated_days' => 'nullable|string|max:50'

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            if (auth()->check()) {
                $user_id = auth()->user()->id;
                // dd($user_id);
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }

            $store_id = request('store_id') != null ? request('store_id') : '';
            //dd($user_id,$store_id);
            $cart_data = fetchDetails(Cart::class, ['user_id' => $user_id, 'store_id' => $store_id, 'is_saved_for_later' => 0], ['product_variant_id', 'qty', 'product_type']);
            if ($cart_data->isEmpty()) {
                $response = [
                    'error' => true,
                    'message' => 'Your cart is empty. Discover amazing products and start adding them!',
                    'language_message_key' => 'your_cart_is_empty_discover_amazing_products_and_start_adding_them',
                    'code' => 103,
                ];
                return response()->json($response);
            }
            $product_variant_ids = collect($cart_data)->pluck('product_variant_id')->toArray();
            $cart_product_type = collect($cart_data)->pluck('product_type')->toArray();
            $qty = collect($cart_data)->pluck('qty')->toArray();
            $request['product_variant_id'] = implode(',', $product_variant_ids);
            $request['cart_product_type'] = implode(',', $cart_product_type);
            $request['quantity'] = implode(',', $qty);
            $request['mobile'] = auth()->user()->mobile;
            $language_code = $request->attributes->get('language_code');


            // affiliate data

            $affiliate_reference = $request->input('affiliate_reference') ?? "";

            $affiliate_data = [];
            if ($affiliate_reference) {
                $affiliateCartData = app(CartService::class)->getCartTotal($user_id, false, '', $request->input('address_id'), $store_id, $affiliate_reference);
                if (!$affiliateCartData->isempty()) {
                    foreach ($affiliateCartData['cart_items'] as $cart_items) {
                        $affiliate_data[$cart_items['product_variant_id']] = [
                            'affiliate_id' => $cart_items['affiliate_id'],
                            'affiliate_token' => $cart_items['affiliate_token'],
                            'category_commission' => $cart_items['category_commission'],
                            'affiliate_commission_amount' => $cart_items['affiliate_commission_amount'],
                        ];
                    }
                }
            }
            // affiliate end

            // get details based on cart product type
            $productVariant = Product_variants::with('product')
                ->whereIn('id', $product_variant_ids)
                ->whereHas('cart', fn($q) => $q->where('product_type', 'regular'))
                ->orderByRaw('FIELD(id, ' . implode(',', $product_variant_ids) . ')')
                ->get()
                ->map(function ($variant) {
                    return (object) [
                        'type' => $variant->product->type,
                        'download_allowed' => $variant->product->download_allowed,
                        'is_attachment_required' => $variant->product->is_attachment_required,
                        'product_name' => $variant->product->name,
                        'id' => $variant->id,
                    ];
                });

            $comboProductVariant = ComboProduct::whereIn('id', $product_variant_ids)
                ->whereHas('cart', fn($q) => $q->where('product_type', 'combo'))
                ->orderByRaw('FIELD(id, ' . implode(',', $product_variant_ids) . ')')
                ->get()
                ->map(function ($combo) {
                    return (object) [
                        'type' => $combo->product_type,
                        'download_allowed' => $combo->download_allowed,
                        'is_attachment_required' => $combo->is_attachment_required,
                        'product_name' => $combo->title,
                        'id' => $combo->id,
                    ];
                });

            $productVariant = $productVariant->concat($comboProductVariant);

            foreach ($productVariant as $variant) {

                if ($variant->is_attachment_required && empty($request->file('order_attachment'))) {
                    $response = [
                        'error' => true,
                        'message' => 'Order attachment is required for product ' . app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $variant->id, $language_code) . ' and no files were provided.',
                        'language_message_key' => 'attachment_is_required_for_this_product_when_you_place_an_order',
                        'code' => 102,
                    ];
                    return response()->json($response);
                }
            }
            $request['attachment_path'] = array();
            if (!File::exists('storage/order_attachments')) {
                File::makeDirectory('storage/order_attachments', 0755, true);
            }
            if ($request->file('order_attachment')) {
                foreach ($product_variant_ids as $variant_id) {
                    foreach ($request->file('order_attachment') as $key => $attachment) {

                        if ($variant_id == $key) {

                            try {
                                $order_attachments['attachment_path'] = '';
                                $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
                                $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
                                $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';
                                // dd($disk);
                                $media = StorageType::find($mediaStorageType);
                                $mediaIds = [];
                                if ($request->hasFile('order_attachment')) {
                                    $mediaItem = $media->addMedia($attachment)
                                        ->sanitizingFileName(function ($fileName) use ($media) {
                                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);
                                            return "{$baseName}-{$uniqueId}.{$extension}";
                                        })
                                        ->toMediaCollection('order_attachments', $disk);

                                    $mediaIds[] = $mediaItem->id;
                                    if ($disk == 'public') {
                                        $order_attachments = [
                                            'attachment_path' => 'order_attachments/' . $mediaItem->file_name,
                                        ];
                                    }
                                }
                                if ($disk == 's3') {
                                    $media_list = $media->getMedia('order_attachments');
                                    $media_url = $media_list[($media_list->count()) - (count($mediaIds))]->getUrl();
                                    $order_attachments = [
                                        'attachment_path' => $media_url,
                                    ];
                                    Media::destroy($mediaIds);
                                }

                                $request_data = $request->all();

                                // dd($request_data);
                                $request_data['attachment_path'][$key] = $order_attachments['attachment_path'];
                                $request->merge($request_data);
                            } catch (Exception $e) {

                                return response()->json([
                                    'error' => true,
                                    'message' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                }
            }


            $productType = $productVariant->pluck('type')->unique()->toArray();
            $downloadAllowed = $productVariant->pluck('download_allowed')->unique()->toArray();

            if (in_array(0, $downloadAllowed) && $productType[0] === 'digital_product') {

                $rules = [
                    'email' => 'required|email',

                ];
                if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
                    return $response;
                }
            }

            if ($request->input('is_wallet_used') == '1') {
                $request['payment_method'] = 'wallet';
                $rules = [
                    'wallet_balance_used' => 'required|numeric',
                ];
                if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
                    return $response;
                }
            }

            //physical_product product type is used in combo product
            if (in_array($productType[0], ["variable_product", "simple_product", "physical_product"])) {
                $rules = [
                    'address_id' => 'required|numeric',
                ];
                if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
                    return $response;
                }
            }

            $request['order_note'] = $request->filled('order_note') ? $request->input('order_note') : null;

            /* Checking for product availability */

            $area_details = fetchDetails(Address::class, ['id' => $request->input('address_id')], ['pincode', 'city']);

            $zipcode = isset($area_details) && isset($area_details[0]) ? $area_details[0]->pincode : '';

            $city = isset($area_details) && isset($area_details[0]) ? $area_details[0]->city : '';

            $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
            // dd($zipcode_id);
            $zipcode_id = !$zipcode_id->isEmpty() ? $zipcode_id[0]->id : '';

            // $city_id = fetchDetails(City::class, ['name' => $city], 'id');
            $city_id = fetchDetails(City::class, ['name->en' => $city], 'id');
            $city_id = isset($city_id) && isset($city_id[0]) ? $city_id[0]->id : '';


            $settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);
            if (isset($settings[0]->product_deliverability_type) && !empty($settings[0]->product_deliverability_type)) {
                if ($settings[0]->product_deliverability_type == 'city_wise_deliverability') {
                    $productDeliverable = app(DeliveryService::class)->checkCartProductsDeliverable($user_id, '', '', $store_id, $city, $city_id);
                } else {

                    $productDeliverable = app(DeliveryService::class)->checkCartProductsDeliverable($user_id, $zipcode, $zipcode_id, $store_id);
                }
            }



            if (!empty($productDeliverable) && ($productType[0] == "variable_product" || $productType[0] == "simple_product" || $productType[0] == "physical_product")) {

                $productDeliverableCollection = new Collection($productDeliverable);
                if (!$productDeliverableCollection->isEmpty()) {
                    // Filter out items where 'is_deliverable' is false and 'product_id' is not null
                    $productNotDeliverable = $productDeliverableCollection->filter(function ($var) {
                        return $var['is_deliverable'] === false && $var['product_id'] !== null;
                    })->values();

                    // Filter out items where 'product_id' is not null
                    $productDeliverable = $productDeliverableCollection->filter(function ($var) {
                        return $var['product_id'] !== null;
                    })->values();
                }

                if (!$productNotDeliverable->isEmpty()) {

                    $response = [
                        'error' => true,
                        'message' => "Some of the item(s) are not delivarable on selected address. Try changing address or modify your cart items.",
                        'language_message_key' => 'some_items_not_deliverable_on_selected_address_change_the_address',
                        'data' => $productDeliverable,
                    ];
                    return response()->json($response);
                } else {
                    $request['is_delivery_charge_returnable'] = isset($request['delivery_charge']) && !empty($request['delivery_charge']) && $request['delivery_charge'] != '' && $request['delivery_charge'] > 0 ? 1 : 0;
                    $request['user_id'] = $user_id;
                    $request['affiliate_data'] = $affiliate_data;
                    $request['delivery_type'] = isset($productDeliverable) && isset($productDeliverable[0]) ? $productDeliverable[0]['delivery_by'] : '';
                    $request['is_shiprocket_order'] = (isset($productDeliverable) && !empty($productDeliverable) && $productDeliverable[0]['delivery_by'] == 'standard_shipping') ? '1' : '0';
                    // Extract shipping details if shipping_option is provided
                    if ($request->filled('shipping_option') && !$request->filled('shipping_option_name')) {
                        $shipping_option_input = strtolower(trim($request->input('shipping_option')));

                        // Re-check delivery to get available shipping options
                        $delivery_pincode = fetchDetails(Address::class, ['id' => $request->input('address_id')], 'pincode');
                        if (!$delivery_pincode->isEmpty()) {
                            $cart_user_data = fetchDetails(Cart::class, ['user_id' => $user_id, 'store_id' => $store_id, 'is_saved_for_later' => 0]);

                            if (!$cart_user_data->isEmpty()) {
                                // Calculate cart total for free delivery logic
                                $cart_total_data = app(CartService::class)->getCartTotal($user_id, false, 0, $request->input('address_id'), $store_id);
                                $cart_total = isset($cart_total_data['sub_total']) ? floatval($cart_total_data['sub_total']) : 0;

                                $parcels = app(ShiprocketService::class)->makeShippingParcels($cart_user_data);
                                $parcels_details = app(ShiprocketService::class)->checkParcelsDeliverability($parcels, $delivery_pincode[0]->pincode, $cart_total);

                                // Extract shipping details based on shipping_option
                                if ($shipping_option_input == 'cheapest' || $shipping_option_input == 'standard') {
                                    // Use the cheapest option (without COD for lower cost)
                                    $request->merge([
                                        'shipping_option_id' => 1,
                                        'shipping_option_name' => 'Standard Shipping',
                                        'shipping_carrier' => 'Shiprocket',
                                        'shipping_estimated_days' => $parcels_details['estimated_delivery_days'] ?? ''
                                    ]);
                                } elseif ($shipping_option_input == 'fastest' || $shipping_option_input == 'express') {
                                    // Use the fastest option (shortest delivery time)
                                    $request->merge([
                                        'shipping_option_id' => 2,
                                        'shipping_option_name' => 'Express Shipping',
                                        'shipping_carrier' => 'Shiprocket',
                                        'shipping_estimated_days' => $parcels_details['estimated_delivery_days'] ?? ''
                                    ]);
                                } else {
                                    // Use the provided shipping name as-is
                                    $request->merge([
                                        'shipping_option_id' => 1,
                                        'shipping_option_name' => ucwords($request->input('shipping_option')),
                                        'shipping_carrier' => 'Shiprocket',
                                        'shipping_estimated_days' => $parcels_details['estimated_delivery_days'] ?? ''
                                    ]);
                                }
                            }
                        }
                    }

                    // Add shipping option data to request
                    $request['shipping_option'] = [
                        'shipping_option_id' => $request->input('shipping_option_id'),
                        'shipping_option_name' => $request->input('shipping_option_name'),
                        'shipping_carrier' => $request->input('shipping_carrier'),
                        'shipping_estimated_days' => $request->input('shipping_estimated_days')
                    ];
                    $res = app(OrderService::class)->placeOrder($request);

                    if (!empty($res)) {
                        if ($request['payment_method'] == "bank_transfer" || $request['payment_method'] == "direct_bank_transfer") {

                            $data = new Request([
                                'status' => "awaiting",
                                'txn_id' => null,
                                'message' => null,
                                'order_id' => $res->original['order_id'],
                                'user_id' => $user_id,
                                'type' => $request['payment_method'],
                                'amount' => $res->original['final_total'],
                            ]);

                            $transactionController->store($data);
                        }
                    }
                    if (isset($res->original) && !empty($res->original)) {

                        return response()->json($res->original);
                    } else {
                        return response()->json($res);
                    }
                }
            } else {

                $request['is_delivery_charge_returnable'] = isset($request['delivery_charge']) && !empty($request['delivery_charge']) && $request['delivery_charge'] != '' && $request['delivery_charge'] > 0 ? 1 : 0;
                $request['user_id'] = $user_id;
                $request['store_id'] = $store_id;
                $request['status'] = isset($request['status']) && !empty($request['status']) && $request['status'] != '' ? $request['status'] : 'awaiting';
                $request['affiliate_data'] = $affiliate_data;

                // Set delivery type if shipping option is provided (implies Shiprocket/Standard Shipping)
                if ($request->filled('shipping_option') || $request->filled('shipping_option_id')) {
                    $request['delivery_type'] = 'standard_shipping';
                }

                // Extract shipping details if shipping_option is provided
                if ($request->filled('shipping_option') && !$request->filled('shipping_option_name')) {
                    $shipping_option_input = strtolower(trim($request->input('shipping_option')));

                    // For digital products or when address is available, extract shipping details
                    if ($request->filled('address_id')) {
                        $delivery_pincode = fetchDetails(Address::class, ['id' => $request->input('address_id')], 'pincode');
                        if (!$delivery_pincode->isEmpty()) {
                            $cart_user_data = fetchDetails(Cart::class, ['user_id' => $user_id, 'store_id' => $store_id, 'is_saved_for_later' => 0]);

                            if (!$cart_user_data->isEmpty()) {
                                // Calculate cart total for free delivery logic
                                $cart_total_data = app(CartService::class)->getCartTotal($user_id, false, 0, $request->input('address_id'), $store_id);
                                $cart_total = isset($cart_total_data['sub_total']) ? floatval($cart_total_data['sub_total']) : 0;

                                $parcels = app(ShiprocketService::class)->makeShippingParcels($cart_user_data);
                                $parcels_details = app(ShiprocketService::class)->checkParcelsDeliverability($parcels, $delivery_pincode[0]->pincode, $cart_total);

                                // Extract shipping details based on shipping_option
                                if ($shipping_option_input == 'cheapest' || $shipping_option_input == 'standard') {
                                    $request->merge([
                                        'shipping_option_id' => 1,
                                        'shipping_option_name' => 'Standard Shipping',
                                        'shipping_carrier' => 'Shiprocket',
                                        'shipping_estimated_days' => $parcels_details['estimated_delivery_days'] ?? ''
                                    ]);
                                } elseif ($shipping_option_input == 'fastest' || $shipping_option_input == 'express') {
                                    $request->merge([
                                        'shipping_option_id' => 2,
                                        'shipping_option_name' => 'Express Shipping',
                                        'shipping_carrier' => 'Shiprocket',
                                        'shipping_estimated_days' => $parcels_details['estimated_delivery_days'] ?? ''
                                    ]);
                                } else {
                                    $request->merge([
                                        'shipping_option_id' => 1,
                                        'shipping_option_name' => ucwords($request->input('shipping_option')),
                                        'shipping_carrier' => 'Shiprocket',
                                        'shipping_estimated_days' => $parcels_details['estimated_delivery_days'] ?? ''
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Add shipping option data to request
                $request['shipping_option'] = [
                    'shipping_option_id' => $request->input('shipping_option_id'),
                    'shipping_option_name' => $request->input('shipping_option_name'),
                    'shipping_carrier' => $request->input('shipping_carrier'),
                    'shipping_estimated_days' => $request->input('shipping_estimated_days')
                ];
                $res = app(OrderService::class)->placeOrder($request, '', $language_code);
                // dd($res);
                if (!empty($res)) {

                    if ($request['payment_method'] == "bank_transfer" || $request['payment_method'] == "direct_bank_transfer") {
                        $data = new Request([
                            'status' => "awaiting",
                            'txn_id' => null,
                            'message' => null,
                            'order_id' => $res->original['order_id'],
                            'user_id' => $user_id,
                            'type' => $request['payment_method'],
                            'amount' => $res->original['final_total'],
                        ]);

                        $transactionController->store($data);
                    }
                }
                return response()->json($res->original);
            }
        }
    }
    public function remove_from_cart(Request $request, CartController $cartController)
    {
        /*
            product_variant_id:23
            address_id : 2 // optional
            store_id : 1,
            product_type:regular // {regular / combo}
        */
        $rules = [
            'product_variant_id' => 'required|numeric|exists:product_variants,id',
            'address_id' => 'numeric',
            'store_id' => 'required|numeric|exists:stores,id',
            'product_type' => 'required',
            'is_saved_for_later' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            $settings = app(SettingService::class)->getSettings('system_settings', true);
            $settings = json_decode($settings, true);
            $product_variant_id = request('product_variant_id');
            $address_id = request('address_id', '');
            $store_id = request('store_id') != null ? request('store_id') : '';
            $is_saved_for_later = request('is_saved_for_later') != null ? request('is_saved_for_later') : '';
            $product_type = request('product_type') != null ? Str::lower(request('product_type')) : "";
            $data = [
                'user_id' => $user_id,
                'product_variant_id' => $product_variant_id,
                'product_type' => $product_type,
                'store_id' => $store_id,
                'is_saved_for_later' => $is_saved_for_later,
            ];
            app(CartService::class)->removeFromCart($data);

            // Get cart totals for active cart items only (is_saved_for_later = 0)
            $cart_total_response = app(CartService::class)->getCartTotal($user_id, false, 0, $address_id, $store_id);
            $language_code = $request->attributes->get('language_code');
            $cart_user_data = $cartController->get_user_cart($user_id, $is_saved_for_later, '', $store_id, $language_code);
            $other_saved_for_later = ($is_saved_for_later == 1) ? 0 : 1;
            $other_cart_user_data = $cartController->get_user_cart($user_id, $other_saved_for_later, '', $store_id, $language_code);

            // Get both active and save-for-later cart data to process product details
            $active_cart_data = $cartController->get_user_cart($user_id, 0, '', $store_id, $language_code);
            $save_for_later_cart_data = $cartController->get_user_cart($user_id, 1, '', $store_id, $language_code);

            // Combine both datasets for processing
            $cart_user_data = collect($active_cart_data)->merge(collect($save_for_later_cart_data))->values();
            $product_type = collect($cart_user_data)->pluck('type')->unique()->values()->all();

            $tmpCartUserData = $cart_user_data;

            if (!empty($tmpCartUserData)) {
                $weight = 0;

                foreach ($tmpCartUserData as $index => $cartItem) {
                    $cart[$index]['product_qty'] = $cartItem->qty;
                    $cart[$index]['minimum_free_delivery_order_qty'] = $cartItem->minimum_free_delivery_order_qty;
                    $cart[$index]['product_delivery_charge'] = $cartItem->product_delivery_charge;
                    $cart[$index]['product_type'] = $cartItem->product_type;
                    $cart[$index]['type'] = $cartItem->type;

                    $weight += $cartItem->weight * $cartItem->qty;

                    if ($cartItem->cart_product_type == 'regular') {
                        $productData = Product_variants::select('product_id', 'availability')
                            ->where('id', $cartItem->product_variant_id)
                            ->first();
                    }
                    if ($cartItem->cart_product_type == 'combo') {
                        $productData = ComboProduct::select('id as product_id', 'availability')
                            ->where('id', $cartItem->product_variant_id)
                            ->first();
                    }
                    if (!empty($productData) && !empty($productData->product_id)) {

                        if ($cartItem->cart_product_type == 'regular') {
                            $proDetails = app(ProductService::class)->fetchProduct(request()->input('user_id'), null, $productData->product_id, '', 20, 0, '', '', '', '', '', '', '', '', '', $language_code);
                        } else {
                            $proDetails = app(ComboProductService::class)->fetchComboProduct(request()->input('user_id'), null, $productData->product_id, '20', '', '', '', '', '', '', $store_id, '', '', '', '', $language_code);
                        }

                        if (!empty($proDetails['product']) || !empty($proDetails['combo_product'])) {
                            if ($cartItem->cart_product_type == 'regular') {
                                // Only check availability for active cart items (saved_for_later = 0)
                                if ($cartItem->is_saved_for_later == 0 && trim($proDetails['product'][0]['availability']) == '0' && !is_null($proDetails['product'][0]['availability'])) {
                                    updateDetails(['is_saved_for_later' => '1'], ['id' => $cart_user_data[$index]->id], Cart::class);
                                    unset($cart_user_data[$index]);
                                    continue;
                                }

                                // Add product details for both active and save for later items
                                if (!empty($proDetails['product'])) {
                                    $cart_user_data[$index]->product_details = $proDetails['product'];
                                } else {
                                    deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                                    unset($cart_user_data[$index]);
                                    continue;
                                }
                            }
                            if ($cartItem->cart_product_type == 'combo') {
                                // Only check availability for active cart items (saved_for_later = 0)
                                if ($cartItem->is_saved_for_later == 0 && trim($proDetails['combo_product'][0]->availability) == '0' && !is_null($proDetails['combo_product'][0]->availability)) {
                                    updateDetails(['is_saved_for_later' => '1'], ['id' => $cart_user_data[$index]->id], Cart::class);
                                    unset($cart_user_data[$index]);
                                    continue;
                                }

                                // Add product details for both active and save for later items
                                if (!empty($proDetails['combo_product'])) {
                                    $cart_user_data[$index]->product_details = $proDetails['combo_product'];
                                } else {
                                    deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                                    unset($cart_user_data[$index]);
                                    continue;
                                }
                            }
                        } else {
                            // If no product details found, remove the item
                            deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                            unset($cart_user_data[$index]);
                            continue;
                        }
                    } else {
                        // If no product details found, remove the item
                        deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                        unset($cart_user_data[$index]);
                        continue;
                    }
                    $local_user_cart[] = $cart[$index];
                }
            }

            // Handle delivery charge calculations
            if (isset($cart_total_response['sub_total']) && !empty($cart_total_response['sub_total'])) {
                $delivery_charge_settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);
                $delivery_charge = app(DeliveryService::class)->getDeliveryCharge(request('address_id'), $cart_total_response['sub_total'], $local_user_cart, $store_id);

                if ((isset($delivery_charge_settings[0]->delivery_charge_type) && !empty($delivery_charge_settings[0]->delivery_charge_type) && $delivery_charge_settings[0]->delivery_charge_type == 'zipcode_wise_delivery_charge') || (isset($delivery_charge_settings[0]->delivery_charge_type) && !empty($delivery_charge_settings[0]->delivery_charge_type) && $delivery_charge_settings[0]->delivery_charge_type == 'city_wise_delivery_charge') || (isset($delivery_charge_settings[0]->delivery_charge_type) && !empty($delivery_charge_settings[0]->delivery_charge_type) && $delivery_charge_settings[0]->delivery_charge_type == 'global_delivery_charge')) {
                    for ($i = 0; $i < count($tmpCartUserData); $i++) {
                        $cart_user_data[$i]->product_delivery_charge = isset($delivery_charge[$index]['delivery_charge']) && !empty($delivery_charge[$index]['delivery_charge']) ? strval($delivery_charge[$index]['delivery_charge']) : "0";
                    }
                } else {
                    $delivery_charge = app(DeliveryService::class)->getDeliveryCharge(request('address_id'), $cart_total_response['sub_total'], $local_user_cart, $store_id);
                    $total_delivery_charge = 0;

                    // Loop through cart user data
                    for ($i = 0; $i < count($tmpCartUserData); $i++) {
                        // Get individual delivery charge - clean formatted string by removing commas
                        $product_delivery_charge = isset($delivery_charge[$i]['delivery_charge']) && !empty($delivery_charge[$i]['delivery_charge'])
                            ? (float) str_replace(',', '', $delivery_charge[$i]['delivery_charge'])
                            : 0;
                        $cart_user_data[$i]->product_delivery_charge = strval($product_delivery_charge);

                        // Add to total delivery charge
                        $total_delivery_charge += $cart_user_data[$i]->product_delivery_charge;

                        // Format with currency function
                        $cart_user_data[$i]->currency_product_delivery_charge_data = app(CurrencyService::class)->getPriceCurrency($cart_user_data[$i]->product_delivery_charge);
                    }

                    // Assign the total delivery charge **after** the loop
                    $delivery_charge = strval($total_delivery_charge);
                }
            }

            // Prepare variant_id array from active cart items only
            $variant_ids = [];
            if (!empty($active_cart_data)) {
                foreach ($active_cart_data as $cart_item) {
                    $variant_ids[] = $cart_item->product_variant_id;
                }
            }

            // Get promo codes
            $promo_codes = app(PromoCodeService::class)->getPromoCodes(null, null, 'id', 'DESC', null, $store_id);

            // Handle empty cart case
            if ($cart_total_response->isEmpty() || empty($cart_total_response)) {
                $sub_total = 0;
                $item_total = 0;
                $discount = 0;
                $delivery_charge = 0;
                $tax_percentage = 0;
                $tax_amount = 0;
                $overall_amount = 0;
            } else {
                $sub_total = floatval($cart_total_response['sub_total'] ?? 0);
                $item_total = floatval($cart_total_response['item_total'] ?? 0);
                $discount = floatval($cart_total_response['discount'] ?? 0);
                $delivery_charge = floatval(isset($cart_total_response['delivery_charge']) && !empty($cart_total_response['delivery_charge']) ? $cart_total_response['delivery_charge'] : 0);
                $tax_percentage = floatval($cart_total_response['tax_percentage'] ?? 0);
                $tax_amount = floatval($cart_total_response['tax_amount'] ?? 0);
                $overall_amount = floatval($cart_total_response['overall_amount'] ?? 0);
            }

            $response['error'] = false;
            $response['message'] = 'Data Retrieved From Cart !';
            $response['language_message_key'] = 'data_retrieved_from_cart';

            // Calculate total quantity from cart items
            $calculated_total_quantity = 0;
            if (!empty($cart_user_data)) {
                foreach ($cart_user_data as $cart_item) {
                    $calculated_total_quantity += intval($cart_item->qty);
                }
            }

            $response['total_quantity'] = strval($calculated_total_quantity);
            $response['sub_total'] = number_format($sub_total, 2, '.', '');
            $response['item_total'] = number_format($item_total, 2, '.', '');
            $response['discount'] = number_format($discount, 2, '.', '');
            $response['currency_sub_total_data'] = app(CurrencyService::class)->getPriceCurrency($sub_total);
            $response['delivery_charge'] = number_format($delivery_charge, 2, '.', '');
            $response['currency_delivery_charge_data'] = app(CurrencyService::class)->getPriceCurrency($delivery_charge);
            $response['tax_percentage'] = number_format($tax_percentage, 2, '.', '');
            $response['tax_amount'] = number_format($tax_amount, 2, '.', '');
            $response['currency_tax_amount_data'] = app(CurrencyService::class)->getPriceCurrency($tax_amount);
            $response['overall_amount'] = number_format($overall_amount, 2, '.', '');
            $response['currency_overall_amount_data'] = app(CurrencyService::class)->getPriceCurrency($overall_amount);
            $response['total_arr'] = number_format($overall_amount, 2, '.', '');
            $response['currency_total_arr_data'] = app(CurrencyService::class)->getPriceCurrency($overall_amount);
            $response['variant_id'] = $variant_ids;

            // Filter the processed cart_user_data based on saved_for_later parameter
            $active_cart_data = collect($cart_user_data)->filter(function ($item) {
                return $item->is_saved_for_later == 0;
            })->values();

            // Filter for save for later items from the processed data
            $save_for_later_data = collect($cart_user_data)->filter(function ($item) {
                return $item->is_saved_for_later == 1;
            })->values();

            $response['cart'] = (isset($active_cart_data) && !empty($active_cart_data)) ? $active_cart_data : [];
            $response['save_for_later_data'] = (isset($save_for_later_data) && !empty($save_for_later_data)) ? $save_for_later_data : [];
            $response['out_of_stock_data'] = [];
            $promo_list = $promo_codes['data'] ?? null;
            $response['promo_codes'] = ($promo_list && count($promo_list) > 0) ? $promo_list : null;
            $response['data'] = [
                'total_items' => (isset($cart_total_response[0]->total_items)) ? strval($cart_total_response[0]->total_items) : "0",
                'cart_count' => (isset($cart_total_response[0]->cart_count)) ? strval($cart_total_response[0]->cart_count) : "0",
                'max_items_cart' => $settings['maximum_item_allowed_in_cart'],
            ];

            return response()->json($response);
        }
    }

    public function manage_cart(Request $request, CartController $cartController)
    {
        /*
            Add/Update
            store_id:1
            product_variant_id:23
            is_saved_for_later: 1 { default:0 }
            qty:2 // pass 0 to remove qty
            address_id : 2 // optional
            product_type:regular // {regular / combo}
        */
        $rules = [
            'product_variant_id' => 'required|numeric',
            'address_id' => 'nullable',
            'qty' => 'required|numeric',
            'is_saved_for_later' => 'numeric',
            'store_id' => 'required|numeric|exists:stores,id',
            'product_type' => 'required|in:regular,combo',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }


            $product_variant_id = request('product_variant_id') != null ? request('product_variant_id') : "";
            $qty = request('qty') != null ? request('qty') : "";
            $address_id = request('address_id') != null ? request('address_id') : "";
            $store_id = request('store_id') != null ? request('store_id') : "";
            $product_type = request('product_type') != null ? Str::lower(request('product_type')) : "";
            $saved_for_later = request('is_saved_for_later') != null ? request('is_saved_for_later') : "";
            $settings = app(SettingService::class)->getSettings('system_settings', true);
            $settings = json_decode($settings, true);
            $language_code = $request->attributes->get('language_code');
            $weight = 0;

            if ($product_type == 'regular') {
                $variant = Product_variants::where('id', $product_variant_id)
                    ->where('status', 1)
                    ->whereHas('product', function ($q) use ($store_id) {
                        $q->where('status', 1)->where('store_id', $store_id);
                    })->first();

                if (!$variant) {
                    $response = [
                        'error' => true,
                        'message' => 'Product Variant not available or does not belong to the selected store.',
                        'language_message_key' => 'product_variant_not_available',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
            } else {
                $combo = ComboProduct::where('id', $product_variant_id)
                    ->where('status', 1)
                    ->where('store_id', $store_id)
                    ->first();

                if (!$combo) {
                    $response = [
                        'error' => true,
                        'message' => 'Combo Product not available or does not belong to the selected store.',
                        'language_message_key' => 'product_not_available',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
            }

            $clear_cart = ($request->filled('clear_cart')) ? request('clear_cart') : 0;

            if ($clear_cart == true) {
                if (!app(CartService::class)->removeFromCart(['user_id' => $user_id])) {
                    $response = [
                        'error' => true,
                        'message' => 'Not able to remove existing seller items please try agian later.',
                        'language_message_key' => 'unable_to_remove_existing_seller_items_try_again_later',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
            }
            $store_details = fetchDetails(Store::class, ['id' => $store_id], '*');

            $is_single_seller_order_system = !$store_details->isEmpty() ? $store_details[0]->is_single_seller_order_system : "";


            // if ($settings['single_seller_order_system'] == 1 || $is_single_seller_order_system == 1) {
            //     if (!app(CartService::class)->isSingleSeller($product_variant_id, $user_id, $product_type, $store_id)) {
            //         $response = [
            //             'error' => true,
            //             'message' => 'Only single seller items are allow in cart.You can remove privious item(s) and add this item.',
            //             'language_message_key' => 'single_seller_item_only_allowed_in_cart',
            //             'data' => [],
            //         ];
            //         return response()->json($response);
            //     }
            // }

            //check for digital or phisical product in cart
            if (!app(CartService::class)->isSingleProductType($product_variant_id, $user_id, $product_type)) {
                $response = [
                    'error' => true,
                    'message' => 'you can only add either digital product or physical product to cart',
                    'language_message_key' => 'only_digital_or_physical_product_allowed_in_cart',
                    'data' => [],
                ];
                return response()->json($response);
            }

            $local_user_cart = [];

            $settings = app(SettingService::class)->getSettings('system_settings', true);
            $settings = json_decode($settings, true);
            $check_status = ($qty == 0 || $saved_for_later == 1) ? false : true;
            $cart_count = app(CartService::class)->getCartCount($user_id, $store_id);

            $is_variant_available_in_cart = app(CartService::class)->isVariantAvailableInCart($product_variant_id, $user_id);
            if (!$is_variant_available_in_cart) {
                if ($cart_count >= $settings['maximum_item_allowed_in_cart']) {
                    $response = [
                        'error' => true,
                        'message' => 'Maximum ' . $settings['maximum_item_allowed_in_cart'] . ' Item(s) Can Be Added Only!',
                        'language_message_key' => 'max_cart_limit_warning',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
            }
            $request["user_id"] = $user_id;
            // dd($request->toArray());
            $add_to_cart = app(CartService::class)->addToCart($request->toArray(), $check_status, true);
            // dd($add_to_cart);
            // dd($add_to_cart['message']);
            if (isset($add_to_cart['error']) && $add_to_cart['error'] == true) {
                $response['error'] = true;
                $response['message'] = $add_to_cart['message'];
                $response['language_message_key'] = 'product_is_out_of_stock';
                return response()->json($response);
            }
            if (app(CartService::class)->addToCart($request->toArray(), $check_status, true)) {

                // Get cart totals for active cart items only (is_saved_for_later = 0)
                $res = app(CartService::class)->getCartTotal($user_id, false, 0, $address_id, $store_id);

                // Get both active and save-for-later cart data to process product details
                $active_cart_data = $cartController->get_user_cart($user_id, 0, '', $store_id, $language_code);
                $save_for_later_cart_data = $cartController->get_user_cart($user_id, 1, '', $store_id, $language_code);

                // Combine both datasets for processing
                $cart_user_data = collect($active_cart_data)->merge(collect($save_for_later_cart_data))->values();
                $product_type = collect($cart_user_data)->pluck('type')->unique()->values()->all();

                $tmpCartUserData = $cart_user_data;

                // Debug: Log initial cart data
                Log::info('Initial cart data count: ' . count($tmpCartUserData));

                if (!empty($tmpCartUserData)) {
                    $weight = 0;

                    foreach ($tmpCartUserData as $index => $cartItem) {
                        Log::info('Processing cart item index: ' . $index . ', product_type: ' . $cartItem->cart_product_type . ', availability check: ' . ($cartItem->is_saved_for_later == 0 ? 'active' : 'saved_for_later'));

                        $cart[$index]['product_qty'] = $cartItem->qty;
                        $cart[$index]['minimum_free_delivery_order_qty'] = $cartItem->minimum_free_delivery_order_qty;
                        $cart[$index]['product_delivery_charge'] = $cartItem->product_delivery_charge;
                        $cart[$index]['product_type'] = $cartItem->product_type;
                        $cart[$index]['type'] = $cartItem->type;

                        $weight += $cartItem->weight * $cartItem->qty;

                        if ($cartItem->cart_product_type == 'regular') {
                            $productData = Product_variants::select('product_id', 'availability')
                                ->where('id', $cartItem->product_variant_id)
                                ->first();
                        }
                        if ($cartItem->cart_product_type == 'combo') {
                            $productData = ComboProduct::select('id as product_id', 'availability')
                                ->where('id', $cartItem->product_variant_id)
                                ->first();
                        }
                        if (!empty($productData) && !empty($productData->product_id)) {

                            if ($cartItem->cart_product_type == 'regular') {
                                $proDetails = app(ProductService::class)->fetchProduct(request()->input('user_id'), null, $productData->product_id, '', 20, 0, '', '', '', '', '', '', '', '', '', '', $language_code);
                            } else {
                                $proDetails = app(ComboProductService::class)->fetchComboProduct(request()->input('user_id'), null, $productData->product_id, '20', '', '', '', '', '', '', $store_id, '', '', '', '', $language_code);
                            }

                            if (!empty($proDetails['product']) || !empty($proDetails['combo_product'])) {
                                if ($cartItem->cart_product_type == 'regular') {
                                    // Only check availability for active cart items (saved_for_later = 0)
                                    if ($cartItem->is_saved_for_later == 0 && trim($proDetails['product'][0]['availability']) == '0' && !is_null($proDetails['product'][0]['availability'])) {
                                        updateDetails(['is_saved_for_later' => '1'], ['id' => $cart_user_data[$index]->id], Cart::class);
                                        unset($cart_user_data[$index]);
                                        continue;
                                    }

                                    // Add product details for both active and save for later items
                                    if (!empty($proDetails['product'])) {
                                        $cart_user_data[$index]->product_details = $proDetails['product'];
                                    } else {
                                        deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                                        unset($cart_user_data[$index]);
                                        continue;
                                    }
                                }
                                if ($cartItem->cart_product_type == 'combo') {
                                    // Only check availability for active cart items (saved_for_later = 0)
                                    if ($cartItem->is_saved_for_later == 0 && trim($proDetails['combo_product'][0]->availability) == '0' && !is_null($proDetails['combo_product'][0]->availability)) {
                                        updateDetails(['is_saved_for_later' => '1'], ['id' => $cart_user_data[$index]->id], Cart::class);
                                        unset($cart_user_data[$index]);
                                        continue;
                                    }

                                    // Add product details for both active and save for later items
                                    if (!empty($proDetails['combo_product'])) {
                                        $cart_user_data[$index]->product_details = $proDetails['combo_product'];
                                    } else {
                                        deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                                        unset($cart_user_data[$index]);
                                        continue;
                                    }
                                }
                            } else {
                                // If no product details found, remove the item
                                deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                                unset($cart_user_data[$index]);
                                continue;
                            }
                        } else {
                            deleteDetails(['id' => $cart_user_data[$index]->id], Cart::class);
                            unset($cart_user_data[$index]);
                            continue;
                        }
                        $local_user_cart[] = $cart[$index];
                    }
                    Log::info('Final cart data count after processing: ' . count($cart_user_data));
                }
                // dd($local_user_cart);

                // dd($res['sub_total']);
                if (isset($res['sub_total']) && !empty($res['sub_total'])) {
                    $delivery_charge_settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);
                    // dd($delivery_charge_settings);
                    // dd($local_user_cart);
                    $delivery_charge = app(DeliveryService::class)->getDeliveryCharge(request('address_id'), $res['sub_total'], $local_user_cart, $store_id);
                    //    dd($delivery_charge);
                    if ((isset($delivery_charge_settings[0]->delivery_charge_type) && !empty($delivery_charge_settings[0]->delivery_charge_type) && $delivery_charge_settings[0]->delivery_charge_type == 'zipcode_wise_delivery_charge') || (isset($delivery_charge_settings[0]->delivery_charge_type) && !empty($delivery_charge_settings[0]->delivery_charge_type) && $delivery_charge_settings[0]->delivery_charge_type == 'city_wise_delivery_charge') || (isset($delivery_charge_settings[0]->delivery_charge_type) && !empty($delivery_charge_settings[0]->delivery_charge_type) && $delivery_charge_settings[0]->delivery_charge_type == 'global_delivery_charge')) {
                        // dd('here');
                        for ($i = 0; $i < count($tmpCartUserData); $i++) {
                            $cart_user_data[$i]->product_delivery_charge = isset($delivery_charge[$index]['delivery_charge']) && !empty($delivery_charge[$index]['delivery_charge']) ? strval($delivery_charge[$index]['delivery_charge']) : "0";
                        }
                    } else {
                        $delivery_charge = app(DeliveryService::class)->getDeliveryCharge(request('address_id'), $res['sub_total'], $local_user_cart, $store_id);
                        $total_delivery_charge = 0;

                        // Loop through cart user data
                        for ($i = 0; $i < count($tmpCartUserData); $i++) {
                            // Get individual delivery charge - clean formatted string by removing commas
                            $product_delivery_charge = isset($delivery_charge[$i]['delivery_charge']) && !empty($delivery_charge[$i]['delivery_charge'])
                                ? (float) str_replace(',', '', $delivery_charge[$i]['delivery_charge'])
                                : 0;
                            $cart_user_data[$i]->product_delivery_charge = strval($product_delivery_charge);

                            // Add to total delivery charge
                            $total_delivery_charge += $cart_user_data[$i]->product_delivery_charge;

                            // Format with currency function
                            $cart_user_data[$i]->currency_product_delivery_charge_data = app(CurrencyService::class)->getPriceCurrency($cart_user_data[$i]->product_delivery_charge);
                        }

                        // Assign the total delivery charge **after** the loop
                        $delivery_charge = strval($total_delivery_charge);
                    }
                }
                // dd($res['delivery_charge']);
                // Prepare variant_id array from active cart items only
                $variant_ids = [];
                if (!empty($active_cart_data)) {
                    foreach ($active_cart_data as $cart_item) {
                        $variant_ids[] = $cart_item->product_variant_id;
                    }
                }

                // Get promo codes
                $promo_codes = app(PromoCodeService::class)->getPromoCodes(null, null, 'id', 'DESC', null, $store_id);

                $response['error'] = false;
                $response['message'] = 'Data Retrieved From Cart !';
                $response['language_message_key'] = 'data_retrieved_from_cart';
                // Calculate total quantity from cart items
                $calculated_total_quantity = 0;
                if (!empty($cart_user_data)) {
                    foreach ($cart_user_data as $cart_item) {
                        $calculated_total_quantity += intval($cart_item->qty);
                    }
                }

                $response['total_quantity'] = strval($calculated_total_quantity);

                // Ensure consistent monetary values
                $sub_total = floatval($res['sub_total'] ?? 0);
                $item_total = floatval($res['item_total'] ?? 0);
                $discount = floatval($res['discount'] ?? 0);
                $delivery_charge = floatval(!empty($delivery_charge) ? $delivery_charge : $res['delivery_charge'] ?? 0);
                $tax_percentage = floatval($res['tax_percentage'] ?? 0);
                $tax_amount = floatval($res['tax_amount'] ?? 0);
                $overall_amount = floatval($res['overall_amount'] ?? 0);

                $response['sub_total'] = number_format($sub_total, 2, '.', '');
                $response['item_total'] = number_format($item_total, 2, '.', '');
                $response['discount'] = number_format($discount, 2, '.', '');
                $response['currency_sub_total_data'] = app(CurrencyService::class)->getPriceCurrency($sub_total);
                $response['delivery_charge'] = number_format($delivery_charge, 2, '.', '');
                $response['currency_delivery_charge_data'] = app(CurrencyService::class)->getPriceCurrency($delivery_charge);
                $response['tax_percentage'] = number_format($tax_percentage, 2, '.', '');
                $response['tax_amount'] = number_format($tax_amount, 2, '.', '');
                $response['currency_tax_amount_data'] = app(CurrencyService::class)->getPriceCurrency($tax_amount);
                $response['overall_amount'] = number_format($overall_amount, 2, '.', '');
                $response['currency_overall_amount_data'] = app(CurrencyService::class)->getPriceCurrency($overall_amount);
                $response['total_arr'] = number_format($overall_amount, 2, '.', '');
                $response['currency_total_arr_data'] = app(CurrencyService::class)->getPriceCurrency($overall_amount);
                $response['variant_id'] = $variant_ids;

                // Use the cart_user_data that already contains product_details
                // Filter the processed cart_user_data based on saved_for_later parameter

                // If we're dealing with active cart, filter for active items
                $active_cart_data = collect($cart_user_data)->filter(function ($item) {
                    return $item->is_saved_for_later == 0;
                })->values();

                // Filter for save for later items from the processed data
                $save_for_later_data = collect($cart_user_data)->filter(function ($item) {
                    return $item->is_saved_for_later == 1;
                })->values();

                $response['cart'] = (isset($active_cart_data) && !empty($active_cart_data)) ? $active_cart_data : [];
                $response['save_for_later_data'] = (isset($save_for_later_data) && !empty($save_for_later_data)) ? $save_for_later_data : [];

                Log::info('Final response cart count: ' . count($response['cart'] ?? []));
                $response['out_of_stock_data'] = [];
                $promo_list = $promo_codes['data'] ?? null;
                $response['promo_codes'] = ($promo_list && count($promo_list) > 0) ? $promo_list : null;
                $response['data'] = [
                    'total_items' => (isset($res[0]->total_items)) ? strval($res[0]->total_items) : "0",
                    'cart_count' => (isset($res[0]->cart_count)) ? strval($res[0]->cart_count) : "0",
                    'max_items_cart' => $settings['maximum_item_allowed_in_cart'],
                ];
                return response()->json($response);
            }
        }
    }




    public function clear_cart()
    {
        if (auth()->check()) {
            $user_id = auth()->user()->id;
        } else {
            $response = [
                'error' => true,
                'message' => 'Please Login first.',
                'language_message_key' => 'please_login_first',
                'code' => 102,
            ];
            return response()->json($response);
        }
        deleteDetails(['user_id' => $user_id, 'is_saved_for_later' => 0], Cart::class);
        $response = [
            'error' => false,
            'message' => 'Data removed successfully',
            'language_message_key' => 'data_removed_successfully',
        ];
        return response()->json($response);
    }

    public function get_orders(Request $request)
    {
        /*
            offset:0
            active_status: received  {received,delivered,cancelled,processed,returned}     // optional
            limit:25           // { default - 0 } optional
            sort: id / date_added // { default - id } optional
            order:DESC/ASC      // { default - DESC } optional
            download_invoice:0 // { default - 0 } optional
        */
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'download_invoice' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            $limit = request('limit', 25);
            $order_id = request('id') != null ? request('id') : "";
            $offset = request('offset', 0);
            $sort = request('sort', 'o.id');
            $order = request('order', 'DESC');
            $search = request('search', '');
            $start_date = request('start_date', '');
            $end_date = request('end_date', '');
            $download_invoice = request('download_invoice', 1);
            $store_id = request('store_id') != null ? request('store_id') : "";
            $multiple_status = request()->has('active_status') ? explode(',', request('active_status')) : '';
            $store_id = request('store_id') != null ? request('store_id') : "";
            $language_code = $request->attributes->get('language_code');
            // dd($order_id);
            $order_details = app(OrderService::class)->fetchOrders($order_id, $user_id, $multiple_status, NULL, $limit, $offset, $sort, $order, $download_invoice, $start_date, $end_date, $search, NULL, NULL, NULL, '', true, $store_id, $language_code);


            if (!$order_details['order_data']->isEmpty()) {
                $response = [
                    'error' => false,
                    'message' => 'Data retrieved successfully',
                    'language_message_key' => 'data_retrieved_successfully',
                    'total' => $order_details['total'],
                    'data' => $order_details['order_data'],
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => 'No Order(s) Found !',
                    'language_message_key' => 'no_orders_found',
                    'data' => [],
                ];
            }
            return response()->json($response);
        }
    }

    public function update_order_item_status(Request $request)
    {
        /*
            status: cancelled / returned
            order_item_id:1201
        */

        $rules = [
            'status' => 'required',
            'order_item_id' => 'required|numeric|exists:order_items,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $status = request('status', 25);
            $order_item_id = request('order_item_id', 0);

            $order_item_data = fetchDetails(OrderItems::class, ['id' => $order_item_id], 'order_id');
            if ($order_item_data->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Order item not found',
                    'language_message_key' => 'order_item_not_found',
                    'data' => []
                ]);
            }
            $order_method = fetchDetails(Order::class, ['id' => $order_item_data[0]->order_id], 'payment_method');
            if ($order_method->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Order not found',
                    'language_message_key' => 'order_not_found',
                    'data' => []
                ]);
            }

            if ($order_method[0]->payment_method == 'bank_transfer') {
                $bank_receipt = fetchDetails(OrderBankTransfers::class, ['order_id' => $order_item_data[0]->order_id]);
                $transaction_status = fetchDetails(Transaction::class, ['order_id' => $order_item_data[0]->order_id], 'status');
                if ($status != "cancelled" && (empty($bank_receipt) || (!empty($transaction_status) && !$transaction_status->isEmpty() && strtolower($transaction_status[0]->status) != 'success'))) {
                    $response = [
                        'error' => true,
                        'message' => 'Order Status can not update, Bank verification is remain from transactions.',
                        'language_message_key' => 'order_status_cannot_update_bank_verification_remaining',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
            }

            if ($status == 'returned') {
                $response = app(OrderService::class)->update_order_item($order_item_id, $status, 0, true);
                $order_id = fetchDetails(OrderItems::class, ['id' => $order_item_id], 'order_id');
                $order_id = isset($order_id) && !empty($order_id) ? $order_id[0]->order_id : "";
                $order_details = app(OrderService::class)->fetchOrders($order_id);
                $response['data'] = $order_details['order_data'];
            } else {
                $response = app(OrderService::class)->update_order_item($order_item_id, $status, '', true);
            }
            if ($status != 'returned' && $response['error'] == false) {
                app(OrderService::class)->process_refund($order_item_id, $status, 'order_items');
            }

            if ($status == 'cancelled') {
                $data = fetchDetails(OrderItems::class, ['id' => $order_item_id], ['product_variant_id', 'quantity', 'order_type']);
                $order_id = fetchDetails(OrderItems::class, ['id' => $order_item_id], 'order_id');
                $order_id = !$order_id->isEmpty() ? $order_id[0]->order_id : "";
                $order_details = app(OrderService::class)->fetchOrders($order_id);
                $response['data'] = $order_details['order_data'];

                if ($data[0]->order_type == 'regular_order') {
                    app(ProductService::class)->updateStock($data[0]->product_variant_id, $data[0]->quantity, 'plus');
                }
                if ($data[0]->order_type == 'combo_order') {
                    app(ComboProductService::class)->updateComboStock($data[0]->product_variant_id, $data[0]->quantity, 'plus');
                }
            }
        }
        return response()->json($response);
    }


    public function get_faqs(Request $request, FaqController $faqController)
    {
        /*
            limit:25                // { default - 25 } optional
            offset:0                // { default - 0 } optional
            sort: id   			    // { default - id } optional
            order:DESC/ASC          // { default - DESC } optional
        */

        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order = $request->filled('order') ? $request->input('order') : 'DESC';
            $sort = $request->filled('sort') ? $request->input('sort') : 'id';
            $res = $faqController->getFaqs($offset, $limit, $sort, $order);

            $response = [
                'error' => $res['data']->isEmpty() ? true : false,
                'message' => $res['data']->isEmpty() ? 'FAQ(s) Not Found' : 'FAQ(s) Retrieved Successfully',
                'language_message_key' => $res['data']->isEmpty() ? 'faqs_not_found' : 'faqs_retrieved_successfully',
                'total' => $res['total'],
                'data' => $res['data'],
            ];
            return response()->json($response);
        }
    }

    public function get_offer_images(Request $request, CategoryController $categoryController)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $store_id = request('store_id') != null ? request('store_id') : "";

            $res = fetchDetails(Offer::class, ['store_id' => $store_id], '*');
            $language_code = $request->attributes->get('language_code');
            $i = 0;
            foreach ($res as $row) {

                $res[$i]->image = app(MediaService::class)->getImageUrl($res[$i]->image);
                $res[$i]->title = app(TranslationService::class)->getDynamicTranslation(Offer::class, 'title', $res[$i]->id, $language_code);
                $res[$i]->banner_image = app(MediaService::class)->getImageUrl($res[$i]->banner_image);
                if ($res[$i]->link == null || empty($res[$i]->link)) {
                    $res[$i]->link = '';
                }
                if (strtolower($res[$i]->type) == 'categories') {
                    $id = (!empty($res[$i]->type_id) && isset($res[$i]->type_id)) ? $res[$i]->type_id : '';
                    $cat_res = $categoryController->getCategories($id, 10, 0, 'row_order', 'ASC', '', '', '', '', '', $language_code);
                    $res[$i]->data = $cat_res;
                } else if (strtolower($res[$i]->type) == 'products') {
                    $id = (!empty($res[$i]->type_id) && isset($res[$i]->type_id)) ? $res[$i]->type_id : '';
                    $pro_res = app(ProductService::class)->fetchProduct(NULL, NULL, $id, '', "20", "0", '', '', '', '', '', '', '', '', '', '', $language_code);
                    $res[$i]->data = $pro_res['product'];
                } else if (strtolower($res[$i]->type) == 'combo_products') {
                    $id = (!empty($res[$i]->type_id) && isset($res[$i]->type_id)) ? $res[$i]->type_id : '';
                    $pro_res = app(ComboProductService::class)->fetchComboProduct(NULL, NULL, $id, '20', '0', '', '', '', '', '', $store_id, '', '', '', '', $language_code);
                    $res[$i]->data = $pro_res['combo_product'];
                } else {
                    $res[$i]->data = [];
                }

                $i++;
            }
            $response = [
                'error' => empty($res) ? true : false,
                'message' => empty($res) ? 'Offers Not Found' : 'Offers Retrieved Successfully',
                'language_message_key' => empty($res) ? 'offers_not_found' : 'offers_retrieved_successfully',
                'data' => $res,
            ];
            return response()->json($response);
        }
    }

    public function get_ticket_types()
    {
        $types = TicketType::all();

        if ($types->isEmpty()) {
            return response()->json([
                'error' => true,
                'message' => 'No ticket types found',
                'language_message_key' => 'no_ticket_types_found',
                'data' => []
            ]);
        }

        return response()->json([
            'error' => false,
            'message' => 'Ticket types fetched successfully',
            'language_message_key' => 'ticket_types_fetched_successfully',
            'data' => $types
        ]);
    }

    public function add_ticket(Request $request)
    {
        /*
            ticket_type_id:1
            subject:product_image not displaying
            email:test@gmail.com
            description:its not showing images of products in web
        */
        $rules = [
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'subject' => 'required',
            'email' => 'required|email',
            'description' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }

            $ticket_type_id = $request->ticket_type_id;
            $subject = $request->subject;
            $email = $request->email;
            $description = $request->description;

            $user = User::find($user_id);

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'User not found!',
                    'language_message_key' => 'user_does_not_exist',
                    'data' => [],
                ]);
            }

            // Create a new ticket
            $ticket = new Ticket();
            $ticket->ticket_type_id = $ticket_type_id;
            $ticket->user_id = $user_id;
            $ticket->subject = $subject;
            $ticket->email = $email;
            $ticket->description = $description;
            $ticket->status = 1;
            $ticket->save();

            $result = Ticket::find($ticket->id);
            $ticket_type = fetchDetails(TicketType::class, ['id' => $ticket->ticket_type_id], 'title');
            $ticket_type = isset($ticket_type) && !empty($ticket_type) ? $ticket_type[0]->title : '';
            $result->ticket_type = $ticket_type;

            if ($result) {
                return response()->json([
                    'error' => false,
                    'message' => 'Ticket Added Successfully',
                    'language_message_key' => 'ticket_added_successfully',
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Ticket Not Added',
                    'language_message_key' => 'ticket_not_added',
                    'data' => [],
                ]);
            }
        }
    }


    public function edit_ticket(Request $request)
    {
        /*
            ticket_id:1
            ticket_type_id:1
            subject:product_image not displying
            email:test@gmail.com
            description:its not showing attachments of products in web
            status:3 or 5 [3 -> resolved, 5 -> reopened]
            [1 -> pending, 2 -> opened, 3 -> resolved, 4 -> closed, 5 -> reopened]
        */

        $rules = [
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'ticket_id' => 'required|exists:tickets,id',
            'subject' => 'required',
            'email' => 'required|email',
            'description' => 'required',
            'status' => 'nullable',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            $ticket_type_id = request('ticket_type_id');
            $ticket_id = request('ticket_id');
            $subject = request('subject');
            $email = request('email');
            $description = request('description');
            $status = request('status');

            $user = User::find($user_id);

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'User not found!',
                    'language_message_key' => 'user_does_not_exist',
                    'data' => [],
                ]);
            }

            // Check if the ticket exists
            $ticket = Ticket::where('id', $ticket_id)
                ->where('user_id', $user_id)
                ->first();

            if (!$ticket) {
                return response()->json([
                    'error' => true,
                    'message' => "User id is changed, you cannot update the ticket.",
                    'language_message_key' => 'user_id_changed_cannot_update_ticket',
                    'data' => [],
                ]);
            }

            if ($status == config('constants.RESOLVED') && $ticket->status == config('constants.CLOSED')) {
                return response()->json([
                    'error' => true,
                    'message' => "Current status is closed.",
                    'language_message_key' => 'current_status_is_closed',
                    'data' => [],
                ]);
            }

            if ($status == 'REOPEN' && ($ticket->status == config('constants.PENDING') || $ticket->status == config('constants.OPENED'))) {
                return response()->json([
                    'error' => true,
                    'message' => "Current status is pending or opened.",
                    'language_message_key' => 'current_status_is_pending_or_opened',
                    'data' => [],
                ]);
            }

            // Update the ticket
            $ticket->ticket_type_id = $ticket_type_id;
            $ticket->subject = $subject;
            $ticket->email = $email;
            $ticket->description = $description;
            $ticket->status = $status ?? "1";

            // If ticket is reopened, require admin to reply first before customer can chat
            if ($status == config('constants.REOPEN') || $status == 'REOPEN') {
                $ticket->awaiting_admin_reply = true;
            } elseif ($status == config('constants.OPENED') || $status == '2') {
                // If admin sets status to OPENED, enable chat (even without sending a message)
                $ticket->awaiting_admin_reply = false;
            }

            $ticket->save();

            // Retrieve the updated ticket
            $result = Ticket::find($ticket_id);

            $ticket_type = fetchDetails(TicketType::class, ['id' => $ticket->ticket_type_id], 'title');
            $ticket_type = !$ticket_type->isEmpty() ? $ticket_type[0]->title : '';
            $result->ticket_type = $ticket_type;

            if ($result) {
                return response()->json([
                    'error' => false,
                    'message' => 'Ticket updated successfully',
                    'language_message_key' => 'ticket_updated_successfully',
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Ticket not updated',
                    'language_message_key' => 'ticket_not_updated',
                    'data' => [],
                ]);
            }
        }
    }
    public function get_tickets(Request $request, TicketController $ticketController)
    {
        /*
        ticket_id: 1001
        ticket_type_id: 1001
        status: [1 -> pending, 2 -> opened, 3 -> resolved, 4 -> closed, 5 -> reopened]
        search : Search keyword
        limit:25
        offset:0
        sort: id | date_created | last_updated
        order:DESC/ASC
    */

        $rules = [
            'ticket_id' => 'numeric',
            'ticket_type_id' => 'numeric',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (!auth()->check()) {
            return response()->json([
                'error' => true,
                'message' => 'Please login first.',
                'language_message_key' => 'please_login_first',
                'code' => 102,
                'total' => 0,
                'data' => collect(),
            ]);
        }

        $user_id = auth()->id();
        $ticket_type_id = $request->input('ticket_type_id', '');
        $ticket_id = $request->input('ticket_id', '');
        $status = $request->input('status', '');
        $search = $request->input('search', '');
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'id');

        $result = $ticketController->getTickets(
            $ticket_id,
            $ticket_type_id,
            $user_id,
            $status,
            $search,
            $offset,
            $limit,
            $sort,
            $order
        );

        if (!empty($result) && isset($result['data']) && count($result['data']) > 0) {
            return response()->json([
                'error' => false,
                'message' => 'Tickets fetched successfully.',
                'language_message_key' => 'tickets_fetched_successfully',
                'total' => $result['total'] ?? count($result['data']),
                'data' => $result['data'],
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Ticket(s) does not exist.',
            'language_message_key' => 'ticket_does_not_exist',
            'total' => 0,
            'data' => collect(),
        ]);
    }



    public function get_messages(Request $request, TicketController $ticketController)
    {
        /*
            ticket_id: 1001
            user_type: 1001                // { optional}
            search : Search keyword // { optional }
            limit:25                // { default - 25 } optional
            offset:0                // { default - 0 } optional
            sort: id | date_created | last_updated                // { default - id } optional
            order:DESC/ASC          // { default - DESC } optional
        */

        $rules = [
            'ticket_id' => 'required|numeric|exists:tickets,id',
            'status' => 'numeric',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'language_message_key' => 'please_login_first',
                    'code' => 102,
                ];
                return response()->json($response);
            }
            $ticket_id = $request->input('ticket_id', null);
            $search = $request->input('search', null);
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order = $request->input('order', 'DESC');
            $sort = $request->input('sort', 'id');
            $data = config('eshop_pro.type');

            $response = $ticketController->getMessages($ticket_id, $user_id, $search, $offset, $limit, $sort, $order, $data, "");
            return response()->json($response);
        }
    }

    public function is_product_delivarable(Request $request)
    {
        /*
            product_id:10
            product_type:regular // {regular / combo}
            zipcode:132456 {{optional based on type of delivery}}
            city : Ahmedabad {{optional based on type of delivery}}
        */

        $rules = [
            'product_id' => 'required|numeric|exists:products,id',
            'product_type' => 'required'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $zipcode = request('zipcode');
            $city = request('city');

            // Handle JSON city name
            if (!empty($city) && str_starts_with($city, '{')) {
                $city_json = json_decode($city, true);
                $city = $city_json['en'] ?? $city;
            }

            $product_id = request('product_id');
            $product_type = request('product_type') != null ? Str::lower(request('product_type')) : "";
            $isPincode = Zipcode::where('zipcode', $zipcode)->exists();
            $isCity = City::where('name->en', $city)
                ->exists();

            if ($isPincode) {
                $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
                $is_available = app(DeliveryService::class)->isProductDelivarable('zipcode', $zipcode_id[0]->id, $product_id, $product_type);
                if ($is_available) {
                    $response['error'] = false;
                    $response['message'] = 'Product is deliverable on ' . $zipcode . '.';
                    return response()->json($response);
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Product is not deliverable on ' . $zipcode . '.';
                    return response()->json($response);
                }
            } else if ($isCity) {
                $city_id = fetchDetails(City::class, ['name->en' => $city], 'id');
                $is_available = app(DeliveryService::class)->isProductDelivarable('city', $city_id[0]->id, $product_id, $product_type);
                // $is_available = isProductDelivarableOld('city', $city_id[0]->id, $product_id, $product_type);

                if ($is_available) {
                    $response['error'] = false;
                    $response['message'] = 'Product is deliverable in ' . $city . '.';
                    return response()->json($response);
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Product is not deliverable in ' . $city . '.';
                    return response()->json($response);
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Cannot deliver to ' . (isset($zipcode) ? $zipcode : $city) . '.';
                return response()->json($response);
            }
        }
    }
    public function is_seller_delivarable(Request $request)
    {
        /*
            seller_id:10
            store_id:10
            zipcode:132456 {{optional based on type of delivery}}
            city : Ahmedabad {{optional based on type of delivery}}
        */

        $rules = [
            'seller_id' => 'required|numeric',
            'store_id' => 'required|numeric'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $zipcode = request('zipcode');
            $city = request('city');

            // Handle JSON city name
            if (!empty($city) && str_starts_with($city, '{')) {
                $city_json = json_decode($city, true);
                $city = $city_json['en'] ?? $city;
            }

            $seller_id = request('seller_id') ?? "";
            $store_id = request('store_id') ?? "";
            $isPincode = Zipcode::where('zipcode', $zipcode)->exists();
            $isCity = City::where('name->en', $city)
                ->exists();

            if ($isPincode) {
                $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
                $is_available = app(DeliveryService::class)->isSellerDeliverable('zipcode', $zipcode_id[0]->id, $seller_id, $store_id);
                if ($is_available) {
                    $response['error'] = false;
                    $response['message'] = 'Product is deliverable on ' . $zipcode . '.';
                    return response()->json($response);
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Product is not deliverable on ' . $zipcode . '.';
                    return response()->json($response);
                }
            } else if ($isCity) {
                $city_id = fetchDetails(City::class, ['name->en' => $city], 'id');
                $is_available = app(DeliveryService::class)->isSellerDeliverable('city', $city_id[0]->id, $seller_id, $store_id);
                if ($is_available) {
                    $response['error'] = false;
                    $response['message'] = 'Product is deliverable in ' . $city . '.';
                    return response()->json($response);
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Product is not deliverable in ' . $city . '.';
                    return response()->json($response);
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Cannot deliver to ' . (isset($zipcode) ? $zipcode : $city) . '.';
                return response()->json($response);
            }
        }
    }
    public function check_cart_products_delivarable(Request $request)
    {
        $rules = [
            'address_id' => 'required|numeric|exists:addresses,id',
            'store_id'   => 'required|numeric|exists:stores,id',
            'promo_code' => 'nullable|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (!auth()->check()) {
            return response()->json([
                'error' => true,
                'message' => 'Please Login first.',
                'language_message_key' => 'please_login_first',
                'code' => 102,
            ]);
        }

        $user_id = auth()->id();


        $store_id = $request->store_id;
        $address_id = $request->address_id;
        $promo_code = $request->input('promo_code', '');
        $language_code = $request->attributes->get('language_code');

        /** ---------------- ADDRESS ---------------- */
        $address = fetchDetails(Address::class, ['id' => $address_id], ['pincode', 'city', 'city_id']);
        if ($address->isEmpty()) {
            return response()->json([
                'error' => true,
                'message' => 'Address not available.',
                'language_message_key' => 'address_not_available',
                'data' => []
            ]);
        }

        $zipcode = $address[0]->pincode;
        $city = $address[0]->city;
        $address_city_id = $address[0]->city_id;

        // Handle JSON city name
        if (!empty($city) && str_starts_with($city, '{')) {
            $city_json = json_decode($city, true);
            $city = $city_json['en'] ?? $city;
        }

        /** ---------------- CART SUBTOTAL (ONCE) ---------------- */
        $cart_data = app(\App\Services\CartService::class)
            ->getCartTotal($user_id, false, 0, '', $store_id);

        $cart_subtotal = (float) ($cart_data['sub_total'] ?? 0);

        /** ---------------- PRODUCT DELIVERABILITY ---------------- */
        $settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);
        $product_delivarable = [];

        if (!empty($settings[0]->product_deliverability_type)) {
            if ($settings[0]->product_deliverability_type === 'city_wise_deliverability') {
                // Get city_id from database
                $city_id = $address_city_id ?? '';
                if (empty($city_id) && !empty($city)) {
                    $city_data = fetchDetails(City::class, ['name->en' => $city], 'id');
                    $city_id = !$city_data->isEmpty() ? $city_data[0]->id : '';
                }

                $product_delivarable = app(DeliveryService::class)
                    ->checkCartProductsDeliverable(
                        $user_id,
                        '',
                        '',
                        $store_id,
                        $city,
                        $city_id,
                        '',
                        $language_code
                    );
            } else {
                // Get zipcode_id from database
                $zipcode_id = '';
                if (!empty($zipcode)) {
                    $zipcode_data = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
                    $zipcode_id = !$zipcode_data->isEmpty() ? $zipcode_data[0]->id : '';
                }

                $product_delivarable = app(DeliveryService::class)
                    ->checkCartProductsDeliverable(
                        $user_id,
                        $zipcode,
                        $zipcode_id,
                        $store_id,
                        '',
                        '',
                        '',
                        $language_code
                    );
            }
        }

        if (empty($product_delivarable)) {
            return response()->json([
                'error' => true,
                'message' => 'Product(s) are not delivarable',
                'language_message_key' => 'products_are_not_deliverable',
                'data' => []
            ]);
        }

        $not_deliverable = array_filter(
            $product_delivarable,
            fn($item) =>
            $item['product_id'] !== null && $item['is_deliverable'] === false
        );

        if (!empty($not_deliverable)) {
            return response()->json([
                'error' => true,
                'message' => 'Some items are not delivarable on selected address.',
                'language_message_key' => 'some_items_not_deliverable_on_selected_address_change_the_address',
                'data' => array_values($product_delivarable)
            ]);
        }

        /** ---------------- SHIPROCKET STANDARD DELIVERY ---------------- */
        $delivery_charge_without_cod = 0.0;
        $delivery_charge_with_cod = 0.0;
        $cod_extra_charge = 0.0;
        $shipping_options = [];
        $default_shipping_option = null;

        $has_standard_shipping = collect($product_delivarable)
            ->where('delivery_by', 'standard_shipping')
            ->isNotEmpty();


        if ($has_standard_shipping && !empty($cart_data['cart_items'])) {
            try {
                $parcels = app(\App\Services\ShiprocketService::class)
                    ->makeShippingParcels(collect($cart_data['cart_items']));

                if (!$parcels->isEmpty()) {

                    // NON-COD
                    $non_cod = app(\App\Services\ShiprocketService::class)
                        ->checkParcelsDeliverability($parcels, $zipcode, $cart_subtotal);

                    // COD
                    $cod = app(\App\Services\ShiprocketService::class)
                        ->checkParcelsDeliverability($parcels, $zipcode, $cart_subtotal);

                    $shipping_options = app(\App\Services\ShiprocketService::class)
                        ->getAvailableShippingOptions($parcels, $zipcode, 0, $cart_subtotal);



                    /** ---- DEFAULT = RECOMMENDED ---- */
                    if (!empty($shipping_options) && isset($shipping_options['recommended'])) {

                        $requested_option = $request->input('shipping_option', 'recommended');
                        $courier_rate = (float) ($shipping_options[$requested_option]['rate'] ?? 0);

                        // Shiprocket COD extra fee
                        $cod_extra_charge = (float) (
                            $shipping_options[$requested_option]['cod']
                            ?? 30
                        );

                        $delivery_charge_without_cod = $courier_rate;
                        $delivery_charge_with_cod = $courier_rate + $cod_extra_charge ?? 30;

                        $default_shipping_option = 'recommended';

                        foreach ($shipping_options as $key => &$option) {
                            $option['rate'] = (float) $option['rate'];
                            $option['format_rate'] = '₹' . number_format($option['rate'], 2);
                            $option['is_selected'] = ($key === 'recommended');
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Shiprocket calculation failed', [
                    'user_id' => $user_id,
                    'zipcode' => $zipcode,
                    'error' => $e->getMessage()
                ]);
            }
        }


        /** ---------------- FINAL TOTALS (BEFORE PROMO) ---------------- */
        $final_total_without_cod = round($cart_subtotal + $delivery_charge_without_cod, 2);
        $final_total_with_cod = round($cart_subtotal + $delivery_charge_with_cod, 2);

        /** ---------------- PROMO CODE VALIDATION & APPLICATION ---------------- */
        $promo_code_discount = 0.0;
        $promo_code_data = null;

        if (!empty($promo_code)) {
            // Validate promo code against the total (we'll use final_total_without_cod as base)
            $promo_validation = app(\App\Services\PromoCodeService::class)
                ->validatePromoCode($promo_code, $user_id, $final_total_without_cod, 0, $language_code);

            $promo_response = $promo_validation->original;

            if (!$promo_response['error'] && !empty($promo_response['data'])) {
                $promo_data = $promo_response['data'][0];
                $promo_code_discount = (float) ($promo_data->final_discount ?? 0);

                // Apply discount to both totals
                $final_total_without_cod = max(0, $final_total_without_cod - $promo_code_discount);
                $final_total_with_cod = max(0, $final_total_with_cod - $promo_code_discount);

                $promo_code_data = [
                    'id' => $promo_data->id,
                    'promo_code' => $promo_data->promo_code,
                    'discount' => $promo_code_discount,
                    'discount_type' => $promo_data->discount_type,
                    'is_cashback' => $promo_data->is_cashback ?? 0,
                ];
            }
        }

        /** ---------------- RESPONSE ---------------- */
        return response()->json([
            'error' => false,
            'message' => 'Product(s) are delivarable.',
            'language_message_key' => 'products_are_deliverable',

            'data' => array_values($product_delivarable),

            'cart_subtotal' => round($cart_subtotal, 2),

            'delivery_charge_without_cod' => round($delivery_charge_without_cod, 2),
            'delivery_charge_with_cod' => round($delivery_charge_with_cod, 2),
            'cod_extra_charge' => round($cod_extra_charge, 2),

            'promo_code_discount' => round($promo_code_discount, 2),
            'promo_code_data' => $promo_code_data,

            'final_total_without_cod' => round($final_total_without_cod, 2),
            'final_total_with_cod' => round($final_total_with_cod, 2),

            'currency' => [
                'code' => 'INR',
                'symbol' => '₹'
            ],

            'currency_cart_subtotal' => '₹' . number_format($cart_subtotal, 2),
            'currency_delivery_charge_without_cod' => '₹' . number_format($delivery_charge_without_cod, 2),
            'currency_delivery_charge_with_cod' => '₹' . number_format($delivery_charge_with_cod, 2),
            'currency_promo_code_discount' => '₹' . number_format($promo_code_discount, 2),
            'currency_final_total_without_cod' => '₹' . number_format($final_total_without_cod, 2),
            'currency_final_total_with_cod' => '₹' . number_format($final_total_with_cod, 2),

            'shipping_options' => $shipping_options,
            'default_shipping_option' => $default_shipping_option,
        ]);
    }




    public function get_sellers(Request $request, SellerController $sellerController)
    {
        /*
            store_id:1
            store_slug:store-slug  //{optional - alternative to store_id}
            slug:seller-slug  //{optional - filter by seller slug}
            zipcode:1  //{optional}
            search : Search keyword // { optional }
            limit:25                // { default - 25 } optional
            offset:0                // { default - 0 } optional
            sort: id    // { default - id } optional
            order:DESC/ASC          // { default - DESC } optional
        */
        $rules = [
            'store_id' => 'required_without:store_slug|exists:stores,id',
            'store_slug' => 'required_without:store_id|string|exists:stores,slug',
            'slug' => 'sometimes|string',
            'zipcode' => 'sometimes|string',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            // Resolve store_id from store_slug if provided
            if ($request->filled('store_slug') && !$request->filled('store_id')) {
                $store = fetchDetails(Store::class, ['slug' => $request->input('store_slug')], 'id');
                if ($store->isEmpty()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Store not found',
                        'language_message_key' => 'store_not_found',
                        'data' => []
                    ]);
                }
                $store_id = (int) $store[0]->id;
            } else {
                $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';
            }

            $zipcode = $request->input('zipcode_id', '');
            $search = $request->input('search', null);
            $slug = $request->input('slug', null);
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order = $request->input('order', 'DESC');
            $sort = $request->input('sort', 'users.id');
            $user_id = auth('sanctum')->check() ? (int) auth('sanctum')->id() : '';
            $seller_ids = $request->input('seller_ids', null);
            if (!is_null($seller_ids)) {
                $seller_ids = explode(",", $seller_ids);
            }
            if (isset($zipcode) && !empty($zipcode)) {
                $is_pincode = isExist(['zipcode' => $zipcode], Zipcode::class);
                if ($is_pincode) {
                    $zipcode_ids = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
                    $zipcode_id = !$zipcode_ids->isEmpty() ? $zipcode_ids[0]->id : "";
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Sellers Not Found!';
                    $response['language_message_key'] = 'sellers_not_found';
                    $response['data'] = array();
                    return response()->json($response);
                }
            } else {
                $zipcode_id = "";
            }
            $data = $sellerController->getSellers($zipcode_id, $limit, $offset, $sort, $order, $search, '', $store_id, $seller_ids, $user_id);
            // dd($data);

            return response()->json($data);
            
        }
    }

    public function get_promo_codes(Request $request, PromoCodeController $PromoCodeController)
    {
        /*
            store_id:1
            search : Search keyword // { optional }
            limit:25                // { default - 25 } optional
            offset:0                // { default - 0 } optional
            sort: id    // { default - id } optional
            order:DESC/ASC          // { default - DESC } optional
        */
        $rules = [
            'store_id' => 'required|exists:stores,id',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $store_id = request('store_id') != null ? request('store_id') : "";

            $search = $request->input('search', null);
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order = $request->input('order', 'DESC');
            $sort = $request->input('sort', 'id');
            $language_code = $request->attributes->get('language_code');
            $data = $PromoCodeController->getPromoCodes($limit, $offset, $sort, $order, $search, $store_id, $language_code);

            return response()->json($data);
        }
    }
    public function get_stores(Request $request, StoreController $StoreController)
    {
        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $search = $request->input('search', null);
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order = $request->input('order', 'DESC');
            $sort = $request->input('sort', 'id');
            $language_code = $request->attributes->get('language_code');
            // dd($language_code);
            $data = $StoreController->getStores($limit, $offset, $sort, $order, $search, "", $language_code);

            return response()->json($data);
        }
    }

    // public function get_brands(Request $request, BrandController $BrandController)
    // {
    //     $rules = [
    //         'store_id' => 'required|exists:stores,id',
    //         'limit' => 'numeric',
    //         'offset' => 'numeric',
    //     ];
    //     if ($validationResponse= $this->HandlesValidation($request, $rules, [], null, true)) {
    //         return $response;
    //     } else {
    //         $store_id = request('store_id') != null ? request('store_id') : "";
    //         $ids = $request->filled('ids') ? $request->input('ids') : '';
    //         $search = $request->input('search', null);
    //         $limit = $request->input('limit', 25);
    //         $offset = $request->input('offset', 0);

    //         $data = $BrandController->get_brand_list($search, $offset, $limit, $store_id, $ids);

    //         return response()->json($data);
    //     }
    // }
    public function get_brands(Request $request, BrandController $BrandController)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $store_id = request('store_id') ?? "";
            $ids = $request->filled('ids') ? $request->input('ids') : '';
            $search = $request->input('search', null);
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);

            // Fetch the language ID from the middleware
            $language_code = $request->attributes->get('language_code');

            // Pass language ID to the function if needed
            $data = $BrandController->get_brand_list($search, $offset, $limit, $store_id, $ids, $language_code);

            return response()->json($data);
        }
    }

    public function sign_up(Request $request)
    {
        // Step 1: Basic validation
        $rules = [
            'mobile' => 'nullable|sometimes|numeric',
            'email' => 'nullable|sometimes|email',
            'fcm_id' => 'nullable|sometimes',
            'type' => 'nullable|sometimes',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $email  = $request->input('email', '');
        $mobile = $request->input('mobile', '');
        $type   = $request->input('type', '');
        $where  = !empty($mobile) ? ['mobile' => $mobile] : ['email' => $email];

        // Step 2: Check if user exists
        $user = User::where($where)
            ->whereNotIn('type', ['phone'])
            ->first();

        if ($user) {
            // Existing user login
            if ($request->filled('fcm_id')) {
                UserFcm::firstOrCreate([
                    'user_id' => $user->id,
                    'fcm_id'  => $request->input('fcm_id'),
                ]);
            }

            // Ensure type matches
            if ($user->type !== $type) {
                return response()->json([
                    'error' => true,
                    'message' => 'User does not exist with this type!',
                    'language_message_key' => 'user_does_not_exist',
                    'data' => [],
                ]);
            }

            // Generate FCM list
            $fcm_ids = UserFcm::where('user_id', $user->id)->pluck('fcm_id')->toArray();

            // Fix image path
            // if (empty($user->image) || !Storage::exists('USER_IMG_PATH' . $user->image)) {
            //     $user->image = asset(Config::get('constants.NO_USER_IMAGE'));
            // } else {
            //     $user->image = app(MediaService::class)->getImageUrl('USER_IMG_PATH' . $user->image);
            // }

            // Format user data
            $user_data = $this->formatUserData($user, $fcm_ids);

            if ($user->active == 0) {
                return response()->json([
                    'error' => true,
                    'message' => 'You are not allowed to login. Your account is inactive.',
                    'language_message_key' => 'account_inactive_not_allowed_to_login',
                    'data' => [],
                ]);
            }

            return response()->json([
                'error' => false,
                'message' => 'User Logged in successfully',
                'language_message_key' => 'user_logged_in_successfully',
                'token' => $user->createToken('authToken')->plainTextToken,
                'data' => $user_data,
            ]);
        }

        // Step 3: New user registration
        $rules = [
            'type' => 'required',
            'name' => 'required',
            'email' => 'nullable|email',
            'mobile' => 'nullable|numeric|unique:users,mobile',
            'country_code' => 'nullable',
            'fcm_id' => 'nullable',
            'referral_code' => 'nullable|unique:users,referral_code',
            'friends_code' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'image' => 'nullable|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Check referral
        if ($request->filled('friends_code')) {
            $friend = User::where('referral_code', $request->input('friends_code'))->first();
            if (!$friend) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid friends code! Please pass the valid referral code of the inviter',
                    'language_message_key' => 'invalid_friends_code_pass_valid_referral_code',
                    'data' => [],
                ]);
            }
        }

        // Create user
        $user = User::create([
            'username'     => $request->input('name'),
            'mobile'       => $mobile,
            'email'        => $email,
            'type'         => $type,
            'country_code' => $request->input('country_code'),
            'referral_code' => $request->input('referral_code'),
            'friends_code' => $request->input('friends_code'),
            'latitude'     => $request->input('latitude'),
            'longitude'    => $request->input('longitude'),
            'image'        => $request->input('image') ?? '',
            'active'       => 1,
            'role_id'      => 2,
        ]);
        $user->refresh()->load(['city']);
        // Add FCM
        if ($request->filled('fcm_id')) {
            UserFcm::firstOrCreate([
                'user_id' => $user->id,
                'fcm_id'  => $request->input('fcm_id'),
            ]);
        }

        // Wallet welcome bonus
        $settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);
        if (!empty($settings['wallet_balance_status']) && $settings['wallet_balance_status'] == 1) {
            $wallet_balance = $settings['wallet_balance_amount'] ?? 0;
            app(WalletService::class)->updateWalletBalance('credit', $user->id, $wallet_balance, 'Welcome Wallet Amount Credited for User ID: ' . $user->id);
        }

        // Prepare response data
        $fcm_ids = UserFcm::where('user_id', $user->id)->pluck('fcm_id')->toArray();
        $user_data = $this->formatUserData($user, $fcm_ids);

        return response()->json([
            'error' => false,
            'message' => 'Registered Successfully',
            'language_message_key' => 'registered_successfully',
            'token' => $user->createToken('authToken')->plainTextToken,
            'data' => $user_data,
        ]);
    }

    /**
     * Format user data for API response.
     */
    private function formatUserData(User $user, array $fcm_ids): array
    {
        $fields = [
            'id',
            'ip_address',
            'username',
            'mobile',
            'email',
            'balance',
            'activation_selector',
            'activation_code',
            'forgotten_password_selector',
            'forgotten_password_code',
            'forgotten_password_time',
            'remember_selector',
            'remember_code',
            'created_on',
            'last_login',
            'active',
            'company',
            'address',
            'bonus_type',
            'bonus',
            'dob',
            'country_code',
            'city',
            'area',
            'street',
            'pincode',
            'serviceable_zones',
            'apikey',
            'is_notification_on',
            'referral_code',
            'friends_code',
            'latitude',
            'longitude',
            'type',
            'front_licence_image',
            'back_licence_image'
        ];

        $user_data = [];
        foreach ($fields as $field) {
            $user_data[$field] = $user->$field ?? "";
        }

        // Handle image properly
        if (!empty($user->image)) {
            if (filter_var($user->image, FILTER_VALIDATE_URL)) {
                $user_data['image'] = $user->image; // keep external URL
            } elseif (Storage::disk('public')->exists('users/' . $user->image)) {
                $user_data['image'] = app(MediaService::class)->getMediaImageUrl($user->image, 'USER_IMG_PATH');
            } else {
                $user_data['image'] = asset(Config::get('constants.NO_USER_IMAGE'));
            }
        } else {
            $user_data['image'] = asset(Config::get('constants.NO_USER_IMAGE'));
        }


        // Add FCM IDs
        $user_data['fcm_id'] = $fcm_ids;

        return $user_data;
    }




    public function delete_social_account()
    {
        $user_id = auth()->user()->id;
        $user_data = fetchDetails(User::class, ['id' => $user_id], ['id', 'username', 'role_id']);
        $role_id = $user_data[0]->role_id;

        if ($user_data) {
            $user_roles = fetchDetails(Role::class, ['id' => $role_id]);

            if ($user_roles[0]->id == 2) {
                $status = 'awaiting,received,processed,shipped';
                $multiple_status = explode(',', $status);
                $orders = app(OrderService::class)->fetchOrders('', $user_id, $multiple_status);

                foreach ($orders['order_data'] as $order) {

                    updateDetails(['status' => 'cancelled'], ['id' => $order->id], Order::class);
                    updateDetails(['active_status' => 'cancelled'], ['id' => $order->id], Order::class);

                    updateDetails(['active_status' => 'cancelled'], ['order_id' => $order->id], OrderItems::class);
                    updateDetails(['active_status' => 'cancelled'], ['order_id' => $order->id], OrderItems::class);


                    app(OrderService::class)->process_refund($order->id, 'cancelled', 'orders');

                    $data = fetchDetails(OrderItems::class, ['order_id' => $order->id], ['product_variant_id', 'quantity']);
                    $product_variant_ids = [];
                    $qtns = [];

                    foreach ($data as $d) {
                        $product_variant_ids[] = $d['product_variant_id'];
                        $qtns[] = $d['quantity'];
                    }
                    app(ProductService::class)->updateStock($product_variant_ids, $qtns, 'plus');
                }
                deleteDetails(['id' => $user_id], User::class);
                return response()->json(['error' => false, 'message' => 'User Deleted Successfully', 'language_message_key' => 'user_deleted_successfully']);
            } else {
                return response()->json(['error' => true, 'message' => 'Details do not match', 'language_message_key' => 'details_do_not_match']);
            }
        } else {
            return response()->json(['error' => true, 'message' => 'User not found', 'language_message_key' => 'user_does_not_exist']);
        }
    }

    public function add_product_faqs(Request $request)
    {
        /*
            product_id:1
            question : you question here
            product_type:regular // {regular / combo}
        */

        $rules = [
            'product_id' => 'required|numeric',
            'question' => 'required|string',
            'product_type' => 'required'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $product_id = $request->input('product_id');
        $product_type = request('product_type') != null ? Str::lower(request('product_type')) : "";
        $user_id = auth()->user()->id;
        $question = $request->input('question');

        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User not found!',
                'language_message_key' => 'user_does_not_exist',
                'data' => []
            ]);
        }

        if ($product_type == 'regular') {

            if (!isExist(['id' => $product_id], Product::class)) {
                $response = [
                    'error' => true,
                    'message' => 'Product not available.',
                    'language_message_key' => 'product_not_available',
                    'data' => [],
                ];
                return response()->json($response);
            }
            $product_faqs = new ProductFaq([
                'product_id' => $product_id,
                'user_id' => $user_id,
                'question' => $question,
            ]);

            $product_faqs->save();

            $result = ProductFaq::where('id', $product_faqs->id)
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
        }
        if ($product_type == 'combo') {
            if (!isExist(['id' => $product_id], ComboProduct::class)) {
                $response = [
                    'error' => true,
                    'message' => 'Product not available.',
                    'language_message_key' => 'product_not_available',
                    'data' => [],
                ];
                return response()->json($response);
            }
            $product_faqs = new ComboProductFaq([
                'product_id' => $product_id,
                'user_id' => $user_id,
                'question' => $question,
            ]);

            $product_faqs->save();

            $result = ComboProductFaq::where('id', $product_faqs->id)
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
        }

        foreach ($result as $value) {
            $fields = [
                'id',
                'user_id',
                'seller_id',
                'product_id',
                'votes',
                'question',
                'answer',
                'answered_by'
            ];

            foreach ($fields as $field) {
                $faq_data[$field] = ($value->$field == null) ? "" : $value->$field;
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'FAQs added successfully',
            'language_message_key' => 'faqs_added_successfully',
            'data' => $faq_data ? $faq_data : []
        ]);
    }

    public function get_product_faqs(Request $request)
    {

        $rules = [
            'id' => 'nullable|numeric',
            'product_id' => 'nullable|numeric',
            'search' => 'nullable|string',
            'sort' => 'nullable|string',
            'limit' => 'nullable|numeric',
            'offset' => 'nullable|numeric',
            'order' => 'nullable|string',
            'product_type' => 'required'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $id = $request->input('id');
        $product_id = $request->input('product_id');
        $user_id = $request->input('user_id');
        $search = trim($request->input('search'));
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'id');
        $product_type = request('product_type') != null ? Str::lower(request('product_type')) : "";

        $query = null;

        if ($product_type == 'regular') {
            if (!isExist(['id' => $product_id], Product::class)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Product not available.',
                    'language_message_key' => 'product_not_available',
                    'data' => [],
                    'total' => 0,
                ]);
            }
            $query = ProductFaq::when($id, function ($query) use ($id) {
                $query->where('id', $id);
            })
                ->when($product_id, function ($query) use ($product_id) {
                    $query->where('product_id', $product_id);
                })
                ->when($user_id, function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('question', 'like', '%' . $search . '%');
                })->whereNotNull('answer')
                ->where('answer', '!=', '');
        }

        if ($product_type == 'combo') {
            if (!isExist(['id' => $product_id], ComboProduct::class)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Product not available.',
                    'language_message_key' => 'product_not_available',
                    'data' => [],
                    'total' => 0,
                ]);
            }

            $query = ComboProductFaq::when($id, function ($query) use ($id) {
                $query->where('id', $id);
            })
                ->when($product_id, function ($query) use ($product_id) {
                    $query->where('product_id', $product_id);
                })
                ->when($user_id, function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('question', 'like', '%' . $search . '%');
                })->whereNotNull('answer')
                ->where('answer', '!=', '');
        }

        if ($query === null) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid product type.',
                'language_message_key' => 'invalid_product_type',
                'data' => [],
                'total' => 0,
            ]);
        }
        // dd($query->tosql(),$query->getbindings());
        // Get total count of records
        $total = $query->count();

        // Get paginated results
        $result = $query->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        foreach ($result as &$item) {
            foreach (['answer'] as $field) {
                if (array_key_exists($field, $item) && $item[$field] === null) {
                    $item[$field] = "";
                }
            }

            foreach (['seller_id'] as $field) {
                if (array_key_exists($field, $item) && $item[$field] === null) {
                    $item[$field] = "";
                }
            }

            // Add username for answered_by field
            if ($item['answered_by'] == 0) {
                $item['answered_by'] = "";
            } else {
                $username = $this->getUserName($item['answered_by']);
                $item['answered_by'] = !empty($username) ? $username : "";
            }
        }

        return response()->json([
            'error' => !empty($result) ? false : true,
            'message' => !empty($result) ? 'Faqs Retrieved Successfully' : 'No FAQs found!',
            'language_message_key' => !empty($result) ? 'faqs_retrieved_successfully' : 'no_faqs_found',
            'total' => $total,
            'data' => $result,
        ]);
    }

    private function getUserName($userId)
    {
        $user = User::find($userId);
        return $user ? $user->username : "";
    }


    public function send_message(Request $request, TicketController $TicketController)
    {

        $rules = [
            'user_type' => 'required|alpha',
            'ticket_id' => 'required|numeric',
            'message' => 'nullable|string',
            'attachments.*' => 'nullable|max:8000',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_type = $request->input('user_type');
        $user_id = auth()->user()->id;
        $ticket_id = $request->input('ticket_id');
        $message = $request->input('message', '');

        $user = fetchUsers($user_id);
        if (empty($user)) {
            return response()->json([
                'error' => true,
                'message' => 'User not found!',
                'language_message_key' => 'user_does_not_exist',
                'data' => []
            ]);
        }

        // Check if ticket is awaiting admin reply (customers cannot chat until admin replies)
        $ticket = Ticket::find($ticket_id);
        if ($ticket && $user_type === 'user' && $ticket->awaiting_admin_reply) {
            return response()->json([
                'error' => true,
                'message' => 'Chat will be available after admin responds to your ticket.',
                'language_message_key' => 'awaiting_admin_reply',
                'data' => []
            ]);
        }

        $uploaded_images = [];

        if (!File::exists('storage/tickets')) {
            File::makeDirectory('storage/tickets', 0755, true);
        }

        //code for upload media attachements
        try {
            $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
            $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
            $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';

            $media = StorageType::find($mediaStorageType);

            $mediaIds = [];

            if ($request->hasFile('attachments')) {

                $files = $request->file('attachments');

                foreach ($files as $file) {
                    $mediaItem = $media->addMedia($file)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));

                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);

                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('tickets', $disk);

                    $mediaIds[] = $mediaItem->id;

                    if ($disk == 'public') {
                        $uploaded_images[] = 'tickets/' . $mediaItem->file_name;
                    }
                }
            }
            if ($disk == 's3') {
                $media_list = $media->getMedia('tickets');
                for ($i = 0; $i < count($mediaIds); $i++) {
                    $media_url = $media_list[($media_list->count()) - (count($mediaIds) - $i)]->getUrl();

                    $uploaded_images[] = $media_url;

                    Media::destroy($mediaIds[$i]);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }

        $ticket_messages = new TicketMessage([
            'user_type' => $user_type,
            'user_id' => $user_id,
            'ticket_id' => $ticket_id,
            'message' => $message,
            'attachments' => json_encode($uploaded_images) ?? [],
            'disk' => $disk ?? '',
        ]);

        $response = $ticket_messages->save();
        $last_insert_id = $ticket_messages->id;


        if ($response) {
            $type = config('eshop_pro.type');
            $result = $TicketController->getMessages($ticket_id, $user_id, "", "", "1", "id", "DESC", $type, $last_insert_id);

            return response()->json([
                'error' => false,
                'message' => 'Message send successfully',
                'language_message_key' => 'message_send_successfully',
                'data' => $result['data'][0]
            ]);
        }
    }

    public function get_zipcodes(Request $request, AreaController $AreaController)
    {
        $rules = [
            'limit' => 'nullable|numeric',
            'offset' => 'nullable|numeric',
            'search' => 'nullable|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }



        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $search = $request->input('search', '');

        $zipcodes = $AreaController->getZipcodes($search, $limit, $offset);

        return response()->json($zipcodes);
    }

    public function update_order_status(Request $request, OrderController $OrderController)
    {
        $rules = [
            'order_id' => 'required|numeric|exists:orders,id',
            'status' => 'required|in:received,processed,shipped,delivered,cancelled,returned',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $allStatus = ['received', 'processed', 'shipped', 'delivered', 'cancelled', 'returned'];

        if (!in_array(strtolower($request->status), $allStatus)) {
            return response()->json(['error' => true, 'message' => 'Invalid Status supplied', 'language_message_key' => 'invalid_status_supplied', 'data' => []]);
        }

        $order = Order::findOrFail($request->order_id);
        $orderMethod = $order->payment_method;

        if ($orderMethod == 'bank_transfer') {
            $bankReceipt = OrderBankTransfers::where('order_id', $request->order_id)->first();
            $transactionStatus = Transaction::where('order_id', $request->order_id)->value('status');

            if ($request->status != 'cancelled' && (empty($bankReceipt) || strtolower($transactionStatus) != 'success')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Order Status cannot be updated. Bank verification is pending in transactions.',
                    'language_message_key' => 'order_status_cannot_be_updated_bank_verification_pending',
                    'data' => []
                ]);
            }
        }

        $response = $OrderController->update_order_status($request);

        if (trim($request->status) != 'returned') {
            app(OrderService::class)->process_refund($request->order_id, $request->status, 'order_items');
        }

        if (trim($request->status) == 'cancelled') {
            $data = Order::find($request->order_id)->orderItems()->first(['product_variant_id', 'quantity', 'order_type']);

            if ($data[0]->order_type == 'regular_order') {
                app(ProductService::class)->updateStock($data[0]->product_variant_id, $data[0]->quantity, 'plus');
            }
            if ($data[0]->order_type == 'combo_order') {
                app(ComboProductService::class)->updateComboStock($data[0]->product_variant_id, $data[0]->quantity, 'plus');
            }
        }
        return $response;
    }

    public function delete_order(Request $request)
    {

        $rules = [
            'order_id' => 'required|numeric|exists:orders,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $order_id = $request->order_id;
        $order_items = fetchDetails(OrderItems::class, ['order_id' => $order_id], ['user_id', 'product_variant_id', 'quantity', 'store_id', 'order_type']);
        $order = app(OrderService::class)->fetchOrders($order_id, false, false, false, false, false, 'o.id', 'DESC');
        foreach ($order_items as $order_item) {
            $cart_data = [
                'user_id' => $order_item->user_id,
                'product_variant_id' => $order_item->product_variant_id,
                'qty' => $order_item->quantity,
                'is_saved_for_later' => 0,
                'store_id' => $order_item->store_id,
                'product_type' => $order_item->order_type == 'regular_order' ? 'regular' : 'combo',
            ];
            $test = Cart::create($cart_data);
        }
        // Restore stock for all order items
        foreach ($order_items as $order_item) {
            if ($order_item->order_type == 'regular_order') {
                app(ProductService::class)->updateStock($order_item->product_variant_id, $order_item->quantity, 'plus');
            }
            if ($order_item->order_type == 'combo_order') {
                app(ComboProductService::class)->updateComboStock($order_item->product_variant_id, $order_item->quantity, 'plus');
            }
        }
        if (isset($order['order_data']->first()->wallet_balance) && $order['order_data']->first()->wallet_balance != '' && $order['order_data']->first()->wallet_balance != 0) {
            app(WalletService::class)->updateWalletBalance('credit', $order['order_data']->first()->user_id, $order['order_data']->first()->wallet_balance, 'Wallet Amount Credited for Order ID: ' . $order['order_data']->first()->id);
        }
        Order::where('id', $order_id)->delete();
        OrderItems::where('order_id', $order_id)->delete();
        return response()->json([
            'error' => false,
            'message' => 'Order deleted successfully',
            'language_message_key' => 'order_deleted_successfully',
            'data' => []
        ]);
    }

    public function validate_refer_code(Request $request)
    {

        $rules = [
            'referral_code' => 'required|alpha_num|unique:users,referral_code',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }


        return response()->json([
            'error' => false,
            'message' => 'Referral Code is available to be used',
            'language_message_key' => 'referral_code_available_to_use',
        ]);
    }

    public function get_notifications(Request $request, NotificationController $NotificationController)
    {

        $rules = [
            'sort' => 'nullable|sometimes|string',
            'limit' => 'nullable|sometimes|numeric',
            'offset' => 'nullable|sometimes|numeric',
            'order' => 'nullable|sometimes|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'id');
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : "";

        $language_code = $request->attributes->get('language_code');
        $res = $NotificationController->get_notifications($offset, $limit, $sort, $order, $user_id, $language_code);
        return response()->json([
            'error' => empty($res['data']) ? true : false,
            'message' => empty($res['data']) ? 'Notification not found' : 'Notification Retrieved Successfully',
            'language_message_key' => empty($res['data']) ? 'no_notification_found' : 'notification_retrieved_successfully',
            'total' => $res['total'],
            'data' => $res['data'],
        ]);
    }

    public function add_transaction(Request $request, TransactionController $TransactionController)
    {
        Log::alert("[API] add_transaction called - payment_method: " . $request->input('payment_method') . ", txn_id: " . $request->input('txn_id') . ", amount: " . $request->input('amount'));

        $rules = [
            'transaction_type' => 'nullable|string',
            'order_id' => 'nullable',
            'type' => 'nullable|string',
            'txn_id' => 'required|string',
            'amount' => 'required|numeric',
            'status' => 'nullable|string', // ✅ Fixed: changed to nullable
            'message' => 'nullable|string',
            'skip_verify_transaction' => 'nullable|string',
            'payment_method' => 'required_if:transaction_type,wallet|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $transaction_type = $request->input('transaction_type');
        $payment_method = strtolower($request->input('payment_method') ?? '');
        $order_id = $request->input('order_id');

        // ✅ Reinforced defaults for Stripe: often apps only send payment_method=stripe
        // but forget the explicit transaction_type=wallet and type=credit.
        if ($payment_method === 'stripe') {
            if (empty($transaction_type) && empty($order_id)) {
                $transaction_type = 'wallet';
            }
            if (empty($request['type'])) {
                $request['type'] = 'credit';
            } // Essential for wallet branch line 6136
        }

        $type = $request->input('type') ?? "credit";
        $user_id = auth()->user()->id;
        $txn_id = $request->input('txn_id');
        $status = $request->input('status');
        $amount = $request->input('amount');
        $payment_method = strtolower($request->input('payment_method'));

        // Convert amount for Razorpay wallet transactions (app sends *100, but store actual amount)
        if ($payment_method === 'razorpay' && $transaction_type === 'wallet' && !empty($amount)) {
            $amount = $amount / 100;
            Log::alert("[API] Razorpay wallet transaction - converted amount from paise: {$amount}");
        }

        // Auto-detect transaction type: if order_id is provided, it's an order payment, not wallet refill
        if (empty($transaction_type) && !empty($order_id)) {
            $transaction_type = 'transaction';
            Log::alert("[API] Auto-detected transaction_type as 'transaction' for order_id: {$order_id}");
        } elseif (empty($transaction_type)) {
            $transaction_type = 'wallet'; // Default to wallet for backward compatibility
            Log::alert("[API] Defaulting transaction_type to 'wallet' (no order_id provided)");
        }

        // ✅ Auto-generate order_id for Stripe wallet transactions when the app doesn't send one.
        // The webhook uses the 'wallet-refill-user-{user_id}-{timestamp}' pattern to:
        //   (a) infer the payment type when metadata is empty (old sessions)
        //   (b) look up the user_id from the DB as a fallback
        // Without this, order_id is NULL and the webhook cannot resolve the user to credit.
        if ($payment_method === 'stripe' && $transaction_type === 'wallet' && empty($order_id)) {
            $order_id = 'wallet-refill-user-' . $user_id . '-' . time();
            Log::alert("[API] Auto-generated order_id for Stripe wallet transaction: {$order_id}");
        }

        Log::alert("[API] Processing transaction - type: {$transaction_type}, order_id: {$order_id}, payment_method: " . $request->input('payment_method'));

        // Only process wallet balance updates for actual wallet transactions (not order payments)
        // Note: we now proceed even if $order_id is present because we auto-generate it for Stripe.
        if ($transaction_type === 'wallet' && $request->input('type') === 'credit') {
            $payment_method = strtolower($request->input('payment_method'));
            Log::alert("[API] Processing wallet credit transaction - payment_method: {$payment_method}");

            $user = fetchUsers($user_id);
            if (empty($user)) {
                return response()->json([
                    'error' => true,
                    'message' => 'User not found!',
                    'language_message_key' => 'user_does_not_exist',
                    'data' => []
                ]);
            }
            $old_balance = isset($user->balance) && $user->balance !== "" ? $user->balance : "";
            $skip_verify_transaction = ($request->input('skip_verify_transaction') != null) ? $request->input('skip_verify_transaction') : false;

            $transaction = fetchDetails(Transaction::class, ['txn_id' => $txn_id]);
            Log::alert("[API] Checking for duplicate transaction - txn_id: {$txn_id}, found: " . ($transaction->isempty() ? 'NO' : 'YES'));
            // dd($transaction->isempty());
            if ($transaction->isempty() || (isset($transaction[0]['status']) && strtolower($transaction[0]['status']) != 'success')) {
                // Transaction is new or not successful - proceed with wallet balance update
                Log::alert("[API] Transaction is new or not successful - proceeding with wallet update");
                $status = strtolower($request->input('status') ?? 'awaiting');
                $type = $request->input('type') ?? 'credit';

                if ($payment_method == 'stripe' && $status != 'success') {
                    $status = 'awaiting';
                }

                if ($payment_method != 'paystack' && $payment_method != 'stripe' || $skip_verify_transaction == 'true' || $status == 'success') {
                    // Update wallet balance for all payment methods except Paystack and Stripe (unless skip_verify is true or status is already success)
                    if (!app(WalletService::class)->updateBalance($amount, $user_id, 'add')) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Wallet could not be recharged! Please try again later.',
                            'language_message_key' => 'wallet_recharge_failed',
                            'amount' => 0,
                            'old_balance' => "$old_balance",
                            'new_balance' => "$old_balance",
                            'data' => []
                        ]);
                    }

                    $user = fetchUsers($user_id);
                    $new_balance = isset($user->balance) && $user->balance !== "" ? $user->balance : "";

                    Log::alert("[API Wallet] Balance updated for user_id={$user_id} amount={$amount} old_balance={$old_balance} new_balance={$new_balance}");
                } else {
                    Log::alert("[API Wallet] {$payment_method} transaction - skipping balance update, waiting for webhook");
                }
            } else {
                Log::alert("[API] Duplicate transaction found - txn_id: {$txn_id} already exists with status: " . $transaction[0]['status']);
                return response()->json([
                    'error' => true,
                    'message' => 'Wallet could not be recharged! Transaction has already been added before',
                    'language_message_key' => 'wallet_recharge_transaction_already_added',
                    'amount' => 0,
                    'old_balance' => "$old_balance",
                    'new_balance' => "$old_balance",
                    'data' => $transaction,
                ]);
            }
        }
        $transaction_type = (($request->input('transaction_type') != null) && !empty($request->input('transaction_type'))) ? $request->input('transaction_type') : $transaction_type;

        // ✅ Idempotency guard: prevent duplicate transaction records.
        // The Stripe webview flow (stripe_response → WalletController::refill()) may have
        // already created an 'awaiting' record for this txn_id before the app calls
        // add_transaction. Creating a second record would cause the webhook to only update
        // one of them, leaving the other orphaned and the balance potentially double-credited.
        $existing_txn = Transaction::where('txn_id', $txn_id)->first();
        if ($existing_txn) {
            Log::alert("[API] add_transaction — txn_id={$txn_id} already exists (id={$existing_txn->id}, status={$existing_txn->status}). Returning existing record to avoid duplicate.");
            return response()->json([
                'error' => false,
                'message' => ($transaction_type == "wallet") ? 'Wallet Transaction Added Successfully' : 'Order Transaction Added Successfully',
                'language_message_key' => ($transaction_type == "wallet") ? 'wallet_transaction_added_successfully' : 'transaction_added_successfully',
                'data' => $existing_txn,
            ]);
        }

        $order_item_id = fetchDetails(OrderItems::class, ['order_id' => $request->input('order_id')], ['id', 'sub_total']);

        $transaction_data = [
            'transaction_type' => $transaction_type,
            'user_id' => $user_id,
            'order_id' => $order_id, // ✅ Use generated $order_id, not $request->input('order_id')
            'type' => isset($type) && !empty($type) ? $type : $transaction_type,
            'txn_id' => $txn_id,
            'amount' => $amount,
            'status' => $status,
            'message' => $request->input('message') ?? 'Transaction Added via API',
        ];

        $res = Transaction::create($transaction_data);

        Log::alert("[API] Transaction created - ID: {$res->id}, type: {$transaction_type}, order_id: {$order_id}, amount: {$amount}");

        return response()->json([
            'error' => false,
            'message' => ($transaction_type == "wallet") ? 'Wallet Transaction Added Successfully' : 'Order Transaction Added Successfully',
            'language_message_key' => ($transaction_type == "wallet") ? 'wallet_transaction_added_successfully' : 'transaction_added_successfully',
            'data' => $res,
        ]);
    }

    public function transactions(Request $request, TransactionController $TransactionController)
    {

        $rules = [
            'transaction_type' => 'sometimes|nullable',
            'type' => 'sometimes|nullable',
            'search' => 'sometimes|nullable',
            'sort' => 'sometimes|nullable',
            'limit' => 'sometimes|nullable|numeric',
            'offset' => 'sometimes|nullable|numeric',
            'order' => 'sometimes|nullable',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = auth('sanctum')->id();

        $id = $request->input('id', '');
        $transaction_type = $request->input('transaction_type', 'transaction');
        $type = $request->input('type', '');
        $search = $request->input('search', '');
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'id');

        $res = $TransactionController->get_transactions($id, $user_id, $transaction_type, $search, $offset, $limit, $sort, $order, $type);


        if (!$res['data']->isEmpty()) {
            $response = [
                'error' => false,
                'message' => 'Transactions Retrieved Successfully',
                'language_message_key' => 'transactions_retrieved_successfully',
                'total' => $res['total'],
                'balance' => app(WalletService::class)->getUserBalance($user_id),
                'data' => $res['data'],
            ];
        } else {
            $response = [
                'error' => true,
                'message' => 'Transaction Not Exist',
                'language_message_key' => 'transaction_not_exist',
                'data' => [],
            ];
        }
        return response()->json($response);
    }

    public function set_product_rating(Request $request, ProductRatingController $ProductRatingController)
    {
        $rules = [
            'product_id' => 'required|numeric|exists:products,id',
            'rating' => 'required|min:1|max:5',
            'title' => 'required',
            'comment' => 'nullable|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = auth()->user()->id;
        $request['user_id'] = $user_id;


        $files = ($request->allFiles());
        $response = $ProductRatingController->set_rating($request, $files);
        $rating_data = $ProductRatingController->fetch_rating(($request->input('product_id') != null) ? $request->input('product_id') : '', '', '25', '0', 'id', 'DESC', '', '', '', 'true');

        $rating['product_rating'] = $rating_data['product_rating'];

        return response()->json([
            'error' => false,
            'message' => 'Product Rated Successfully',
            'language_message_key' => 'product_rated_successfully',
            'data' => $rating,
        ]);
    }

    public function get_product_rating(Request $request, ProductRatingController $ProductRatingController)
    {
        $rules = [
            'product_id' => 'required|numeric|exists:products,id',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'order' => 'string',
            'has_images' => 'boolean',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = $request->input('user_id');
        $product_id = $request->input('product_id');
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $has_images = $request->input('has_images', false);

        // Increment category clicks
        $category_id = fetchDetails(Product::class, ['id' => $product_id], 'category_id');
        if (!empty($category_id) && isset($category_id[0]->category_id)) {
            Category::where('id', $category_id[0]->category_id)->increment('clicks');
        }

        $pr_rating = fetchDetails(Product::class, ['id' => $product_id], 'rating');
        $avg_rating = (!empty($pr_rating)) ? $pr_rating[0]->rating : "0";

        $rating = $ProductRatingController->fetch_rating(
            $product_id,
            $user_id,
            $limit,
            $offset,
            $sort,
            $order,
            '',
            $has_images,
            '',
            true
        );
        //dd($rating);

        if (!empty($rating['product_rating'])) {
            $response = [
                'error' => false,
                'message' => 'Rating retrieved successfully',
                'language_message_key' => 'rating_retrieved_successfully',
                'no_of_rating' => $rating['no_of_rating'] ?? 0,
                'no_of_reviews' => $rating['no_of_reviews'] ?? 0,
                'total' => $rating['total_reviews'] ?? 0,
                'star_1' => $rating['star_1'] ?? 0,
                'star_2' => $rating['star_2'] ?? 0,
                'star_3' => $rating['star_3'] ?? 0,
                'star_4' => $rating['star_4'] ?? 0,
                'star_5' => $rating['star_5'] ?? 0,
                'total_images' => $rating['total_images'] ?? 0,
                'product_rating' => $rating['rating'] ?? $avg_rating,
            ];

            // Convert nulls to empty strings
            $data = json_decode(json_encode($rating['product_rating']), true);
            foreach ($data as &$item) {
                foreach ($item as $key => $value) {
                    if ($value === null) {
                        $item[$key] = "";
                    }
                }
            }
            $response['data'] = $data;
        } else {
            $response = [
                'error' => true,
                'message' => 'No ratings found!',
                'language_message_key' => 'no_ratings_found',
                'no_of_rating' => $rating['no_of_rating'] ?? 0,
                'no_of_reviews' => $rating['no_of_reviews'] ?? 0,
                'star_1' => $rating['star_1'] ?? 0,
                'star_2' => $rating['star_2'] ?? 0,
                'star_3' => $rating['star_3'] ?? 0,
                'star_4' => $rating['star_4'] ?? 0,
                'star_5' => $rating['star_5'] ?? 0,
                'total_images' => $rating['total_images'] ?? 0,
                'product_rating' => $avg_rating,
                'data' => [],
            ];
        }

        return $response;
    }

    public function delete_product_rating(Request $request, ProductRatingController $ProductRatingController)
    {
        $rules = [
            'rating_id' => 'required|numeric|exists:product_ratings,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }


        $rating = $ProductRatingController->delete_rating(($request->input('rating_id') != null) ? $request->input('rating_id') : '');

        if ($rating == true) {
            return response()->json([
                'error' => false,
                'message' => 'Rating Deleted Successfully',
                'language_message_key' => 'rating_deleted_successfully',
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Something Went Wrong',
                'language_message_key' => 'something_went_wrong',
            ]);
        }
    }
    public function check_shiprocket_serviceability(Request $request)
    {
        /*
            product_variant_id:10
            product_type:regular // {regular / combo}
            delivery_pincode:132456
            delivery_city:bhuj
        */
        $rules = [
            'product_variant_id' => 'required|numeric',
            'delivery_pincode' => 'numeric',
            'product_type' => 'required',
            'declared_value' => 'numeric'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $product_type = request('product_type') != null ? Str::lower(request('product_type')) : "";
        $product_variant_id = ($request->input('product_variant_id') != null) ? $request->input('product_variant_id') : 0;
        $delivery_pincode = ($request->input('delivery_pincode') != null) ? $request->input('delivery_pincode') : 0;
        $delivery_city = ($request->input('delivery_city') != null) ? $request->input('delivery_city') : '';
        $declared_value = ($request->input('declared_value') != null) ? $request->input('declared_value') : 0;


        if ($product_type == 'regular') {
            $product_id = fetchDetails(Product_variants::class, ['id' => $product_variant_id], 'product_id');
            $product_id = $product_id[0]->product_id;
        }

        if ($product_type == 'combo') {
            $product_id = $product_variant_id;
        }


        $settings = app(SettingService::class)->getSettings('shipping_method', true);
        $settings = json_decode($settings, true);

        $is_pincode = isExist(['zipcode' => $delivery_pincode], Zipcode::class);
        $is_city = isExist(['name' => $delivery_city], City::class);

        if ($is_pincode && isset($settings['local_shipping_method']) && $settings['local_shipping_method'] == 1) {

            $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $delivery_pincode], 'id');

            $is_available = app(DeliveryService::class)->isProductDelivarable($type = 'zipcode', $zipcode_id[0]->id, $product_id, $product_type);

            if ($is_available) {
                return response()->json([
                    'error' => false,
                    'message' => 'Product is deliverable on ' . $delivery_pincode,
                ]);
            }
        }
        if ($is_city && isset($settings['local_shipping_method']) && $settings['local_shipping_method'] == 1) {

            $city_id = fetchDetails(City::class, ['name->en' => $delivery_city], 'id');
            if ($city_id->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'City not found',
                    'language_message_key' => 'city_not_found',
                ]);
            }

            $is_available = app(DeliveryService::class)->isProductDelivarable($type = 'city', $city_id[0]->id, $product_id, $product_type);

            if ($is_available) {
                return response()->json([
                    'error' => false,
                    'message' => 'Product is deliverable in ' . $delivery_city,
                ]);
            }
        }
        if (isset($settings['shiprocket_shipping_method']) && $settings['shiprocket_shipping_method'] == 1) {
            if (!empty($product_variant_id) && !empty($delivery_pincode)) {

                $shiprocket = new Shiprocket();
                $min_days = $max_days = $delivery_charge_with_cod = $delivery_charge_without_cod = 0;

                if ($product_type == 'regular') {
                    $product_variant_detail = fetchDetails(Product_variants::class, ['id' => $product_variant_id], ['product_id', 'weight']);
                    if ($product_variant_detail->isEmpty()) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Product variant not found',
                            'language_message_key' => 'product_variant_not_found',
                        ]);
                    }
                    $product_data = fetchDetails(Product::class, ['id' => $product_variant_detail[0]->product_id], 'pickup_location');
                    if (!$product_data->isEmpty()) {
                        $product_detail = $product_data[0]->pickup_location;
                    } else {
                        $product_detail = "";
                    }
                }

                if ($product_type == 'combo') {
                    $product_variant_detail = fetchDetails(ComboProduct::class, ['id' => $product_id], ['weight', 'pickup_location']);
                    $product_detail = $product_variant_detail[0]->pickup_location;
                }

                if (isset($product_variant_detail[0]->weight) && $product_variant_detail[0]->weight > 15) {
                    return response()->json([
                        'error' => true,
                        'message' => 'More than 15kg weight is not allowed',
                        'language_message_key' => 'more_then_15_kg_weight_is_not_allowed',

                    ]);
                } else {

                    $pickup_postcode = fetchDetails(PickupLocation::class, ['id' => $product_detail, 'status' => 1], 'pincode');

                    $availability_data = [
                        'pickup_postcode' => !$pickup_postcode->isEmpty() ? $pickup_postcode[0]->pincode : "",
                        'delivery_postcode' => $delivery_pincode,
                        'cod' => 0,
                        'weight' => isset($product_variant_detail[0]->weight) ? $product_variant_detail[0]->weight : 0,
                        'declared_value' => $declared_value,
                    ];

                    $check_deliveribility = $shiprocket->check_serviceability($availability_data);

                    $shiprocket_data = app(ShiprocketService::class)->shiprocketRecomendedData($check_deliveribility);

                    $availability_data_with_cod = [
                        'pickup_postcode' => !$pickup_postcode->isEmpty() ? $pickup_postcode[0]->pincode : "",
                        'delivery_postcode' => $delivery_pincode,
                        'cod' => 1,
                        'weight' => isset($product_variant_detail[0]->weight) ? $product_variant_detail[0]->weight : 0,
                        'declared_value' => $declared_value,
                    ];

                    $check_deliveribility_with_cod = $shiprocket->check_serviceability($availability_data_with_cod);
                    $shiprocket_data_with_cod = app(ShiprocketService::class)->shiprocketRecomendedData($check_deliveribility_with_cod);

                    if (isset($check_deliveribility['status_code']) && $check_deliveribility['status_code'] == 422) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Invalid Delivery Pincode',
                            'language_message_key' => 'invalid_delivery_pincode',
                        ]);
                    } else {
                        $estimate_data = [
                            'pickup_availability' => $shiprocket_data['pickup_availability'],
                            'courier_name' => $shiprocket_data['courier_name'],
                            'delivery_charge_with_cod' => $shiprocket_data_with_cod['rate'],
                            'delivery_charge_without_cod' => $shiprocket_data['rate'],
                            'estimate_date' => $shiprocket_data['etd'],
                            'estimate_days' => $shiprocket_data['estimated_delivery_days'],
                        ];
                        if (isset($check_deliveribility['status']) && $check_deliveribility['status'] == 200 && !empty($check_deliveribility['data']['available_courier_companies'])) {
                            $estimate_date = $check_deliveribility['data']['available_courier_companies'][0]['etd'];

                            return response()->json([
                                'error' => false,
                                'message' => 'Product is deliverable by ' . $estimate_date,
                                'data' => $estimate_data,
                            ]);
                        } else {


                            return response()->json([
                                'error' => true,
                                'message' => 'Product is not deliverable on ' . $delivery_pincode,
                                'language_message_key' => 'products_are_not_deliverable',
                                'data' => $estimate_data,
                            ]);
                        }
                    }
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'No product variants found',
                    'language_message_key' => 'no_product_variant_found'
                ]);
            }
        }
    }

    public function send_withdrawal_request(Request $request)
    {

        $rules = [
            'payment_address' => 'required',
            'amount' => 'required|numeric|gt:0',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $user_id = auth()->user()->id;
        $payment_address = $request->input('payment_address');
        $amount = $request->input('amount');

        $user = User::find($user_id);

        if ($user) {
            if ($amount <= $user->balance) {
                $data = [
                    'user_id' => $user_id,
                    'payment_address' => $payment_address,
                    'payment_type' => 'customer',
                    'amount_requested' => $amount,
                ];

                if (PaymentRequest::create($data)) {
                    $lastAddedRequest = PaymentRequest::latest()->first();

                    if ($lastAddedRequest) {
                        $data = $lastAddedRequest->toArray();
                        $data['created_at'] = Carbon::parse($data['created_at'])->format('Y-m-d H:i:s');
                    }

                    app(WalletService::class)->updateBalance($amount, $user_id, 'deduct');
                    $user = User::find($user_id);

                    return response()->json([
                        'error' => false,
                        'message' => 'Withdrawal Request Sent Successfully',
                        'language_message_key' => 'withdrawel_request_sent_successfully',
                        'amount' => $user->balance,
                        'data' => $data,
                    ]);
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Cannot send Withdrawal Request. Please try again later.',
                        'language_message_key' => 'cannot_send_withdrawel_request'
                    ]);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'You do not have enough balance to send the withdrawal request.',
                    'language_message_key' => 'you_do_not_have_enough_balance_to_send_withdrawel_request'

                ]);
            }
        }

        return response()->json([
            'error' => true,
            'message' => 'User not found.',
            'language_message_key' => 'user_does_not_exist'
        ]);
    }
    public function get_withdrawal_request(Request $request)
    {

        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $user_id = auth('sanctum')->id();
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $user_data = fetchDetails(PaymentRequest::class, ['user_id' => $user_id], '*', $limit, $offset, $sort, $order);

        $data = array_map(function ($item) {
            $item->remarks = $item->remarks ?? "";
            return $item;
        }, $user_data->all());

        $user_data_count = fetchDetails(PaymentRequest::class, ['user_id' => $user_id], '*');
        return response()->json([
            'error' => empty($data) ? true : false,
            'message' => empty($data) ? 'Withdrawal Request Not Found' : 'Withdrawal Request Retrieved Successfully',
            'language_message_key' => empty($data) ? 'withdrawel_request_not_found' : 'withdrawel_request_retrived_successfully',
            'total' => empty($data) ? 0 : count($user_data_count),
            'data' => $data,
        ]);
    }
    public function send_bank_transfer_proof(Request $request)
    {

        /*
           order_id:5
           attachments[]:file  {optional} {type allowed -> image,video,document,spreadsheet,archive}
       */

        $rules = [
            'order_id' => 'required|numeric|exists:orders,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $order_id = ($request->input('order_id') != null) ? $request->input('order_id') : 0;
        $order = fetchDetails(Order::class, ['id' => $order_id], 'id');

        if ($order->isEmpty()) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found!',
                'language_message_key' => 'order_not_found'
            ]);
        }

        if (!File::exists('storage/bank_transfer_proof')) {
            File::makeDirectory('storage/bank_transfer_proof', 0755, true);
        }

        try {
            $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
            $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
            $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';

            $media = StorageType::find($mediaStorageType);

            $mediaIds = [];

            if ($request->hasFile('attachments')) {

                $files = $request->file('attachments');



                foreach ($files as $file) {
                    $mediaItem = $media->addMedia($file)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));

                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);

                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('bank_transfer_proof', $disk);

                    $mediaIds[] = $mediaItem->id;

                    if ($disk == 'public') {
                        $uploaded_images[] = [
                            'image_path' => 'bank_transfer_proof/' . $mediaItem->file_name,
                        ];
                    }
                }
            }
            if ($disk == 's3') {
                $media_list = $media->getMedia('bank_transfer_proof');
                for ($i = 0; $i < count($mediaIds); $i++) {
                    $media_url = $media_list[($media_list->count()) - (count($mediaIds) - $i)]->getUrl();

                    $uploaded_images[] = [
                        'image_path' => $media_url,
                    ];

                    Media::destroy($mediaIds[$i]);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }

        $data = array(
            'order_id' => $order_id,
            'attachments' => $uploaded_images,
            'disk' => $disk,
        );

        if (app(OrderService::class)->addBankTransferProof($data)) {
            $responseImages = array_map(function ($item) {
                return [
                    'image_path' => asset('storage/' . $item['image_path']),
                ];
            }, $uploaded_images);
            return response()->json([
                'error' => false,
                'message' => 'Bank Transfer Proof Added Successfully!',
                'language_message_key' => 'bank_transfer_proof_added_successfully',
                'data' => [
                    'order_id' => $order_id,
                    'attachments' => $responseImages,
                    'disk' => $disk,
                ],
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong',
                'language_message_key' => 'something_went_wrong'
            ]);
        }
    }
    public function download_link_hash(Request $request)
    {

        $rules = [
            'order_item_id' => 'required|numeric|exists:order_items,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }



        $order_item_id = ($request->input('order_item_id') != null) ? $request->input('order_item_id') : '';
        $user_id = auth()->user()->id;

        $order_item_data = fetchDetails(OrderItems::class, ['id' => $order_item_id], '*');


        if ($order_item_data == []) {
            return response()->json([
                'error' => true,
                'message' => 'No orders data found!',
                'language_message_key' => 'no_orders_data_found'
            ]);
        } else {
            $order_id = $order_item_data != '' ? $order_item_data[0]->order_id : 0;
            $transaction_data = fetchDetails(Transaction::class, ['order_id' => $order_id], 'status');
            if (!empty($order_item_id) && !empty($user_id)) {
                if (!empty($order_item_data) && !empty($transaction_data)) {
                    $orderData = $order_item_data[0];
                    $transactionStatus = strtolower($transaction_data[0]->status);

                    if ($order_item_id == $orderData->id && $user_id == $orderData->user_id) {
                        if (in_array($transactionStatus, ['success', 'received'])) {
                            $file = $orderData->hash_link;
                            $url = explode("?", $file)[0];
                            $file_path = preg_match('(http:|https:)', $url) === 1 ? $url : app(MediaService::class)->getMediaImageUrl($url);

                            return response()->json([
                                'error' => false,
                                'message' => 'Data retrieved successfully',
                                'language_message_key' => 'data_retrieved_successfully',
                                'data' => $file_path
                            ]);
                        } else {
                            return response()->json([
                                'error' => true,
                                'message' => 'Transaction is not successful for this order',
                                'language_message_key' => 'transaction_is_not_successful_for_this_order',
                            ]);
                        }
                    } else {
                        return response()->json([
                            'error' => true,
                            'message' => 'You are not authorized to download this file',
                            'language_message_key' => 'you_are_not_authorized_to_download_this_file',
                        ]);
                    }
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'No data found for this order',
                        'language_message_key' => 'no_data_found_for_this_order',
                    ]);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid order item ID or user ID',
                    'language_message_key' => 'invalid_order_item_id_or_user_id',
                ]);
            }
        }
    }

    public function get_offers_sliders(Request $request, CategoryController $CategoryController)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }


        $store_id = ($request->input('store_id') != null) ? $request->input('store_id') : '';
        $sliders = OfferSliders::orderBy('id')->where('store_id', $store_id)->get()->toArray();
        $i = 0;
        $language_code = $request->attributes->get('language_code');
        if ($sliders) {
            foreach ($sliders as $slider) {
                $offer_ids = $slider['offer_ids'];
                $offer_ids = explode(",", $offer_ids);
                $sliders[$i]['banner_image'] = app(MediaService::class)->getMediaImageUrl($slider['banner_image']);
                $sliders[$i]['title'] = app(TranslationService::class)->getDynamicTranslation(OfferSliders::class, 'title', $sliders[$i]['id'], $language_code);
                $offer_data = [];

                if (!empty($offer_ids)) {
                    $offer_data = Offer::whereIn('id', $offer_ids)
                        ->orderByRaw('FIELD(id, ' . $slider['offer_ids'] . ')')
                        ->get()
                        ->toArray();
                }

                $sliders[$i]['offer_images'] = $offer_data;

                for ($j = 0; $j < count($sliders[$i]['offer_images']); $j++) {
                    $sliders[$i]['offer_images'][$j]['link'] = (isset($sliders[$i]['offer_images'][$j]['link']) && !empty($sliders[$i]['offer_images'][$j]['link'])) ? $sliders[$i]['offer_images'][$j]['link'] : "";
                    $sliders[$i]['offer_images'][$j]['title'] = app(TranslationService::class)->getDynamicTranslation(Offer::class, 'title', $sliders[$i]['offer_images'][$j]['id'], $language_code);
                    $sliders[$i]['offer_images'][$j]['min_discount'] = (isset($sliders[$i]['offer_images'][$j]['min_discount']) && !empty($sliders[$i]['offer_images'][$j]['min_discount'])) ? $sliders[$i]['offer_images'][$j]['min_discount'] : "";
                    $sliders[$i]['offer_images'][$j]['max_discount'] = (isset($sliders[$i]['offer_images'][$j]['max_discount']) && !empty($sliders[$i]['offer_images'][$j]['max_discount'])) ? $sliders[$i]['offer_images'][$j]['max_discount'] : "";
                    $sliders[$i]['offer_images'][$j]['image'] = (isset($sliders[$i]['offer_images'][$j]['image']) && !empty($sliders[$i]['offer_images'][$j]['image'])) ? app(MediaService::class)->getMediaImageUrl($sliders[$i]['offer_images'][$j]['image']) : "";
                    $sliders[$i]['offer_images'][$j]['banner_image'] = (isset($sliders[$i]['offer_images'][$j]['banner_image']) && !empty($sliders[$i]['offer_images'][$j]['banner_image'])) ? app(MediaService::class)->getMediaImageUrl($sliders[$i]['offer_images'][$j]['banner_image']) : "";

                    if (strtolower($sliders[$i]['offer_images'][$j]['type']) == 'categories') {
                        $id = (!empty($sliders[$i]['offer_images'][$j]['type_id']) && isset($sliders[$i]['offer_images'][$j]['type_id'])) ? $sliders[$i]['offer_images'][$j]['type_id'] : '';
                        $cat_res = $CategoryController->getCategories($id);
                        $cat_res = $cat_res->original;
                        $sliders[$i]['offer_images'][$j]['category_data'] = $cat_res['categories'][0];
                    } else if (strtolower($sliders[$i]['offer_images'][$j]['type']) == 'products') {
                        $id = (!empty($sliders[$i]['offer_images'][$j]['type_id']) && isset($sliders[$i]['offer_images'][$j]['type_id'])) ? $sliders[$i]['offer_images'][$j]['type_id'] : '';
                        $pro_res = app(ProductService::class)->fetchProduct(NULL, NULL, $id, '', '20', '0', '', '', '', '', '', '', '', '', '', '', $language_code);
                        $id = is_array($pro_res['product'][0]) ? $pro_res['product'][0]['id'] : $pro_res['product'][0]->id;
                        $sliders[$i]['offer_images'][$j]['data'][0]['id'] = $id;

                        $product = $pro_res['product'][0];
                        $image = is_array($product) ? $product['image'] : $product->image;
                        $sliders[$i]['offer_images'][$j]['data'][0]['image'] = app(MediaService::class)->getMediaImageUrl($image);
                    } else if (strtolower($sliders[$i]['offer_images'][$j]['type']) == 'combo_products') {
                        $id = (!empty($sliders[$i]['offer_images'][$j]['type_id']) && isset($sliders[$i]['offer_images'][$j]['type_id'])) ? $sliders[$i]['offer_images'][$j]['type_id'] : '';
                        $pro_res = app(ComboProductService::class)->fetchComboProduct(NULL, NULL, $id, '20', '0', '', '', '', '', '', $store_id, '', '', '', '', $language_code);
                        $sliders[$i]['offer_images'][$j]['data'][0]['id'] = $pro_res['combo_product'][0]->id;
                        $sliders[$i]['offer_images'][$j]['data'][0]['image'] = app(MediaService::class)->getMediaImageUrl($pro_res['combo_product'][0]->image);
                    } else if (strtolower($sliders[$i]['offer_images'][$j]['type']) == 'brand') {
                        $id = (!empty($sliders[$i]['offer_images'][$j]['type_id']) && isset($sliders[$i]['offer_images'][$j]['type_id'])) ? $sliders[$i]['offer_images'][$j]['type_id'] : '';
                        $brand_res = fetchDetails(Brand::class, ["id" => $id], '*');
                        $sliders[$i]['offer_images'][$j]['data'][0]['id'] = $brand_res[0]->id;
                        $sliders[$i]['offer_images'][$j]['data'][0]['name'] = $brand_res[0]->name;
                    }
                }

                $i++;
            }

            return response()->json([
                'error' => false,
                'message' => 'Sliders retrieved successfully',
                'language_message_key' => 'sliders_retrieved_successfully',
                'slider_images' => $sliders,
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'No sliders were found',
                'language_message_key' => 'no_sliders_found'
            ]);
        }
    }

    public function get_categories_sliders(Request $request, CategoryController $CategoryController)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }


        $store_id = ($request->input('store_id') != null) ? $request->input('store_id') : '';
        $sliders = CategorySliders::orderBy('id')->where('store_id', $store_id)->get()->toArray();
        $i = 0;

        if ($sliders) {
            $language_code = $request->attributes->get('language_code');
            foreach ($sliders as $slider) {
                $category_ids = $slider['category_ids'];
                $category_ids = explode(",", $category_ids);
                $sliders[$i]['banner_image'] = app(MediaService::class)->getMediaImageUrl($slider['banner_image']);
                $sliders[$i]['title'] = app(TranslationService::class)->getDynamicTranslation(CategorySliders::class, 'title', $sliders[$i]['id'], $language_code);
                $category_data = [];
                if (!empty($category_ids)) {
                    $category_data = Category::whereIn('id', $category_ids)
                        ->orderByRaw('FIELD(id, ' . $slider['category_ids'] . ')')
                        ->get()
                        ->toArray();
                }

                $sliders[$i]['category_data'] = $category_data;

                for ($j = 0; $j < count($sliders[$i]['category_data']); $j++) {
                    $category_id = $sliders[$i]['category_data'][$j]['id'];

                    // Fetch subcategories
                    $subcategories = Category::where('parent_id', $category_id)->get()->toArray();

                    // Count subcategories
                    $sub_category_count = count($subcategories);

                    $sliders[$i]['category_data'][$j]['image'] = (isset($sliders[$i]['category_data'][$j]['image']) && !empty($sliders[$i]['category_data'][$j]['image'])) ? app(MediaService::class)->getMediaImageUrl($sliders[$i]['category_data'][$j]['image']) : "";
                    $sliders[$i]['category_data'][$j]['name'] = app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $sliders[$i]['category_data'][$j]['id'], $language_code);
                    $sliders[$i]['category_data'][$j]['banner'] = (isset($sliders[$i]['category_data'][$j]['banner']) && !empty($sliders[$i]['category_data'][$j]['banner'])) ? app(MediaService::class)->getMediaImageUrl($sliders[$i]['category_data'][$j]['banner']) : "";

                    // Add subcategory count and data
                    $sliders[$i]['category_data'][$j]['children_count'] = $sub_category_count;

                    // Append base URL to subcategory images and banners
                    foreach ($subcategories as &$subcategory) {
                        // dd($subcategory);
                        $subcategory['image'] = (isset($subcategory['image']) && !empty($subcategory['image'])) ? app(MediaService::class)->getMediaImageUrl($subcategory['image']) : "";
                        $subcategory['name'] = app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $subcategory['id'], $language_code);
                        $subcategory['banner'] = (isset($subcategory['banner']) && !empty($subcategory['banner'])) ? app(MediaService::class)->getMediaImageUrl($subcategory['banner']) : "";
                    }
                    unset($subcategory);

                    $sliders[$i]['category_data'][$j]['children'] = $subcategories;

                    // Remove the 'data' part
                    unset($sliders[$i]['category_data'][$j]['data']);
                }

                $i++;
            }

            return response()->json([
                'error' => false,
                'message' => 'Sliders retrieved successfully',
                'language_message_key' => 'sliders_retrieved_successfully',
                'slider_images' => $sliders,
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'No sliders were found',
                'language_message_key' => 'no_sliders_found'
            ]);
        }
    }



    public function set_combo_product_rating(Request $request, ComboProductRatingController $ComboProductRatingController)
    {
        $rules = [
            'product_id' => 'required|numeric|exists:combo_products,id',
            'title' => 'required',
            'rating' => 'required|min:1|max:5',
            'comment' => 'nullable|string',
            'review_image[]' => 'image|mimes:jpg,png,jpeg,gif|max:8000',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = auth()->user()->id;
        $request['user_id'] = $user_id;
        $files = ($request->allFiles());

        $response = $ComboProductRatingController->set_rating($request, $files);
        $rating_data = $ComboProductRatingController->fetch_rating(($request->input('product_id') != null) ? $request->input('product_id') : '', '', '25', '0', 'id', 'DESC');

        $rating['product_rating'] = $rating_data['product_rating'];

        return response()->json([
            'error' => false,
            'message' => 'Product Rated Successfully',
            'language_message_key' => 'product_rated_successfully',
            'data' => $rating,
        ]);
    }

    public function get_combo_product_rating(Request $request, ComboProductRatingController $ProductRatingController)
    {

        $rules = [
            'product_id' => 'required|numeric|exists:combo_products,id',
            'user_id' => 'numeric|exists:users,id',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'order' => 'string',
            'has_images' => 'boolean',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $user_id = $request->input('user_id');
        $product_id = $request->input('product_id');

        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $has_images = $request->input('has_images', false);


        $pr_rating = fetchDetails(ComboProduct::class, ['id' => $product_id], 'rating');

        $rating = $request->input('rating') != null ? $request->input('rating') : '';
        $rating = $ProductRatingController->fetch_rating(($request->input('product_id') != null) ? $request->input('product_id') : '', $user_id, $limit, $offset, $sort, $order, '', $has_images, $rating, 'true');

        if (!empty($rating['product_rating'])) {
            $response['error'] = false;
            $response['message'] = 'Rating retrieved successfully';
            $response['language_message_key'] = 'ratings_retrived_successfully';
            $response['no_of_rating'] = (!empty($rating['no_of_rating'])) ? $rating['no_of_rating'] : 0;
            $response['no_of_reviews'] = (!empty($rating['no_of_reviews'])) ? $rating['no_of_reviews'] : 0;
            $response['total'] = $rating['total_reviews'];
            $response['star_1'] = $rating['star_1'];
            $response['star_2'] = $rating['star_2'];
            $response['star_3'] = $rating['star_3'];
            $response['star_4'] = $rating['star_4'];
            $response['star_5'] = $rating['star_5'];
            $response['total_images'] = $rating['total_images'];
            $response['product_rating'] = (!empty($pr_rating)) ? $pr_rating[0]->rating : "0";
            $response['data'] = $rating['product_rating'];
        } else {

            $response['error'] = true;
            $response['message'] = 'No ratings found !';
            $response['language_message_key'] = 'no_ratings_found';
            $response['no_of_rating'] = (!empty($rating['no_of_rating'])) ? $rating['no_of_rating'] : 0;
            $response['no_of_reviews'] = (!empty($rating['no_of_reviews'])) ? $rating['no_of_reviews'] : 0;
            $response['star_1'] = $rating['star_1'];
            $response['star_2'] = $rating['star_2'];
            $response['star_3'] = $rating['star_3'];
            $response['star_4'] = $rating['star_4'];
            $response['star_5'] = $rating['star_5'];
            $response['total_images'] = $rating['total_images'];
            $response['product_rating'] = (!empty($pr_rating)) ? $pr_rating[0]->rating : "0";
            $response['data'] = array();

            // $response['error'] = true;
            // $response['message'] = 'No ratings found !';
            // $response['language_message_key'] = 'no_ratings_found';
            // $response['no_of_rating'] = 0;
            // $response['data'] = array();
        }
        return $response;
    }
    public function delete_combo_product_rating(Request $request, ComboProductRatingController $ProductRatingController)
    {

        $rules = [
            'rating_id' => 'required|numeric|exists:combo_product_ratings,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $rating = $ProductRatingController->delete_rating(($request->input('rating_id') != null) ? $request->input('rating_id') : '');

        if ($rating == true) {
            return response()->json([
                'error' => false,
                'message' => 'Rating Deleted Successfully',
                'language_message_key' => 'rating_deleted_successfully',
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Something Went Wrong',
                'language_message_key' => 'something_went_wrong',
            ]);
        }
    }

    /**
     * Auto-detect file type based on controller namespace
     * App\v1\ApiController -> 'app'
     * Seller\v1\ApiController -> 'seller'
     * Delivery_boy\v1\ApiController -> 'delivery'
     */
    private function detectFileType()
    {
        $className = get_class($this);

        if (strpos($className, 'App\\v1\\') !== false) {
            return 'app';
        } elseif (strpos($className, 'Seller\\v1\\') !== false) {
            return 'seller';
        } elseif (strpos($className, 'Delivery_boy\\v1\\') !== false) {
            return 'delivery';
        } elseif (strpos($className, 'Admin\\') !== false) {
            return 'panel';
        }

        // Default fallback
        return 'app';
    }

    /**
     * Get file metadata for a language file without loading all labels
     */
    private function getLanguageFileMetadata($languageCode, $fileType)
    {
        $filename = match ($fileType) {
            'app' => 'app_labels.json',
            'panel', 'admin' => 'panel_labels.json',
            'web' => 'web_labels.json',
            'seller' => 'seller_labels.json',
            'delivery' => 'delivery_labels.json',
            default => 'app_labels.json'
        };

        $filePath = resource_path("lang/{$languageCode}/{$filename}");

        $metadata = [
            'updated_at' => null,
            'missing_labels_count' => 0,
            'total_labels' => 0,
            'file_exists' => file_exists($filePath),
        ];

        if ($metadata['file_exists']) {
            // Use file modification time for updated_at (same approach as get_language_labels)
            $file_modified_time = filemtime($filePath);
            $metadata['updated_at'] = date('d-m-Y H:i:s', $file_modified_time); // dd-mm-yyyy hh:mm:ss format

            // Read file to get metadata counts
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);

            if (isset($data['_metadata'])) {
                $metadata['missing_labels_count'] = $data['_metadata']['missing_labels_count'] ?? 0;
                $metadata['total_labels'] = $data['_metadata']['total_labels'] ?? count($data);
            } else {
                $metadata['total_labels'] = count($data);
            }
        }

        return $metadata;
    }

    public function get_languages(Request $request)
    {
        // Fetch languages from the database
        $languages = Language::select('id', 'language', 'code', 'native_language', 'is_rtl')->get();

        // Auto-detect file type based on controller, allow override via parameter
        $file_type = $request->input('file_type', $this->detectFileType());

        // Convert is_rtl to integer and add file metadata
        $languages = $languages->map(function ($language) use ($file_type) {
            $language->is_rtl = intval($language->is_rtl);

            // Get file metadata for this language and file type
            $filename = match ($file_type) {
                'app' => 'app_labels.json',
                'panel', 'admin' => 'panel_labels.json',
                'web' => 'web_labels.json',
                'seller' => 'seller_labels.json',
                'delivery' => 'delivery_labels.json',
                default => 'app_labels.json'
            };

            $filePath = resource_path("lang/{$language->code}/{$filename}");
            $labels = [];
            $updated_at = null;
            $missing_labels_count = 0;
            $total_labels = 0;

            // Load labels and metadata if file exists
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $data = json_decode($content, true);

                if (isset($data['_metadata'])) {
                    $updated_at = $data['_metadata']['updated_at'] ?? null;
                    $missing_labels_count = $data['_metadata']['missing_labels_count'] ?? 0;
                    $total_labels = $data['_metadata']['total_labels'] ?? count($data);
                    unset($data['_metadata']);
                } else {
                    $total_labels = count($data);
                }

                if (isset($data['_missing_labels'])) {
                    unset($data['_missing_labels']);
                }

                $labels = $data;
            }

            // Add file metadata to language object
            $language->updated_at = $updated_at;
            $language->missing_labels_count = $missing_labels_count;
            $language->total_labels = $total_labels;
            $language->file_exists = file_exists($filePath);
            $language->labels = $labels; // Include labels in response

            return $language;
        });

        // Get system settings to merge with language response
        $settings = [];
        $user_id = auth('sanctum')->check() ? auth('sanctum')->user()->id : null;
        $store_id = $request->input('store_id');

        try {
            $settings = [
                'logo' => 0,
                'privacy_policy' => 1,
                'terms_and_conditions' => 1,
                'fcm_server_key' => 1,
                'contact_us' => 1,
                'payment_method' => 1,
                'about_us' => 1,
                'currency' => 0,
                'user_data' => 0,
                'system_settings' => 1,
                'shipping_policy' => 1,
                'return_policy' => 1,
                'shipping_method' => 1,
            ];

            $general_settings = [];
            foreach ($settings as $type => $isjson) {
                if ($type == 'payment_method') {
                    continue;
                }
                $general_settings[$type] = [];
                $settings_res = app(SettingService::class)->getSettings($type, $isjson);
                $settings_res = json_decode($settings_res, true);

                if ($type == 'logo') {
                    $logo_setting = app(SettingService::class)->getSettings('system_settings', true);
                    $logo_setting = json_decode($logo_setting, true);
                    $settings_res = app(MediaService::class)->getMediaImageUrl($logo_setting['logo']);
                }

                if ($isjson && isset($settings_res[$type])) {
                    array_push($general_settings[$type], $settings_res[$type]);
                } else {
                    array_push($general_settings[$type], $settings_res);
                }
            }

            $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
            $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';
            $general_settings['currency'] = $currency;

            // Only unset ai_setting if user is not authenticated
            if (!auth('sanctum')->check() || empty($user_id)) {
                if (isset($general_settings['system_settings'][0]['ai_setting'])) {
                    unset($general_settings['system_settings'][0]['ai_setting']);
                }
            }

            if (isset($general_settings['shipping_method'][0])) {
                unset($general_settings['shipping_method'][0]['password']);
                unset($general_settings['shipping_method'][0]['email']);
                unset($general_settings['shipping_method'][0]['webhook_token']);
            }
        } catch (\Exception $e) {
            // If settings fail to load, continue without them
            $general_settings = [];
        }

        // Return the fetched languages merged with system settings
        return response()->json([
            'error' => $languages->isEmpty() ? true : false,
            'message' => $languages->isEmpty() ? 'Languages not found' : 'Languages retrieved successfully',
            'language_message_key' => $languages->isEmpty() ? 'languages_not_found' : 'languages_retrieved_successfully',
            'data' => $languages,
            'system_settings' => $general_settings,
            'file_type' => $file_type
        ], 200);
    }

    public function get_blogs(Request $request)
    {
        $rules = [
            'store_id' => 'required_without:store_slug|numeric|exists:stores,id',
            'store_slug' => 'required_without:store_id|string|exists:stores,slug',
            'slug' => 'sometimes|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Resolve store_id from store_slug if provided
        if ($request->filled('store_slug') && !$request->filled('store_id')) {
            $store = fetchDetails(Store::class, ['slug' => $request->input('store_slug')], 'id');
            if ($store->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Store not found',
                    'language_message_key' => 'store_not_found',
                    'data' => []
                ]);
            }
            $store_id = $store[0]->id;
        } else {
            $store_id = $request->input('store_id');
        }

        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);
        $category_id = $request->input('category_id');
        $search = $request->input('search', '');
        $slug = $request->input('slug', '');
        $language_code = $request->attributes->get('language_code') ?? 'en';

        $query = Blog::with(['store'])->where('store_id', $store_id)
            ->where('status', 1);

        // Filter by category if provided
        if (!empty($category_id)) {
            $query->where('category_id', $category_id);
        }

        // Filter by slug if provided
        if (!empty($slug)) {
            $query->where('slug', $slug);
        }

        // Search functionality
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ['%' . $search . '%'])
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%')
                    ->orWhere('meta_keywords', 'like', '%' . $search . '%');
            });
        }

        $total = $query->count();
        $blogs = $query->orderBy('id', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();

        $translationService = app(TranslationService::class);
        $mediaService = app(MediaService::class);

        $blogsData = $blogs->map(function ($blog) use ($translationService, $mediaService, $language_code) {
            $title = $translationService->getDynamicTranslation(Blog::class, 'title', $blog->id, $language_code);
            $category = BlogCategory::find($blog->category_id);
            $categoryName = $category ? $translationService->getDynamicTranslation(BlogCategory::class, 'name', $category->id, $language_code) : '';

            return [
                'id' => $blog->id,
                'title' => $title,
                'slug' => $blog->slug,
                'store_slug' => $blog->store->slug,
                'short_description' => $blog->short_description ?? '',
                'description' => $blog->description ?? '',
                'image' => !empty($blog->image) ? $mediaService->getImageUrl($blog->image) : '',
                'category_id' => $blog->category_id,
                'category_name' => $categoryName,
                'meta_title' => $blog->meta_title ?? '',
                'meta_description' => $blog->meta_description ?? '',
                'meta_keywords' => $blog->meta_keywords ?? '',
                'created_at' => $blog->created_at ? $blog->created_at->toDateTimeString() : '',
                'share_url' => url('/blog/' . $blog->slug)
            ];
        });

        return response()->json([
            'error' => false,
            'message' => 'Blogs retrieved successfully',
            'language_message_key' => 'blogs_retrieved_successfully',
            'data' => $blogsData,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    public function get_blog_details(Request $request)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'blog_id' => 'required|numeric|exists:blogs,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $store_id = $request->input('store_id');
        $blog_id = $request->input('blog_id');
        $language_code = $request->attributes->get('language_code') ?? 'en';

        $blog = Blog::where('id', $blog_id)
            ->where('store_id', $store_id)
            ->where('status', 1)
            ->first();

        if (!$blog) {
            return response()->json([
                'error' => true,
                'message' => 'Blog not found',
                'language_message_key' => 'blog_not_found',
                'data' => []
            ]);
        }

        $translationService = app(TranslationService::class);
        $mediaService = app(MediaService::class);

        $title = $translationService->getDynamicTranslation(Blog::class, 'title', $blog->id, $language_code);
        $category = BlogCategory::find($blog->category_id);
        $categoryName = $category ? $translationService->getDynamicTranslation(BlogCategory::class, 'name', $category->id, $language_code) : '';

        $blogData = [
            'id' => $blog->id,
            'title' => $title,
            'slug' => $blog->slug,
            'short_description' => $blog->short_description ?? '',
            'description' => $blog->description ?? '',
            'image' => !empty($blog->image) ? $mediaService->getImageUrl($blog->image) : '',
            'category_id' => $blog->category_id,
            'category_name' => $categoryName,
            'meta_title' => $blog->meta_title ?? $title,
            'meta_description' => $blog->meta_description ?? $blog->short_description ?? '',
            'meta_keywords' => $blog->meta_keywords ?? '',
            'created_at' => $blog->created_at ? $blog->created_at->toDateTimeString() : '',
            'updated_at' => $blog->updated_at ? $blog->updated_at->toDateTimeString() : '',
            'share_url' => url('/blog/' . $blog->slug),
            'share_data' => [
                'title' => $title,
                'description' => $blog->short_description ?? $blog->meta_description ?? '',
                'image' => !empty($blog->image) ? $mediaService->getImageUrl($blog->image) : '',
                'url' => url('/blog/' . $blog->slug)
            ]
        ];

        return response()->json([
            'error' => false,
            'message' => 'Blog details retrieved successfully',
            'language_message_key' => 'blog_details_retrieved_successfully',
            'data' => $blogData
        ]);
    }

    public function get_blog_categories(Request $request)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $store_id = $request->input('store_id');
        $language_code = $request->attributes->get('language_code') ?? 'en';

        $categories = BlogCategory::where('store_id', $store_id)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get();

        $translationService = app(TranslationService::class);
        $mediaService = app(MediaService::class);

        $categoriesData = $categories->map(function ($category) use ($translationService, $mediaService, $language_code) {
            $name = $translationService->getDynamicTranslation(BlogCategory::class, 'name', $category->id, $language_code);

            return [
                'id' => $category->id,
                'name' => $name,
                'slug' => $category->slug,
                'image' => !empty($category->image) ? $mediaService->getImageUrl($category->image) : ''
            ];
        });

        return response()->json([
            'error' => $categories->isEmpty() ? true : false,
            'message' => $categories->isEmpty() ? 'Blog categories not found' : 'Blog categories retrieved successfully',
            'language_message_key' => $categories->isEmpty() ? 'blog_categories_not_found' : 'blog_categories_retrieved_successfully',
            'data' => $categoriesData
        ]);
    }

    public function get_language_labels(Request $request)
    {
        $rules = [
            'language_code' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $language_code = $request->input('language_code');
        // Auto-detect file type based on controller, allow override via parameter
        $file_type = $request->input('file_type', $this->detectFileType());

        // Try JSON file first (preferred)
        $json_filename = match ($file_type) {
            'app' => 'app_labels.json',
            'panel', 'admin' => 'panel_labels.json',
            'web' => 'web_labels.json',
            'seller' => 'seller_labels.json',
            'delivery' => 'delivery_labels.json',
            default => 'app_labels.json'
        };

        $json_file_path = resource_path('lang/' . $language_code . '/' . $json_filename);

        $labels = [];
        $actual_file_path = null; // Track which file was actually used

        // Try to load from JSON file first for the requested language
        if (file_exists($json_file_path)) {
            $jsonContent = file_get_contents($json_file_path);
            $labels = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $labels = [];
            } else {
                $actual_file_path = $json_file_path; // Track that we used this file
                // Remove metadata from labels before returning
                if (isset($labels['_metadata'])) {
                    unset($labels['_metadata']);
                }
                if (isset($labels['_missing_labels'])) {
                    unset($labels['_missing_labels']);
                }
            }
        }

        // If JSON file doesn't exist or is empty, fallback to English (en) JSON file
        if (empty($labels) && $language_code !== 'en') {
            $en_json_file_path = resource_path('lang/en/' . $json_filename);
            if (file_exists($en_json_file_path)) {
                $jsonContent = file_get_contents($en_json_file_path);
                $labels = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $labels = [];
                } else {
                    $actual_file_path = $en_json_file_path; // Track that we used English file
                    // Remove metadata from labels before returning
                    if (isset($labels['_metadata'])) {
                        unset($labels['_metadata']);
                    }
                    if (isset($labels['_missing_labels'])) {
                        unset($labels['_missing_labels']);
                    }
                }
            }
        }

        // If still empty, fallback to admin_labels.php for the requested language
        if (empty($labels)) {
            $php_file_path = resource_path('lang/' . $language_code . '/admin_labels.php');
            if (file_exists($php_file_path)) {
                $labels = include $php_file_path;
                if (isset($labels['langcode'])) {
                    unset($labels['langcode']);
                }
            }
        }

        // If still empty and not English, fallback to English admin_labels.php
        if (empty($labels) && $language_code !== 'en') {
            $en_php_file_path = resource_path('lang/en/admin_labels.php');
            if (file_exists($en_php_file_path)) {
                $labels = include $en_php_file_path;
                if (isset($labels['langcode'])) {
                    unset($labels['langcode']);
                }
            }
        }

        if (empty($labels)) {
            return response()->json([
                'error' => true,
                'message' => 'Language file not found',
                'language_message_key' => 'language_file_not_found',
                'data' => [],
            ]);
        }

        // Calculate missing labels by comparing with English reference file
        $missing_labels_count = 0;
        if (!empty($labels) && $language_code !== 'en') {
            // Load English reference file for comparison
            $en_reference_file = resource_path('lang/en/' . $json_filename);
            if (file_exists($en_reference_file)) {
                $enContent = file_get_contents($en_reference_file);
                $en_data = json_decode($enContent, true);

                if (is_array($en_data) && json_last_error() === JSON_ERROR_NONE) {
                    // Remove metadata from reference data for comparison
                    if (isset($en_data['_metadata'])) {
                        unset($en_data['_metadata']);
                    }
                    if (isset($en_data['_missing_labels'])) {
                        unset($en_data['_missing_labels']);
                    }

                    // Compare to find missing labels
                    $missing_labels = array_diff_key($en_data, $labels);
                    $missing_labels_count = count($missing_labels);
                }
            }
        }

        // Generate metadata dynamically from the actual file that was used
        $metadata = null;
        if ($actual_file_path && file_exists($actual_file_path)) {
            $file_modified_time = filemtime($actual_file_path);
            $total_labels = count($labels);

            $metadata = [
                'updated_at' => date('d-m-Y H:i:s', $file_modified_time), // dd-mm-yyyy hh:mm:ss format
                'missing_labels_count' => $missing_labels_count,
                'total_labels' => $total_labels,
            ];
        } elseif (!empty($labels)) {
            // If labels came from PHP file, generate metadata without file info
            $metadata = [
                'updated_at' => null,
                'missing_labels_count' => $missing_labels_count,
                'total_labels' => count($labels),
            ];
        }

        $response = [
            'error' => false,
            'message' => 'Language labels retrieved successfully',
            'language_message_key' => 'language_labels_retrieved_successfully',
            'data' => $labels,
        ];

        // Include metadata if available (for client-side storage and re-fetch tracking)
        if ($metadata) {
            $response['metadata'] = $metadata;
        }

        return response()->json($response);
    }


    public function get_language_file_info(Request $request)
    {
        $rules = [
            'language_code' => 'required|string',
            'file_type' => 'sometimes|in:app,panel,web,seller,delivery,admin',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $language_code = $request->input('language_code');
        $file_types = $request->has('file_type') ? [$request->input('file_type')] : ['app', 'panel', 'web'];

        $files_info = [];

        foreach ($file_types as $file_type) {
            $filename = match ($file_type) {
                'app' => 'app_labels.json',
                'panel', 'admin' => 'panel_labels.json',
                'web' => 'web_labels.json',
                'seller' => 'seller_labels.json',
                'delivery' => 'delivery_labels.json',
                default => 'app_labels.json'
            };

            $filePath = resource_path("lang/{$language_code}/{$filename}");

            $info = [
                'file_type' => $file_type,
                'filename' => $filename,
                'exists' => file_exists($filePath),
                'updated_at' => null,
                'missing_labels_count' => 0,
                'total_labels' => 0,
                'file_url' => url("/api/get_language_labels?language_code={$language_code}&file_type={$file_type}"),
            ];

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $data = json_decode($content, true);

                if (isset($data['_metadata'])) {
                    $info['updated_at'] = $data['_metadata']['updated_at'] ?? null;
                    $info['missing_labels_count'] = $data['_metadata']['missing_labels_count'] ?? 0;
                    $info['total_labels'] = $data['_metadata']['total_labels'] ?? count($data);
                    $info['reference_labels'] = $data['_metadata']['reference_labels'] ?? 0;
                } else {
                    unset($data['_metadata']);
                    unset($data['_missing_labels']);
                    $info['total_labels'] = count($data);
                }

                $info['file_size'] = filesize($filePath);
                $info['last_modified'] = date('Y-m-d H:i:s', filemtime($filePath));
            }

            $files_info[] = $info;
        }

        return response()->json([
            'error' => false,
            'message' => 'Language file information retrieved successfully',
            'language_message_key' => 'language_file_info_retrieved_successfully',
            'data' => $files_info
        ]);
    }

    public function top_sellers(Request $request)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $store_id = $request->input('store_id');
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : null;

        // Step 1: Get sellers from OrderItems that are tied to valid store entries
        $sellerIds = OrderItems::select('seller_id')
            ->distinct()
            ->whereHas('sellerStore', fn($q) => $q->where('store_id', $store_id)->where('status', 1))
            ->pluck('seller_id');

        // Step 2: Get the relevant sellers and eager load everything
        $sellers = SellerStore::with([
            'user',
            'store:id,slug',
            'favorites' => fn($q) => $q->where('user_id', $user_id),
            'products' => fn($q) => $q->where('store_id', $store_id)->where('status', 1),
            'comboProducts' => fn($q) => $q->where('store_id', $store_id)->where('status', 1),
        ])
            ->whereIn('seller_id', $sellerIds)
            ->where('store_id', $store_id)
            ->where('status', 1)
            ->get();

        // Step 3: Attach order stats
        $orderItems = OrderItems::whereIn('seller_id', $sellerIds)->get();

        $sellersData = $sellers->map(function ($seller) use ($orderItems, $user_id, $store_id) {
            $sellerOrders = $orderItems->where('seller_id', $seller->seller_id);
            $delivered = $sellerOrders->where('active_status', 'delivered');

            // Check if seller has standard shipping enabled (deliverable_type = 1 means all areas)
            $hasStandardShipping = $seller->deliverable_type == 1;

            // Count products with stock management and pickup location
            $validProducts = $seller->products->filter(function ($product) {
                // Check if product has stock management (stock_type is not null/empty) and pickup location
                return !empty($product->stock_type) && !empty($product->pickup_location);
            });

            // Count combo products with pickup location
            $validComboProducts = $seller->comboProducts->filter(function ($comboProduct) {
                return !empty($comboProduct->pickup_location);
            });

            $totalValidProducts = $validProducts->count() + $validComboProducts->count();

            // If seller has standard shipping enabled, only count products with stock management and pickup location
            // If product count is zero, seller should not be included
            if ($hasStandardShipping && $totalValidProducts == 0) {
                return null; // Skip this seller
            }

            // Use total valid products if standard shipping is enabled, otherwise use total products
            $productCount = $hasStandardShipping ? $totalValidProducts : ($seller->products->count() + $seller->comboProducts->count());

            return (object) [
                'seller_id' => $seller->seller_id,
                'total_commission' => $sellerOrders->sum('seller_commission_amount'),
                'store_logo' => app(MediaService::class)->getMediaImageUrl($seller->logo, 'SELLER_IMG_PATH'),
                'store_name' => $seller->store_name,
                'store_slug' => $seller->store->slug ?? '',
                'store_description' => $seller->store_description,
                'user_id' => $seller->user_id,
                'rating' => $seller->rating,
                'no_of_ratings' => $seller->no_of_ratings,
                'store_thumbnail' => app(MediaService::class)->getMediaImageUrl($seller->store_thumbnail, 'SELLER_IMG_PATH'),
                'seller_name' => optional($seller->user)->username,
                'address' => outputEscaping(str_replace("\r\n", ' ', optional($seller->user)->address)),
                'total_sales' => $delivered->isNotEmpty() ? $delivered->sum('sub_total') : null,
                'total_products' => $productCount,
                'is_favorite' => $seller->favorites->isNotEmpty() ? 1 : 0,
            ];
        })->filter(function ($seller) {
            return $seller !== null; // Remove null entries (sellers with zero valid products when standard shipping is enabled)
        });

        // Step 4: Sort and limit
        $sorted = $sellersData->sortByDesc('total_sales')->values()->take(10);

        return response()->json([
            'error' => $sorted->isEmpty(),
            'message' => $sorted->isEmpty() ? 'Sellers not found' : 'Sellers retrieved successfully',
            'language_message_key' => $sorted->isEmpty() ? 'sellers_not_found' : 'sellers_retrieved_successfully',
            'data' => $sorted,
        ]);
    }


    public function most_selling_products(Request $request)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'zipcode' => 'sometimes|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $store_id = $request->input('store_id');
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : null;
        $zipcode = $request->input('zipcode', null);
        $language_code = $request->attributes->get('language_code');

        // Step 1: Fetch products with necessary relationships and conditions
        $top_selling_products = Product::with([
            'variants' => function ($q) {
                $q->where('status', 1);
            },
            'sellerStore' => function ($q) {
                $q->where('status', 1);
            },
            'sellerData' => function ($q) {
                $q->where('status', 1);
            },
            'favorites' => function ($q) use ($user_id) {
                if ($user_id) {
                    $q->where('user_id', $user_id);
                }
            },
            'taxInfo'
        ])
            ->where('store_id', $store_id)
            ->where('status', 1)
            ->whereHas('variants', function ($q) {
                $q->where('status', 1);
            })
            ->whereHas('sellerStore', function ($q) {
                $q->where('status', 1);
            })
            ->whereHas('sellerData', function ($q) {
                $q->where('status', 1);
            })
            ->withSum('orderItems as total_quantity_sold', 'quantity')
            ->withSum('orderItems as total_sales', 'sub_total')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
        // dd($top_selling_products);

        // Step 2: Weekly sales calculations for best seller flag

        $weekly_sales = Product::where('store_id', $store_id)
            ->with([
                'weeklyOrderItems' => function ($query) {
                    $query->select('product_variant_id', DB::raw('SUM(quantity) as weekly_sale'))
                        ->groupBy('product_variant_id');
                }
            ])
            ->get()
            ->mapWithKeys(function ($product) {
                $totalWeeklySale = $product->weeklyOrderItems->sum('weekly_sale');
                return [$product->id => $totalWeeklySale];
            })
            ->toArray();
        $max_weekly_sale = !empty($weekly_sales) ? max($weekly_sales) : 0;

        // Step 3: Transform products - similar to your existing logic
        $top_selling_products->transform(function ($product) use ($zipcode, $language_code, $max_weekly_sale, $weekly_sales) {
            // Handle deliverable zipcodes and deliverability
            if ($product->deliverable_type != 'NONE' && $product->deliverable_type != 'ALL') {
                $zipcode_ids = explode(",", $product->deliverable_zipcodes);
                $zipcodes = Zipcode::whereIn('id', $zipcode_ids)->pluck('zipcode')->toArray();
                $product->deliverable_zipcodes = implode(",", $zipcodes);
            } else {
                $product->deliverable_zipcodes = '';
            }

            // Check if product is deliverable for the given zipcode
            if (!is_null($zipcode)) {
                $zipcodeDetail = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], '*');
                if (!empty($zipcodeDetail)) {
                    $product->is_deliverable = app(DeliveryService::class)->isProductDelivarable('zipcode', $zipcodeDetail[0]->id, $product->id);
                } else {
                    $product->is_deliverable = false;
                }
            } else {
                $product->is_deliverable = false;
            }

            // If deliverable_type is 1 (probably means deliverable everywhere), mark as deliverable
            if ($product->deliverable_type == 1) {
                $product->is_deliverable = true;
            }

            // Mark new arrivals (created within last 7 days)
            $product->new_arrival = isset($product->created_at) && strtotime($product->created_at) >= strtotime('-7 days');

            // Mark best seller based on weekly sales threshold
            $weeklySale = $weekly_sales[$product->id] ?? 0;
            $product->best_seller = ($max_weekly_sale > 0 && $weeklySale >= ($max_weekly_sale * 0.8));

            // Convert image filename to full URL
            $product->image = app(MediaService::class)->getMediaImageUrl($product->image);
            $product->product_id = intval($product->product_id);
            $product->category_id = intval($product->category_id);
            $product->no_of_ratings = intval($product->no_of_ratings);
            $product->deliverable_type = intval($product->deliverable_type);
            $product->is_prices_inclusive_tax = intval($product->is_prices_inclusive_tax);
            $product->total_sales = intval($product->total_sales);
            $product->is_favorite = intval($product->is_favorite);
            // Translate product name and short description
            $product->product_name = app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $language_code);
            $product->short_description = app(TranslationService::class)->getDynamicTranslation(Product::class, 'short_description', $product->id, $language_code);
            $product->tax_percentage = optional($product->taxInfo)->percentage ?? 0;
            // Calculate prices with tax if prices are NOT inclusive of tax
            if (!$product->is_prices_inclusive_tax) {
                $product->special_price = calculatePriceWithTax($product->tax_percentage, $product->special_price);
                $product->price = calculatePriceWithTax($product->tax_percentage, $product->price);
            }

            return $product;
        });
        $top_selling_products = $top_selling_products->map(function ($product) {
            $price = null;
            $special_price = null;
            $is_prices_inclusive_tax = $product->is_prices_inclusive_tax;
            $variant = null;

            // if ($product->type == 'simple_product' && $product->variants->isNotEmpty()) {
            if (($product->type == 'simple_product' && $product->variants->isNotEmpty()) || ($product->type == 'digital_product' && $product->variants->isNotEmpty())) {
                $variant = $product->variants->first();
            } elseif ($product->type == 'variable_product' && $product->variants->isNotEmpty()) {
                $variant = $product->variants->sortBy('price')->first();
            }

            // Initialize tax-related fields
            $tax_percentages = null;
            $tax_ids = array_filter(explode(',', $product->tax)); // get array of tax ids

            if (!empty($tax_ids)) {
                $tax_values = Tax::whereIn('id', $tax_ids)->pluck('percentage')->toArray();
                $tax_percentages = !empty($tax_values) ? implode(',', $tax_values) : null;
            }

            if ($variant) {
                $price = $variant->price;
                $special_price = $variant->special_price;

                // Apply tax only if NOT inclusive and percentages exist
                if (!$is_prices_inclusive_tax && !empty($tax_values)) {
                    foreach ($tax_values as $percentage) {
                        $price += $price * ($percentage / 100);
                        $special_price += $special_price * ($percentage / 100);
                    }
                }
            }

            return [
                'product_id' => $product->id,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand,
                'product_name' => $product->product_name,
                'short_description' => $product->short_description,
                'created_at' => $product->created_at,
                'image' => app(MediaService::class)->getMediaImageUrl($product->image),
                'rating' => number_format($product->rating ?? 0, 1),
                'no_of_ratings' => $product->no_of_ratings,
                'special_price' => $special_price,
                'price' => $price,
                'type' => $product->type,
                'tax' => $product->tax,
                'deliverable_zipcodes' => $product->deliverable_zipcodes,
                'deliverable_type' => $product->deliverable_type,
                'is_prices_inclusive_tax' => $product->is_prices_inclusive_tax,
                'tax_percentage' => $tax_percentages,
                'tax_id' => $product->tax,
                'total_quantity_sold' => (string) $product->total_quantity_sold,
                'total_sales' => (int) $product->total_sales,
                'is_favorite' => $product->favorites->isNotEmpty() ? 1 : 0,
                'is_deliverable' => (bool) $product->is_deliverable,
                'new_arrival' => (bool) $product->new_arrival,
                'best_seller' => (bool) $product->best_seller,
            ];
        });

        return response()->json([
            'error' => $top_selling_products->isEmpty(),
            'message' => $top_selling_products->isEmpty() ? 'Top-selling products not found' : 'Top-selling products retrieved successfully',
            'language_message_key' => $top_selling_products->isEmpty() ? 'top_selling_products_not_found' : 'top_selling_products_retrieved_successfully',
            'category_ids' => implode(',', $top_selling_products->pluck('category_id')->unique()->filter()->values()->all()),
            'brand_ids' => implode(',', $top_selling_products->pluck('brand_id')->unique()->filter()->values()->all()),
            'data' => $top_selling_products,
        ]);
    }

    public function most_popular_products(Request $request)
    {
        // Validate request

        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'sort' => 'sometimes|string|in:name,id',
            'order' => 'sometimes|string|in:DESC,ASC',
            'limit' => 'sometimes|numeric|min:1',
            'offset' => 'sometimes|numeric|min:0',
            'search' => 'sometimes|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Extract request parameters
        $store_id = $request->input('store_id');
        $user_id = $request->input('user_id') ?? '';
        $sort = $request->input('sort', 'products.name');
        $order = $request->input('order', 'ASC');
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $search = $request->input('search', '');

        // Build the query
        $query = Product::with([
            'variants' => function ($q) {
                $q->where('status', 1);
            },
            'ratings',
            'favorites' => function ($q) use ($user_id) {
                $q->where('user_id', $user_id);
            },
            'sellerStore',
            'sellerData',
        ])
            ->where('store_id', $store_id)
            ->where('status', 1)
            ->whereHas('variants')
            ->whereHas('sellerStore')
            ->whereHas('sellerData');

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $top_rated_products = $query->offset($offset)->limit($limit)->get();
        // dd($top_rated_products);

        $language_code = $request->attributes->get('language_code');
        // Transform the image URL
        $top_rated_products = $top_rated_products->map(function ($product) use ($language_code) {
            // Calculate min prices from variants
            $variants = $product->variants;

            $special_price = null;
            $price = null;

            if ($variants->isNotEmpty()) {
                // If simple product (only one variant)
                if ($variants->count() === 1) {
                    $special_price = $variants->first()->special_price ?? null;
                    $price = $variants->first()->price ?? null;
                } else {
                    // Variable product - get min prices
                    $special_price = $variants->min('special_price');
                    $price = $variants->min('price');
                }
            }

            // Calculate tax percentage (assuming tax is stored as comma separated IDs in product->tax)
            $tax_ids = $product->tax ? explode(',', $product->tax) : [];
            $tax_percentages = Tax::whereIn('id', $tax_ids)->pluck('percentage')->toArray();
            $tax_percentage = !empty($tax_percentages) ? implode(',', $tax_percentages) : null;

            return [
                'id' => $product->id,
                'name' => app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $language_code),
                'short_description' => app(TranslationService::class)->getDynamicTranslation(Product::class, 'short_description', $product->id, $language_code),
                'tax' => $product->tax,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand,  // or brand_id if your attribute is named like that
                'special_price' => calculatePriceWithTax($tax_percentage, $special_price),
                'price' => calculatePriceWithTax($tax_percentage, $price),
                'product_image' => app(MediaService::class)->getMediaImageUrl($product->product_image),
                'rating' => round($product->ratings->avg('rating'), 1) ?? 0,
                'no_of_ratings' => $product->ratings->count(),
                'is_favorite' => $product->favorites->isNotEmpty() ? 1 : 0,
                'tax_percentage' => $tax_percentage ?: null,
                'tax_id' => $product->tax ? $product->tax : null,
            ];
        });

        return response()->json([
            'error' => $top_rated_products->isEmpty() ? true : false,
            'message' => $top_rated_products->isEmpty() ? 'Most popular products not found' : 'Most popular products retrieved successfully',
            'language_message_key' => $top_rated_products->isEmpty() ? 'most_popular_products_not_found' : 'most_popular_products_retrieved_successfully',
            'category_ids' => implode(',', collect($top_rated_products)->pluck('category_id')->unique()->values()->all()),
            'brand_ids' => implode(',', collect($top_rated_products)->pluck('brand_id')->filter()->unique()->values()->all()),
            'data' => $top_rated_products,
        ]);
    }

    public function best_sellers(Request $request)
    {
        /*
           sort:               // { name / id } optional
           order:DESC/ASC      // { default - ASC } optional
           limit:25            // { default - 25 } optional
           offset:0            // { default - 0 } optional
           search:value        // {optional}
        */

        // Validate request

        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'sort' => 'sometimes|string|in:name,id',
            'order' => 'sometimes|string|in:DESC,ASC',
            'limit' => 'sometimes|numeric|min:1',
            'offset' => 'sometimes|numeric|min:0',
            'search' => 'sometimes|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Extract request parameters
        $store_id = $request->input('store_id');
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : '';
        $sort = $request->input('sort', 'seller_store.rating');
        $order = $request->input('order', 'DESC');
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $search = $request->input('search', '');

        // Build the query

        $query = User::whereHas('sellerStore', function ($q) use ($store_id) {
            $q->where('store_id', $store_id)
                ->where('status', 1)
                ->where('rating', '>', 0)
                ->where('no_of_ratings', '>', 0);
        })
            ->with([
                'sellerStore' => function ($q) use ($store_id) {
                    $q->where('store_id', $store_id)
                        ->select([
                            'user_id',
                            'seller_id',
                            'store_name',
                            'store_description',
                            'logo',
                            'store_thumbnail',
                            'rating',
                            'no_of_ratings',
                            'store_id'
                        ])->with('store:id,slug');
                },
                'sellerStore.favorites' => function ($q) use ($user_id) {
                    $q->where('user_id', $user_id);
                }
            ]);

        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                    ->orWhereHas('sellerStore', function ($q2) use ($search) {
                        $q2->where('store_name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Fetch all results
        $users = $query->get();

        // Sort in PHP
        if ($sort == 'name') {
            $users = $order == 'DESC' ? $users->sortByDesc('username') : $users->sortBy('username');
        } elseif ($sort == 'id') {
            $users = $order == 'DESC' ? $users->sortByDesc('id') : $users->sortBy('id');
        } else {
            $users = $order == 'DESC'
                ? $users->sortByDesc(fn($user) => optional($user->sellerStore)->rating)
                : $users->sortBy(fn($user) => optional($user->sellerStore)->rating);
        }

        // Paginate manually
        $best_sellers = $users->slice($offset, $limit)->values();

        $best_sellers = $best_sellers->map(function ($user) use ($store_id, $user_id) {
            $store = $user->sellerStore;

            if (!$store) {
                return null;
            }

            // Count total products
            $total_products = Product::where('seller_id', $store->seller_id)
                ->where('store_id', $store_id)
                ->where('status', 1)
                ->count()
                + ComboProduct::where('seller_id', $store->seller_id)
                ->where('store_id', $store_id)
                ->where('status', 1)
                ->count();

            return [
                'seller_id' => $store->seller_id,
                'user_id' => $user->id,
                'seller_name' => $user->username,
                'store_name' => $store->store_name,
                'store_description' => $store->store_description,
                'store_logo' => app(MediaService::class)->getMediaImageUrl($store->logo, 'SELLER_IMG_PATH'),
                'store_thumbnail' => app(MediaService::class)->getMediaImageUrl($store->store_thumbnail, 'SELLER_IMG_PATH'),
                'store_slug' => $store->store->slug ?? '',
                'rating' => $store->rating,
                'no_of_ratings' => $store->no_of_ratings,
                'total_products' => $total_products,
                'is_favorite' => $store->favorites->isNotEmpty() ? 1 : 0,
            ];
        });

        return response()->json([
            'error' => $best_sellers->isEmpty(),
            'message' => $best_sellers->isEmpty() ? 'Best sellers not found' : 'Best sellers retrieved successfully',
            'language_message_key' => $best_sellers->isEmpty() ? 'best_sellers_not_found' : 'best_sellers_retrieved_successfully',
            'data' => $best_sellers,
        ]);
    }
    public function download_order_invoice(Request $request)
    {
        $rules = [
            'order_id' => 'required|numeric|exists:orders,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $order_id = $request->input('order_id');
            $userId = auth('sanctum')->check() ? auth('sanctum')->id() : null;

            if (!isExist(['id' => $order_id], Order::class)) {
                $response = [
                    'error' => true,
                    'message' => 'No order found!',
                    'language_message_key' => 'no_order_found',
                    'data' => [],
                ];
                return response()->json($response);
            }

            // Generating the URL to download the invoice
            $invoice_url = route('admin.orders.generatAPPInvoicePDF', ['id' => $order_id, 'user_id' => $userId, 'path' => true]);

            // dd($invoice_url);
            $response = [
                'error' => false,
                'message' => 'Invoice URL generated successfully',
                'invoice_url' => $invoice_url,
            ];

            return response()->json($response);
        }
    }


    public function phonepe_app(Request $request)
    {
        /*
            type:wallet/cart  //required
            transaction_id:741258 //required
            mobile:123456478   // required for wallet
            amount:5200   // required for wallet
            order_id:1642 // required for cart
        */
        $rules = [
            'type' => 'required|string',
            'transaction_id' => 'required|numeric',
            'mobile' => 'required_if:type,wallet|numeric',
            'amount' => 'required_if:type,wallet|numeric',
            'order_id' => 'required_if:type,cart|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $phonepe = new Phonepe();
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : null;
        if ($request->type == 'wallet') {
            $data = [
                'amount' => $request->amount * 100,
                'mobile' => $request->mobile,
                'order_id' => $request->transaction_id,
                'merchantTransactionId' => $request->order_id,
            ];
            $res = $phonepe->phonepe_checksum_v2($data);
            Transaction::create([
                'user_id' => $user_id,
                'txn_id' => $data['merchantTransactionId'],
                'type' => 'credit',
                'amount' => $request->amount,
                'transaction_type' => 'wallet',
                'order_id' => $request->order_id ?? null,
                'status' => 'awaiting',
            ]);
            return response()->json([
                'error' => false,
                'data' => $res,
            ]);
        } else {
            $order_details = app(OrderService::class)->fetchOrders($request->order_id, $user_id, false, false, false, false, 'o.id', 'DESC');
            if ($order_details['total'] != 0) {
                $transaction_id = time() . "" . rand("100", "999");
                $amount = $order_details['order_data'][0]->total_payable;
                $mobile = $order_details['order_data'][0]->mobile;
                $data = array(
                    // 'merchantTransactionId' => $transaction_id,
                    'merchantTransactionId' => $request->order_id,
                    'merchantUserId' => $user_id,
                    'amount' => $amount * 100,
                    'mobileNumber' => $mobile
                );
                $res = $phonepe->phonepe_checksum_v2($data);
                Transaction::create([
                    'user_id' => $user_id ?? null,
                    'txn_id' => $res['merchantOrderId'] ?? $data['merchantOrderId'] ?? null,
                    'amount' => $request->type == 'wallet' ? $request->amount : $amount,
                    'transaction_type' => $request->type == 'wallet' ? 'wallet' : 'transaction',
                    'order_id' => $request->order_id ?? null,
                    'status' => 'awaiting',
                    'type' => 'phonepe',
                ]);
                return response()->json([
                    'error' => false,
                    'data' => $res,
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Order Not Found',
                    'language_message_key' => 'order_not_found',
                ]);
            }
        }
    }

    // public function phonepe_app_old(Request $request)
    // {

    //     /*
    //         type:wallet/cart  //required
    //         transaction_id:741258 //required
    //         mobile:123456478   // required for wallet
    //         amount:5200   // required for wallet
    //         order_id:1642 // required for cart
    //     */

    //     $rules = [
    //         'type' => 'required|string',
    //         'transaction_id' => 'required|numeric',
    //         'mobile' => 'required_if:type,wallet|numeric',
    //         'amount' => 'required_if:type,wallet|numeric',
    //         'order_id' => 'required_if:type,cart|numeric',
    //     ];
    //     if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
    //         return $response;
    //     }


    //     $phonepe = new Phonepe();
    //     if ($request->type == 'wallet') {
    //         $data = [
    //             'final_total' => $request->amount * 100,
    //             'mobile' => $request->mobile,
    //             'order_id' => $request->transaction_id,
    //         ];
    //         $v2_response = $this->phonepe_app_new($request);
    //         $v2_response = $v2_response->original['data'];
    //         // dd($v2_response);
    //         $res = $phonepe->phonepe_checksum($data);

    //         return response()->json([
    //             'error' => false,
    //             'data' => $res,
    //             'v2_response' => $v2_response,
    //         ]);
    //     } else {
    //         if (auth()->check()) {
    //             $user_id = auth()->user()->id;
    //         }
    //         $order_details = app(OrderService::class)->fetchOrders($request->order_id, $user_id, false, false, false, false, 'o.id', 'DESC');
    //         if ($order_details['total'] != 0) {
    //             $amount = $order_details['order_data'][0]->total_payable * 100;
    //             $data = [
    //                 'final_total' => $amount,
    //                 'mobile' => $order_details['order_data'][0]->mobile,
    //                 'order_id' => $request->transaction_id,
    //             ];
    //             $v2_response = $this->phonepe_app_new($request);
    //             $v2_response = $v2_response->original['data'];
    //             $res = $phonepe->phonepe_checksum($data);

    //             return response()->json([
    //                 'error' => false,
    //                 'data' => $res,
    //                 'v2_response' => $v2_response,
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'error' => true,
    //                 'message' => 'Order Not Found',
    //             ]);
    //         }
    //     }
    // }
    public function get_paypal_link(request $request)
    {
        /*
            order_id : 1
            amount : 150
        */
        header("Content-Type: text/html");

        $rules = [
            'amount' => 'required',
            'order_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : null;

        $order_id = $request->order_id;
        $amount = $request->amount;

        if (!is_numeric($order_id)) {
            return
                $this->paypal_transaction_webview($user_id, $order_id, $amount);
        }
        return
            $this->paypal_transaction_webview($user_id, $order_id, $amount);
    }
    public function app_payment_status(Request $request)
    {
        $paypalInfo = $request->all();

        if (!empty($paypalInfo) && $request->has('st')) {
            $status = strtolower($request->input('st'));
            $custom = $request->input('custom', '');
            $txn_id = $request->input('tx', '');
            $payment_status = $request->input('st', '');
            $payment_amount = $request->input('amt', '');

            // Extract order information from custom field
            $orderData = [];
            if (!empty($custom)) {
                $customParts = explode('|', $custom);
                if (count($customParts) >= 3) {
                    $orderData['user_id'] = $customParts[0];
                    $orderData['order_id'] = $customParts[1];
                    $orderData['payer_email'] = $customParts[2];
                }
            }

            // Update order status based on PayPal status
            if (!empty($orderData['order_id'])) {
                try {
                    $orderService = app(OrderService::class);
                    $order = $orderService->fetchOrders($orderData['order_id']);

                    if (!empty($order['order_data']) && !$order['order_data']->isEmpty()) {
                        $orderModel = $order['order_data']->first();

                        switch ($status) {
                            case 'completed':
                                // Update order to paid status
                                $orderModel->payment_status = 'paid';
                                $orderModel->order_status = 'processing';
                                $orderModel->payment_method = 'paypal';
                                $orderModel->transaction_id = $txn_id;
                                $orderModel->save();

                                $response = [
                                    'error' => false,
                                    'message' => 'Payment Completed Successfully',
                                    'data' => array_merge($paypalInfo, [
                                        'order_id' => $orderData['order_id'],
                                        'payment_status' => 'paid',
                                        'order_status' => 'processing'
                                    ]),
                                ];
                                break;

                            case 'authorized':
                                $orderModel->payment_status = 'authorized';
                                $orderModel->payment_method = 'paypal';
                                $orderModel->transaction_id = $txn_id;
                                $orderModel->save();

                                $response = [
                                    'error' => false,
                                    'message' => 'Your payment has been Authorized successfully. We will capture your transaction within 30 minutes, once we process your order. After successful capture, coins will be credited automatically.',
                                    'data' => array_merge($paypalInfo, [
                                        'order_id' => $orderData['order_id'],
                                        'payment_status' => 'authorized'
                                    ]),
                                ];
                                break;

                            case 'pending':
                                $orderModel->payment_status = 'pending';
                                $orderModel->payment_method = 'paypal';
                                $orderModel->transaction_id = $txn_id;
                                $orderModel->save();

                                $response = [
                                    'error' => false,
                                    'message' => 'Your payment is pending and is under process. We will notify you once the status is updated.',
                                    'data' => array_merge($paypalInfo, [
                                        'order_id' => $orderData['order_id'],
                                        'payment_status' => 'pending'
                                    ]),
                                ];
                                break;

                            default:
                                $orderModel->payment_status = 'failed';
                                $orderModel->payment_method = 'paypal';
                                $orderModel->transaction_id = $txn_id;
                                $orderModel->save();

                                $response = [
                                    'error' => true,
                                    'message' => 'Payment Cancelled / Declined',
                                    'language_message_key' => 'payment_cancelled_or_declined',
                                    'data' => array_merge($paypalInfo, [
                                        'order_id' => $orderData['order_id'],
                                        'payment_status' => 'failed'
                                    ]),
                                ];
                                break;
                        }
                    } else {
                        $response = [
                            'error' => true,
                            'message' => 'Order not found',
                            'language_message_key' => 'order_not_found',
                            'data' => $paypalInfo,
                        ];
                    }
                } catch (\Exception $e) {
                    $response = [
                        'error' => true,
                        'message' => 'Error updating order status: ' . $e->getMessage(),
                        'data' => $paypalInfo,
                    ];
                }
            } else {
                // Fallback response if no order data found
                switch ($status) {
                    case 'completed':
                        $response = [
                            'error' => false,
                            'message' => 'Payment Completed Successfully',
                            'data' => $paypalInfo,
                        ];
                        break;

                    case 'authorized':
                        $response = [
                            'error' => false,
                            'message' => 'Your payment has been Authorized successfully. We will capture your transaction within 30 minutes, once we process your order. After successful capture, coins will be credited automatically.',
                            'data' => $paypalInfo,
                        ];
                        break;

                    case 'pending':
                        $response = [
                            'error' => false,
                            'message' => 'Your payment is pending and is under process. We will notify you once the status is updated.',
                            'data' => $paypalInfo,
                        ];
                        break;

                    default:
                        $response = [
                            'error' => true,
                            'message' => 'Payment Cancelled / Declined',
                            'language_message_key' => 'payment_cancelled_or_declined',
                            'data' => $paypalInfo,
                        ];
                        break;
                }
            }
        } else {
            $response = [
                'error' => true,
                'message' => 'Payment Cancelled / Declined',
                'language_message_key' => 'payment_cancelled_or_declined',
                'data' => $paypalInfo,
            ];
        }

        return response()->json($response);
    }

    /**
     * PayPal IPN (Instant Payment Notification) handler.
     * PayPal POSTs to this endpoint after a payment completes.
     * Validates the IPN with PayPal, then creates/updates the transaction
     * and credits the user's wallet (following the same pattern as Razorpay/Stripe).
     */
    public function ipn(Request $request)
    {
        $ipnData = $request->all();
        Log::alert('[PayPal IPN] Received: ' . json_encode($ipnData));

        $paypal  = new PayPal();
        $verified = $paypal->validate_ipn($ipnData);

        if (!$verified) {
            Log::alert('[PayPal IPN] INVALID — PayPal could not verify the IPN.');
            return response()->json(['error' => true, 'message' => 'Invalid IPN', 'language_message_key' => 'invalid_ipn'], 400);
        }

        Log::alert('[PayPal IPN] VERIFIED');

        $payment_status = strtolower($ipnData['payment_status'] ?? '');
        $txn_id         = $ipnData['txn_id']         ?? '';
        $mc_gross       = $ipnData['mc_gross']        ?? 0;
        $custom         = $ipnData['custom']          ?? '';   // "user_id|order_id|email"

        Log::alert("[PayPal IPN] payment_status={$payment_status} | txn_id={$txn_id} | mc_gross={$mc_gross} | custom={$custom}");

        // Parse the custom field to extract user_id and order_id
        $customParts = explode('|', $custom);
        $user_id     = $customParts[0] ?? null;
        $order_id    = $customParts[1] ?? null;

        Log::alert("[PayPal IPN] Parsed: user_id={$user_id} | order_id={$order_id}");

        // Determine if this is a wallet refill (order_id is non-numeric) or an order payment
        $is_wallet = !is_numeric($order_id);

        if ($payment_status !== 'completed') {
            Log::alert("[PayPal IPN] Skipped — payment_status={$payment_status} (not completed)");
            return response()->json(['error' => false, 'message' => 'IPN received, not completed']);
        }

        // Check for duplicate / already processed transaction
        $existingTxn = Transaction::where('txn_id', $txn_id)->first();
        if ($existingTxn && $existingTxn->status === 'success') {
            Log::alert("[PayPal IPN] Duplicate — txn_id={$txn_id} already processed.");
            return response()->json(['error' => false, 'message' => 'Already processed']);
        }

        if ($is_wallet) {
            // ---- WALLET REFILL PATH ----
            Log::alert("[PayPal IPN] Wallet refill path for user_id={$user_id} amount={$mc_gross}");

            if ($existingTxn) {
                // Update the awaiting transaction created by WalletController::refill()
                $existingTxn->update([
                    'txn_id'  => $txn_id,
                    'status'  => 'success',
                    'message' => 'Wallet refill successful via PayPal IPN',
                ]);
                Log::alert("[PayPal IPN] Updated existing transaction id={$existingTxn->id}");
            } else {
                // Fallback: create it now if WalletController::refill() was not called earlier
                Transaction::create([
                    'transaction_type' => 'wallet',
                    'user_id'          => $user_id,
                    'order_id'         => $order_id,
                    'type'             => 'credit',
                    'txn_id'           => $txn_id,
                    'amount'           => $mc_gross,
                    'status'           => 'success',
                    'message'          => 'Wallet refill successful via PayPal IPN',
                ]);
                Log::alert("[PayPal IPN] Created new wallet transaction for user_id={$user_id}");
            }

            // Credit the wallet
            if (app(WalletService::class)->updateBalance($mc_gross, $user_id, 'add')) {
                Log::alert("[PayPal IPN] Wallet credited user_id={$user_id} amount={$mc_gross}");
            } else {
                Log::alert("[PayPal IPN] ERROR: wallet update failed for user_id={$user_id}");
            }
        } else {
            // ---- ORDER PAYMENT PATH ----
            Log::alert("[PayPal IPN] Order payment path for order_id={$order_id}");

            if ($existingTxn) {
                $existingTxn->update([
                    'txn_id'  => $txn_id,
                    'status'  => 'success',
                    'message' => 'Payment received via PayPal IPN',
                ]);
            } else {
                Transaction::create([
                    'transaction_type' => 'transaction',
                    'user_id'          => $user_id,
                    'order_id'         => $order_id,
                    'type'             => 'paypal',
                    'txn_id'           => $txn_id,
                    'amount'           => $mc_gross,
                    'status'           => 'success',
                    'message'          => 'Payment received via PayPal IPN',
                ]);
            }

            // Update order items status
            updateDetails(['active_status' => 'received'], ['order_id' => $order_id], \App\Models\OrderItems::class);
            $order_status = json_encode([['received', date('d-m-Y h:i:sa')]]);
            updateDetails(['status' => $order_status], ['order_id' => $order_id], \App\Models\OrderItems::class);

            Log::alert("[PayPal IPN] Order {$order_id} marked as received");
            app(OrderService::class)->sendOrderInvoiceMail($order_id);
            app(FirebaseNotificationService::class)->sendCustomNotificationOnPaymentSuccess($order_id, $user_id);
        }

        Log::alert('[PayPal IPN] Processing complete');
        return response()->json(['error' => false, 'message' => 'IPN processed successfully']);
    }

    public function paypal_transaction_webview($user_id, $order_id, $amount)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User not found',
                'language_message_key' => 'user_does_not_exist',
                'data' => []
            ]);
        }

        // Retrieve the order safely
        $order = (is_numeric($order_id)) ? app(OrderService::class)->fetchOrders($order_id) : [];

        $data['user'] = $user;
        $data['order'] = (!empty($order['order_data']) && $order['order_data'] instanceof \Illuminate\Support\Collection && $order['order_data']->isNotEmpty()) ? $order['order_data']->first() : '';
        $data['payment_type'] = 'paypal';
        $returnURL = route('app_payment_status');
        $cancelURL = route('app_payment_status');
        $notifyURL = route('ipn');
        $txn_id = time() . '-' . rand();
        $payeremail = $user->email;
        $paypal = new PayPal();

        $paypal->addField('return', $returnURL);
        $paypal->addField('cancel_return', $cancelURL);
        $paypal->addField('notify_url', $notifyURL);
        $paypal->addField('item_name', 'Payment for Order #' . $order_id);
        $paypal->addField('custom', $user_id . '|' . $order_id . '|' . $payeremail);
        $paypal->addField('item_number', $order_id);
        $paypal->addField('amount', $amount);

        // Debug: Check if business email is set
        $debugFields = $paypal->debug_fields();

        // Get PayPal form HTML
        $formHtml = $paypal->paypal_form();

        // Get PayPal URL from credentials
        $credentials = $paypal->get_credentials();
        $paypal_url = $credentials['paypal_mode'] == 'sandbox' ?
            'https://www.sandbox.paypal.com/cgi-bin/webscr' :
            'https://www.paypal.com/cgi-bin/webscr';

        return response()->json([
            'error' => false,
            'message' => 'PayPal payment initiated',
            'data' => [
                'paypal_url' => $paypal_url,
                'form_html' => $formHtml,
                'order_id' => $order_id,
                'amount' => $amount,
                'return_url' => $returnURL,
                'cancel_url' => $cancelURL,
                'debug' => [
                    'business_email' => $credentials['paypal_business_email'],
                    'currency' => $credentials['currency_code'],
                    'mode' => $credentials['paypal_mode'],
                    'fields' => $debugFields
                ]
            ]
        ]);
    }




    public function get_similar_products(Request $request)
    {

        $rules = [
            'category_id' => 'required|exists:categories,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $store_id = $request->input('store_id');
        $order = $request->filled('p_order') ? $request->input('p_order', 'DESC') : 'DESC';
        $sort = $request->filled('p_sort') ? $request->input('p_sort', 'p.id') : 'products.id';
        $limit = $request->filled('limit') ? $request->input('limit', 10) : 10;
        $offset = $request->filled('offset') ? $request->input('offset', 0) : 0;
        $category_id = $request->input('category_id', null);
        $user_id = $request->input('user_id', '');
        $language_code = $request->attributes->get('language_code');
        $products = app(ProductService::class)->fetchProduct($user_id, '', '', $category_id, $limit, $offset, $sort, $order, null, '', '', '', $store_id, '1', '', '', $language_code);
        if (!empty($products['product'])) {
            $response = [
                'error' => false,
                'message' => 'Products retrieved successfully!',
                'language_message_key' => 'products_retrived_successfully',
                'min_price' => isset($products['min_price']) && !empty($products['min_price']) ? strval($products['min_price']) : '0',
                'max_price' => isset($products['max_price']) && !empty($products['max_price']) ? strval($products['max_price']) : '0',
                'filters' => isset($products['filters']) && !empty($products['filters']) ? $products['filters'] : [],
                'tags' => !empty($tags) ? $tags : [],
                'total' => isset($products['total']) ? strval($products['total']) : '',
                'offset' => $offset,
                'data' => $products['product'],
            ];
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Products Not Found !',
                'language_message_key' => 'products_not_found',
                'data' => [],
            ], 200);
        }
        return response()->json($response);
    }
    public function get_combo_similar_products(Request $request)
    {
        $rules = [
            'product_id' => 'required|exists:combo_products,id',
            'store_id' => 'required|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $store_id = $request->input('store_id');

        $combo_product = ComboProduct::where('id', $request->input('product_id'))
            ->first();

        if (!$combo_product) {
            return response()->json([
                'error' => true,
                'message' => 'Combo product not found.',
                'language_message_key' => 'combo_product_not_found',
                'code' => 102
            ]);
        }
        $order = $request->filled('order') ? $request->input('order', 'DESC') : 'DESC';
        $sort = $request->filled('sort') ? $request->input('sort', 'combo_products.id') : 'combo_products.id';
        $limit = $request->filled('limit') ? $request->input('limit', 10) : 10;
        $offset = $request->filled('offset') ? $request->input('offset', 0) : 0;

        $product_ids = ComboProduct::where('id', $combo_product->id)
            ->pluck('product_ids')->first();

        $product_ids = explode(',', $product_ids);

        $categoryIds = Product::whereIn('id', $product_ids)
            ->pluck('category_id');
        $category_id = $categoryIds->toArray();
        if ($categoryIds->isEmpty()) {
            return response()->json([
                'error' => true,
                'message' => 'No categories found for the products in the combo.',
                'language_message_key' => 'no_categories_found_for_combo_products',
                'code' => 102
            ]);
        }
        $language_code = $request->attributes->get('language_code');

        $similar_products = app(ComboProductService::class)->fetchComboProduct('', '', '', $limit, $offset, $sort, $order, '', '', '', $store_id, $category_id, '', '', '', $language_code);
        // dD($similar_products['combo_product']);
        return response()->json([
            'error' => false,
            'message' => empty($similar_products['combo_product']) ? 'Products not found' : 'Products retrieved successfully',
            'language_message_key' => empty($similar_products['combo_product']) ? 'products_not_found' : 'products_retrieved_successfully',
            'data' => empty($similar_products['combo_product']) ? [] : $similar_products,
            'code' => 200,
        ]);
    }

    public function search_products(Request $request)
    {
        // Validate the request
        $rules = [
            'store_id' => 'required|exists:stores,id',
            'search' => 'required|string',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $search = trim($request->input('search'));
        $store_id = $request->input('store_id');
        $keywords = explode(' ', $search);
        $language_code = $request->attributes->get('language_code');

        // Search Products using Eloquent
        $products = Product::with('category')
            ->where('store_id', $store_id)
            ->where('status', 1)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $keyword = strtolower($keyword);
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"])
                            ->orWhereHas('category', function ($q) use ($keyword) {
                                $q->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"]);
                            })
                            ->orWhereRaw('FIND_IN_SET(?, LOWER(tags))', [$keyword]);
                    });
                }
            })->get();

        // Search Combo Products using Eloquent
        $comboProducts = ComboProduct::where('store_id', $store_id)
            ->where('status', 1)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $keyword = strtolower($keyword);
                    $query->orWhere(function ($subQuery) use ($keyword) {
                        $subQuery->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                            ->orWhereRaw('FIND_IN_SET(?, LOWER(tags))', [$keyword]);
                    });
                }
            })->get();


        // Transform products
        $productsTransformed = $products->map(function ($product) use ($language_code) {
            return (object) [
                'type' => 'products',
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'product_name' => app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $language_code),
                'tags' => explode(',', $product->tags),
                'product_image' => app(MediaService::class)->getMediaImageUrl($product->image),
                'category_id' => $product->category_id,
                'category_name' => app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $product->category_id, $language_code),
            ];
        });

        // Transform combo products
        $comboTransformed = $comboProducts->map(function ($combo) use ($language_code) {
            return (object) [
                'type' => 'combo_products',
                'product_id' => $combo->id,
                'store_id' => $combo->store_id,
                'product_name' => app(TranslationService::class)->getDynamicTranslation(ComboProduct::class, 'title', $combo->id, $language_code),
                'tags' => explode(',', $combo->tags),
                'product_image' => app(MediaService::class)->getMediaImageUrl($combo->image),
            ];
        });

        // Merge both collections
        $results = $productsTransformed->merge($comboTransformed)->values();

        return response()->json([
            'error' => $results->isEmpty(),
            'message' => $results->isEmpty() ? 'Products not found' : 'Products retrieved successfully',
            'language_message_key' => $results->isEmpty() ? 'products_not_found' : 'products_retrieved_successfully',
            'data' => $results,
        ]);
    }


    public function get_most_searched_history(Request $request)
    {
        $searchTerm = trim($request->input('search'));
        $storeId = $request->input('store_id');

        $rules = [
            'search' => 'string|max:255',
            'store_id' => 'required|integer',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Find or create the search history record
        $searchHistory = SearchHistory::firstOrNew([
            'search_term' => $searchTerm,
            'store_id' => $storeId,
        ]);

        // Increment clicks or set to 1 if new
        $searchHistory->clicks = $searchHistory->exists ? $searchHistory->clicks + 1 : 1;
        $searchHistory->save();

        // Fetch most searched terms
        $mostSearchedTerms = SearchHistory::where('store_id', $storeId)
            ->orderByDesc('clicks')
            ->limit(10)
            ->get(['search_term', 'clicks']);

        return response()->json([
            'error' => false,
            'message' => 'Search terms fetched successfully.',
            'data' => $mostSearchedTerms,
        ]);
    }
    public function razorpay_create_order(Request $request)
    {

        $rules = [
            'order_id' => 'required',
            'amount' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $order_id = $request->input('order_id') ?? '';
        $order = app(OrderService::class)->fetchOrders($order_id, '', '', '', 1, 0, 'o.id', 'DESC');
        $currency = fetchDetails(Currency::class, ['is_default' => 1]);
        $currency = isset($currency) && !empty($currency) ? $currency[0]->code : "";
        if (!empty($order) && !empty($currency) && is_numeric($order_id)) {
            $price = $order['order_data']->first()->total_payable;
            $amount = intval($price * 100);
            $razorpay = new Razorpay();
            $create_order = $razorpay->create_order($amount, $order_id, $currency);
            if (!empty($create_order)) {
                return response()->json([
                    'error' => false,
                    'message' => 'Razorpay order created successfully.',
                    'language_message_key' => 'order_created_successfully',
                    'data' => $create_order,
                ]);
            } else {
                return response()->json([
                    'error' => false,
                    'message' => 'Razorpay order not created.',
                    'language_message_key' => 'something_went_wrong',
                    'data' => array(),
                ]);
            }
        } elseif ((!is_numeric($order_id) && strpos($order_id, "wallet-refill-user") !== false)) {
            $amount = $request->input('amount') ?? '';
            // Mobile app already sends amount * 100, so no need to multiply again
            // $amount = intval($amount * 100);
            $amount = intval($amount);
            Log::alert("[API] Razorpay wallet order - using amount: {$amount} (app already multiplied by 100)");
            $razorpay = new Razorpay();
            $create_order = $razorpay->create_order($amount, $order_id, $currency);
            if (!empty($create_order)) {
                return response()->json([
                    'error' => false,
                    'message' => 'Razorpay order created successfully.',
                    'language_message_key' => 'order_created_successfully',
                    'data' => $create_order,
                ]);
            } else {
                return response()->json([
                    'error' => false,
                    'message' => 'Razorpay order not created.',
                    'language_message_key' => 'something_went_wrong',
                    'data' => array(),
                ]);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Details not found.',
                'language_message_key' => 'no_order_found',
                'data' => array(),
            ]);
        }
    }
    public function get_zones(Request $request)
    {
        $request['language_code'] = $request->attributes->get('language_code');
        return getZones($request);
    }
    public function paystack_webview(Request $request)
    {

        $rules = [
            'amount' => 'required|numeric'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $user = Auth::user();
        $email = fetchDetails(User::class, ['id' => $user->id], 'email');
        $email = isset($email) && !empty($email) ? $email[0]->email : '';

        $paystack = new Paystack();
        $data = [
            'user_id' => $user->id,
            'amount' => $request->input('amount'),
            'email' => $email
        ];
        $initialize_payment = $paystack->initialize_payment($data);
        // dd($initialize_payment);
        return response()->json($initialize_payment);
    }

    public function handle_paystack_callback(Request $request)
    {
        $reference = $request->query('reference');
        Log::alert("[API] handle_paystack_callback called with reference: {$reference}");

        if (!$reference) {
            return response()->json([
                'error' => true,
                'message' => 'No reference supplied',
                'language_message_key' => 'no_reference_supplied',
            ]);
        }

        $paystack = new Paystack();
        $verify = $paystack->verify_transaction($reference);
        $verify = json_decode($verify, true);

        Log::alert("[API] Paystack verification response: " . json_encode($verify));

        // dd($verify);
        if ($verify && isset($verify['status']) && $verify['status'] == true) {
            // Payment was successful - create transaction and update wallet
            // Get user from payment data since callback might not be authenticated
            $customer_email = $verify['data']['customer']['email'] ?? null;
            $amount = $verify['data']['amount'] / 100; // Convert from kobo to currency
            Log::alert("[API] Paystack payment successful - customer_email: {$customer_email}, amount: {$amount}, reference: {$reference}");

            if (!$customer_email) {
                Log::alert("[API] No customer email found in Paystack response");
                return response()->json([
                    'error' => true,
                    'message' => 'Payment verified but customer information missing',
                    'language_message_key' => 'payment_verified_customer_info_missing'
                ]);
            }

            // Find user by email
            $user = User::where('email', $customer_email)->first();
            if (!$user) {
                Log::alert("[API] User not found for email: {$customer_email}");
                return response()->json([
                    'error' => true,
                    'message' => 'Payment verified but user not found',
                    'language_message_key' => 'payment_verified_user_not_found'
                ]);
            }

            $user_id = $user->id;
            Log::alert("[API] Found user_id: {$user_id} for email: {$customer_email}");

            // Check if transaction already exists
            $existing_transaction = fetchDetails(Transaction::class, ['txn_id' => $reference]);
            if (!$existing_transaction->isEmpty()) {
                Log::alert("[API] Transaction already exists for reference: {$reference}");
                return response()->json([
                    'error' => false,
                    'message' => 'Payment verified successfully',
                    'data' => $verify['data']
                ]);
            }

            // Create transaction record
            $transaction_data = [
                'transaction_type' => 'wallet',
                'user_id' => $user_id,
                'order_id' => '',
                'type' => 'credit',
                'txn_id' => $reference,
                'amount' => $amount,
                'status' => 'success',
                'message' => 'Wallet refill successful via Paystack',
            ];

            $transaction = Transaction::create($transaction_data);
            Log::alert("[API] Transaction created with ID: {$transaction->id}");

            // Update wallet balance
            $old_balance = $user->balance ?? 0;
            if (!app(WalletService::class)->updateBalance($amount, $user_id, 'add')) {
                Log::alert("[API] Failed to update wallet balance for user_id: {$user_id}");
                return response()->json([
                    'error' => true,
                    'message' => 'Payment verified but wallet update failed',
                    'language_message_key' => 'payment_verified_wallet_update_failed'
                ]);
            }

            // Get updated balance
            $user->refresh();
            $new_balance = $user->balance ?? 0;

            Log::alert("[API] Wallet balance updated - old: {$old_balance}, new: {$new_balance}");

            return response()->json([
                'error' => false,
                'message' => 'Payment verified successfully',
                'data' => $verify['data'],
                'balance_updated' => true,
                'old_balance' => $old_balance,
                'new_balance' => $new_balance
            ]);
        } else {
            Log::alert("[API] Paystack payment verification failed");
            return response()->json([
                'error' => true,
                'message' => 'Payment verification failed',
                'language_message_key' => 'payment_verification_failed'
            ]);
        }
    }
    public function verify_credentials(Request $request)
    {
        $rules = [
            'mobile' => 'required|numeric',
            'country_code' => 'required|string|max:10',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            // Find user by both mobile and country_code
            $user = User::where('mobile', $request->mobile)
                ->where('country_code', $request->country_code)
                ->first();
            if (!$user) {
                return response()->json([
                    'error' => false,
                    'message' => 'User not found for this mobile and country code',
                    'language_message_key' => 'user_not_found_for_country_code',
                ], 200);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'The mobile number is already registered. Please log in',
                    'language_message_key' => 'mobile_already_registered',
                ], 200);
            }
        }
    }

    /**
     * Handle affiliate deep link redirect
     * Returns product details with affiliate context
     */
    public function affiliate_redirect(Request $request)
    {
        $rules = [
            'ref' => 'required|string',
            'product_slug' => 'required_without:combo_slug|string',
            'combo_slug' => 'required_without:product_slug|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $ref = $request->input('ref');
        $productSlug = $request->input('product_slug');
        $comboSlug = $request->input('combo_slug');
        $isCombo = !empty($comboSlug);

        // Find affiliate tracking record
        $tracking = \App\Models\AffiliateTracking::where('token', $ref)->first();

        if (!$tracking) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid affiliate link',
                'language_message_key' => 'invalid_affiliate_link',
            ], 404);
        }

        // Increment click count
        $tracking->increment('click_count');
        $tracking->update(['last_clicked_at' => now()]);

        // Get product details
        if ($isCombo) {
            $product = ComboProduct::where('slug', $comboSlug)->first();
        } else {
            $product = Product::where('slug', $productSlug)->first();
        }

        if (!$product) {
            return response()->json([
                'error' => true,
                'message' => 'Product not found',
                'language_message_key' => 'product_not_found',
            ], 404);
        }

        return response()->json([
            'error' => false,
            'message' => 'Affiliate link tracked successfully',
            'language_message_key' => 'affiliate_link_tracked',
            'data' => [
                'product_id' => $product->id,
                'product_slug' => $isCombo ? $comboSlug : $productSlug,
                'product_type' => $isCombo ? 'combo_products' : 'products',
                'affiliate_token' => $ref,
                'store_id' => $product->store_id ?? null,
            ],
        ]);
    }

    /**
     * Track affiliate link click
     */
    public function track_affiliate_click(Request $request)
    {
        $rules = [
            'ref' => 'required|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $ref = $request->input('ref');

        // Find affiliate tracking record
        $tracking = \App\Models\AffiliateTracking::where('token', $ref)->first();

        if (!$tracking) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid affiliate token',
                'language_message_key' => 'invalid_affiliate_token',
            ], 404);
        }

        // Increment click count
        $tracking->increment('click_count');
        $tracking->update(['last_clicked_at' => now()]);

        return response()->json([
            'error' => false,
            'message' => 'Click tracked successfully',
            'language_message_key' => 'click_tracked_successfully',
            'data' => [
                'click_count' => $tracking->click_count,
            ],
        ]);
    }

    /**
     * Track affiliate conversion (purchase)
     * Called when user completes purchase via affiliate link
     */
    public function track_affiliate_conversion(Request $request)
    {
        $rules = [
            'ref' => 'required|string',
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|numeric',
            'order_amount' => 'required|numeric',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $ref = $request->input('ref');
        $orderId = $request->input('order_id');
        $productId = $request->input('product_id');
        $orderAmount = $request->input('order_amount');

        // Find affiliate tracking record
        $tracking = \App\Models\AffiliateTracking::where('token', $ref)
            ->where('product_id', $productId)
            ->first();

        if (!$tracking) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid affiliate token or product',
                'language_message_key' => 'invalid_affiliate_data',
            ], 404);
        }

        // Calculate commission with consistent formatting
        $commissionRate = floatval($tracking->category_commission ?? 0);
        $orderAmount = floatval($orderAmount);
        $commissionAmount = ($orderAmount * $commissionRate) / 100;

        // Round commission to 2 decimal places for consistency
        $commissionAmount = round($commissionAmount, 2);

        // Update tracking record
        $tracking->increment('usage_count');
        $tracking->increment('commission_earned', $commissionAmount);
        $tracking->increment('total_order_value', $orderAmount);

        // Create affiliate transaction record
        \App\Models\AffiliateTransaction::create([
            'affiliate_id' => $tracking->affiliate_id,
            'order_id' => $orderId,
            'product_id' => $productId,
            'amount' => $commissionAmount,
            'type' => 'credit',
            'status' => 'pending',
            'description' => "Commission from order #{$orderId}",
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Data Retrieved From Cart !',
            'language_message_key' => 'data_retrieved_from_cart',
            'data' => [
                'commission_amount' => number_format($commissionAmount, 2, '.', ''),
                'commission_rate' => number_format($commissionRate, 2, '.', ''),
                'order_amount' => number_format($orderAmount, 2, '.', ''),
                'currency_data' => app(CurrencyService::class)->getPriceCurrency($commissionAmount),
                'currency_order_amount_data' => app(CurrencyService::class)->getPriceCurrency($orderAmount),
            ],
        ]);
    }

    /**
     * Generate deep link for products, sellers, or blogs
     * Returns both web URL and deep link URL
     */
    public function generate_deep_link(Request $request)
    {
        $rules = [
            'type' => 'required|in:product,combo_product,seller,blog',
            'slug' => 'required|string',
            'store_slug' => 'required|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $type = $request->input('type');
        $slug = $request->input('slug');
        $storeSlug = $request->input('store_slug', '');

        // Get deep link scheme from database settings
        $systemSettings = app(SettingService::class)->getSettings('system_settings', true);
        $systemSettings = json_decode($systemSettings, true);
        $deepLinkScheme = $systemSettings['deep_link_scheme'] ?? 'eshop';
        // Remove "://" if it exists (for backward compatibility)
        $deepLinkScheme = str_replace('://', '', $deepLinkScheme);
        // Append "://"
        $deepLinkScheme = $deepLinkScheme . '://';

        // Generate deep link based on type
        switch ($type) {
            case 'product':
                $deepLinkUrl = "{$deepLinkScheme}product/{$slug}";
                $webUrl = url("/products/{$slug}");
                if ($storeSlug) {
                    $deepLinkUrl .= "?store={$storeSlug}";
                    $webUrl .= "?store={$storeSlug}";
                }
                break;

            case 'combo_product':
                $deepLinkUrl = "{$deepLinkScheme}combo-product/{$slug}";
                $webUrl = url("/combo-products/{$slug}");
                if ($storeSlug) {
                    $deepLinkUrl .= "?store={$storeSlug}";
                    $webUrl .= "?store={$storeSlug}";
                }
                break;

            case 'seller':
                $deepLinkUrl = "{$deepLinkScheme}seller/{$slug}";
                // For seller, we use seller_id instead of slug
                $webUrl = url("/seller/{$slug}");
                if ($storeSlug) {
                    $deepLinkUrl .= "?store={$storeSlug}";
                    $webUrl .= "?store={$storeSlug}";
                }
                break;

            case 'blog':
                $deepLinkUrl = "{$deepLinkScheme}blog/{$slug}";
                $webUrl = url("/blogs/{$slug}");
                if ($storeSlug) {
                    $deepLinkUrl .= "?store={$storeSlug}";
                    $webUrl .= "?store={$storeSlug}";
                }
                break;

            default:
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid type',
                    'language_message_key' => 'invalid_type',
                ], 400);
        }

        return response()->json([
            'error' => false,
            'message' => 'Deep link generated successfully',
            'language_message_key' => 'deep_link_generated',
            'data' => [
                'type' => $type,
                'slug' => $slug,
                'deep_link_url' => $deepLinkUrl,
                'web_url' => $webUrl,
            ],
        ]);
    }

    /**
     * Handle deep link redirect for products, sellers, and blogs
     * Validates the entity exists and returns details
     */
    public function deep_link_redirect(Request $request)
    {
        // Allow 'store' key as alias for 'store_slug'
        if ($request->has('store') && !$request->has('store_slug')) {
            $request->merge(['store_slug' => $request->input('store')]);
        }

        $rules = [
            'type' => 'required|in:product,combo_product,seller,blog',
            'slug' => 'required|string',
            'store_slug' => 'required|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $type = $request->input('type');
        $slug = $request->input('slug');
        $language_code = $request->attributes->get('language_code') ?? 'en';

        $storeSlug = $request->input('store_slug');
        $store = Store::where('slug', $storeSlug)->first();

        if (!$store) {
            return response()->json([
                'error' => true,
                'message' => 'Store not found',
                'language_message_key' => 'store_not_found',
            ], 404);
        }

        // Find the entity based on type
        switch ($type) {
            case 'product':
                $entity = Product::where('slug', $slug)
                    ->where('store_id', $store->id)
                    ->first();

                if (!$entity) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Product not found',
                        'language_message_key' => 'product_not_found',
                    ], 404);
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Product found',
                    'language_message_key' => 'product_found',
                    'data' => [
                        'type' => 'product',
                        'id' => $entity->id,
                        'slug' => $entity->slug,
                        'name' => app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $entity->id, $language_code),
                        'store_id' => $entity->store_id,
                    ],
                ]);

            case 'combo_product':
                $entity = ComboProduct::where('slug', $slug)
                    ->where('store_id', $store->id)
                    ->first();

                if (!$entity) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Combo product not found',
                        'language_message_key' => 'combo_product_not_found',
                    ], 404);
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Combo product found',
                    'language_message_key' => 'combo_product_found',
                    'data' => [
                        'type' => 'combo_product',
                        'id' => $entity->id,
                        'slug' => $entity->slug,
                        'title' => app(TranslationService::class)->getDynamicTranslation(ComboProduct::class, 'title', $entity->id, $language_code),
                        'store_id' => $entity->store_id,
                    ],
                ]);

            case 'seller':
                // Use seller_id to find the seller store
                $entity = SellerStore::where('seller_id', $slug)->first();
                if (!$entity) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Seller not found',
                        'language_message_key' => 'seller_not_found',
                    ], 404);
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Seller found',
                    'language_message_key' => 'seller_found',
                    'data' => [
                        'type' => 'seller',
                        'id' => $entity->seller_id, // Return seller_id
                        'slug' => $entity->slug,
                        'name' => $entity->store_name,
                        'store_name' => $entity->store_name,
                    ],
                ]);

            case 'blog':
                $entity = Blog::where('slug', $slug)->first();
                if (!$entity) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Blog not found',
                        'language_message_key' => 'blog_not_found',
                    ], 404);
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Blog found',
                    'language_message_key' => 'blog_found',
                    'data' => [
                        'type' => 'blog',
                        'id' => $entity->id,
                        'slug' => $entity->slug,
                        'title' => app(TranslationService::class)->getDynamicTranslation(Blog::class, 'title', $entity->id, $language_code),
                    ],
                ]);

            default:
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid type',
                    'language_message_key' => 'invalid_type',
                ], 400);
        }
    }
}
